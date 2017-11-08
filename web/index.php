<?php

require('../vendor/autoload.php');
require('../lib/BirkmanAPI.php');

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

  return '<pre>'.print_r($birkmanData, true) . "</pre>";
});

$app->run();
