<?php

require('../vendor/autoload.php');

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

function postAsJSONToSlack($responseUrl, $data) {
	$streamOpts = [
		'http' => [
			'method'  => 'POST',
			'timeout' => '30',
			'header'  => join(PHP_EOL, [
				"Content-type: application/json",
			]) . PHP_EOL,
			'content' => json_encode($data)
		]
	];
	$jsonPosterStreamContext = stream_context_create($streamOpts);
	file_get_contents($responseUrl, null, $jsonPosterStreamContext);
}

$app = new Silex\Application();
$app['debug'] = true;
$app['base_dir'] = __DIR__.'/..';

/** $app['conn'] \PDO */
$app['conn'] = require $app['base_dir'].'/db/connection.php';
$app['birkman_repository'] = new \BirkmanRepository($app['conn']);

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

// Our web handlers
$app->get('/', function() use($app) {
    return $app['twig']->render('index.twig');
});

$app->get('/grid', function(Request $request) use($app) {
    $birkmanId = $request->query->get('birkman_id');
    $birkmanData = $app['birkman_repository']->fetchByBirkmanId($birkmanId);
    $grid = new \BirkmanGrid($birkmanData['birkman_data']);
    ob_start();
    $grid->asPNG();
    $imageData = ob_get_contents();
    ob_end_clean();

    // respond to slack
    return new Response(
        $imageData,
        200,
        ['Content-Type' => 'image/png']
    );
});

$app->get('/slack-slash-command/', function(Request $request) use($app) {
    $app['monolog']->addDebug('Requested Birkman GRID');
    $birkman = new \BirkmanAPI(getenv('BIRKMAN_API_KEY'));

    $slackToken = $request->query->get('token');

    if ($slackToken !== getenv('SLACK_TOKEN')) {
        $app->abort(403, "token does not match app's configured SLACK_TOKEN");
    }

    // grab url to post responses to
    $slack_response_url = $request->query->get('response_url');

    // Parse out the birkman slash command
    // /birkman GTW013 sjhdf skdfjh
    // apperas in API as
    // text=GTW013 sjhdf skdfjh
    $commandFromSlack = $request->query->get('text');
    // Clean up commandFromSlack
    $commandFromSlack = preg_replace('/\s+/', ' ', $commandFromSlack);
    list($birkmanCommand, $birkmanCommandArgsString) = explode(' ', $commandFromSlack, 2);
    $birkmanCommandArgs = array_filter(explode(' ', $birkmanCommandArgsString));

    try {
        switch ($birkmanCommand) {
        // show birkman grid
        case 'grid':
            // `grid [@slackusername]`
            // if slackusername is omitted, A will be the "current user"
            if (count($birkmanCommandArgs) == 0) {
                $slackUser = $request->query->get('user_name');
            } elseif (count($birkmanCommandArgs) == 1) {
                $slackUser = trim($birkmanCommandArgs[0], '@');
            } else {
                throw new \Exception("Expected 0 or 1 arguments, got " . count($birkmanCommandArgs));
            }
            $birkmanData = $app['birkman_repository']->fetchBySlackUsername($slackUser);
            $grid = new \BirkmanGrid($birkmanData['birkman_data']);
            ob_start();
            $grid->asPNG();
            $imageData = ob_get_contents();
            ob_end_clean();

            postAsJSONToSlack($slack_response_url, [
                'attachments' => [
                    [
                        "title"     => "Birkman Grid for {$birkmanData['birkman_data']['name']}",
                        "fallback"  => "Birkman Grid image",
                        "image_url" => "https://{$request->getHost()}:{$request->getPort()}/grid/{$birkmanData['birkman_id']}"
                    ]
                ]
            ]);


            // respond to slack
            return new Response(
                'OK',
                Response::HTTP_OK
            );
            break;
        case 'compare':
            // `compare [@slackusernameA] @slackusernameB`
            // if slackusernameA is omitted, A will be the "current user"
            if (count($birkmanCommandArgs) == 1) {
                $slackUserA = $request->query->get('user_name');
                $slackUserB = trim($birkmanCommandArgs[0], '@');
            } elseif (count($birkmanCommandArgs) == 2) {
                $slackUserA = trim($birkmanCommandArgs[0], '@');
                $slackUserB = trim($birkmanCommandArgs[1], '@');
            } else {
                throw new \Exception("Expected 1 or 2 arguments, got " . count($birkmanCommandArgs));
            }

            // Translate slack username to birkman user ids
            $userABirkman = $app['birkman_repository']->fetchBySlackUsername($slackUserA);
            $userBBirkman = $app['birkman_repository']->fetchBySlackUsername($slackUserB);

            // run report
            $birkmanData = $birkman->getAlastairsComparativeReport($userABirkman['birkman_data'], $userBBirkman['birkman_data']);

            // respond to slack
            return new Response(
                'OK',
                Response::HTTP_OK
            );
            break;
        default:
            throw new \RecordNotFoundException("{$birkmanCommand} does not exist.");
        }
    } catch (\Exception $e) {
        $app['monolog']->addDebug($e->getMessage());
        die($e->getMessage);
        // Return a response instead of blowing up.
        return new Response(
            $e->getMessage(),
            Response::HTTP_NOT_FOUND
        );
    }
});

$app->match('/admin/users', function(Silex\Application $app, \Symfony\Component\HttpFoundation\Request $request) {
    /** @var BirkmanRepository $birkmanRepository */
    $birkmanRepository = $app['birkman_repository'];

    $users = $birkmanRepository->fetchAll();

    // HACK to update all birkman datas...
    $birkman = new BirkmanAPI(getenv('BIRKMAN_API_KEY'));
    foreach ($users as $user) {
        $birkmanData = $birkman->getUserCoreData($user['birkman_id']);
        $birkmanRepository->updateBirkmanData($user['birkman_id'], json_encode($birkmanData));
    }

    if ($request->getMethod() === 'POST') {
        if ($request->request->has('insert')) {
            $birkmanRepository->createUser(
                $request->request->get('birkman_id'),
                $request->request->get('slack_username')
            );
        } elseif ($request->request->has('delete')) {
            $birkmanRepository->delete($request->request->get('birkman_id'));
        }

        return new \Symfony\Component\HttpFoundation\RedirectResponse('/admin/users');
    }

    return $app['twig']->render('admin_users.html.twig', ['users' => $users]);
})->method('GET|POST');

$app->run();
