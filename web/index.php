<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

require('../vendor/autoload.php');
require('../lib/BirkmanAPI.php');
require('../lib/BirkmanGrid.php');

$app = new Silex\Application();
$app['debug'] = true;

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

$app->get('/grid/', function(Request $request) use($app) {
  $app['monolog']->addDebug('Requested Birkman GRID');

  $slackToken = $request->query->get('token');
  if ($slackToken !== getenv('SLACK_TOKEN')) {
      $app->abort(403, "token does not match app's configured SLACK_TOKEN");
  }

  // look up "birkman id" from slack profile
  // /birkman GTW013 sjhdf skdfjh
  // text=GTW013 sjhdf skdfjh
  $userId = $request->query->get('text');

  // build birkman grid
  $birkman = new BirkmanAPI(getenv('BIRKMAN_API_KEY'));
  $birkmanData = $birkman->getUserCoreData($userId);
  $grid = new BirkmanGrid($birkmanData);
  ob_start();
  $grid->asPNG();
  $imageData = ob_get_contents();
  ob_end_clean();

  // return response
  return new Response(
      $imageData,
      200,
      ['Content-Type' => 'image/png']
  );
});

$app->get('/slack-slash-command/', function(Request $request) use($app) {
  $app['monolog']->addDebug('Requested Birkman GRID');

  $slackToken = $request->query->get('token');
  if ($slackToken !== getenv('SLACK_TOKEN')) {
      $app->abort(403, "token does not match app's configured SLACK_TOKEN");
  }

  // look up "birkman id" from slack profile
  // /birkman GTW013 sjhdf skdfjh
  // text=GTW013 sjhdf skdfjh
  $command = $request->query->get('text');
  $userA = 'foo';
  $userB = 'bar';
  $userABirkmanId = '123456';
  $userBBirkmanId = '654321';

  // build birkman grid
  $birkman = new BirkmanAPI(getenv('BIRKMAN_API_KEY'));
  $birkmanData = $birkman->getAlastairsComparativeReport($userABirkmanId, $userBBirkmanId);

  print_r($birkmanData);

  // send responses

  // tell slack we're all good
  return new Response(
      'OK',
      200
  );
});

$app->run();
