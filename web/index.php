<?php

use Symfony\Component\HttpFoundation\Response;

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

$app->get('/grid/{userId}', function(Silex\Application $silexApp, $userId) use($app) {
  $app['monolog']->addDebug('Requested Birkman GRID');

  $birkman = new BirkmanAPI(getenv('BIRKMAN_API_KEY'));
  $birkmanData = $birkman->getUserCoreData($userId);
  $grid = new BirkmanGrid($birkmanData);
  ob_start();
  $grid->asPNG();
  $imageData = ob_get_contents();
  ob_end_clean();

  return new Response(
      $imageData,
      200,
      ['Content-Type' => 'image/png']
  );
});

$app->run();
