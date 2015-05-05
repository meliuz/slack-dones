<?php

use Silex\Application;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\ServicesLoader;
use App\RoutesLoader;
use Carbon\Carbon;
use Igorw\Silex\ConfigServiceProvider;

date_default_timezone_set('America/Sao_Paulo');

define('ROOT_PATH', __DIR__.'/..');

// handling CORS preflight request
$app->before(function (Request $request) {
    if ($request->getMethod() === 'OPTIONS') {
        $response = new Response();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        $response->setStatusCode(200);

        return $response->send();
    }
}, Application::EARLY_EVENT);

// handling CORS respons with right headers
$app->after(function (Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
});

// accepting JSON
$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : []);
    }
});

// providers
$app->register(new ServiceControllerServiceProvider());

$app->register(new DoctrineServiceProvider(), [
    'db.options' => [
        'driver' => 'pdo_sqlite',
        'path' => realpath(ROOT_PATH.'/storage/database/app.db'),
    ],
]);

$app->register(new HttpCacheServiceProvider(), ['http_cache.cache_dir' => ROOT_PATH.'/storage/cache',]);

$app->register(new MonologServiceProvider(), [
    'monolog.logfile' => ROOT_PATH.'/storage/logs/'.Carbon::now('Europe/London')->format('Y-m-d').'.log',
    'monolog.level' => $app['log.level'],
    'monolog.name' => 'application'
]);

$app->register(new ConfigServiceProvider(__DIR__.'/../resources/config/general.php'));

// load services
$servicesLoader = new ServicesLoader($app);
$servicesLoader->bindServicesIntoContainer();

// load routes
$routesLoader = new RoutesLoader($app);
$routesLoader->bindRoutesToControllers();

$app->error(function (\Exception $e, $code) use ($app) {
    $app['monolog']->addError($e->getMessage());
    $app['monolog']->addError($e->getTraceAsString());

    return new JsonResponse([
        'statusCode' => $code,
        'message' => $e->getMessage(),
        'stacktrace' => $e->getTraceAsString()
    ]);
});

return $app;
