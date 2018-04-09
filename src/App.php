<?php

namespace ImHere;

use Symfony\Component\HttpFoundation\Request as Request;
use Symfony\Component\HttpFoundation\Response as Response;
use Symfony\Component\HttpFoundation\JsonResponse ;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection as RouteCollection;
use Silex\Application as Application;
use Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider as DoctrineOrmServiceProvider;
use Silex\Provider\DoctrineServiceProvider as DoctrineServiceProvider;

Class App
{

    public static function App()
    {
        $app = new Application();
        $app['debug'] = true;
        self::initDB($app);
        self::initRoutes($app);
        $app->register( new Services\MailTransport() );
        $app->register( new Services\Authentication() );
        return $app;
    }


    private static function initRoutes($app)
    {
        /*
         * Before each route
         */
        $app->before(function (Request $request, Application $app) {
          $token = $request->headers->get('X-TOKEN');
          $app['app.token'] =  $token;
        });

      	/*
      	 * After each route
      	 */
      	$app->after(function (Request $request, Response $response) {
      		//Enable CORS
      		$response->headers->set('Access-Control-Allow-Origin', '*');
      		$response->headers->set('Access-Control-Allow-Headers', 'X-TOKEN');
      	});

      	/*
      	 * Preflight call
      	 */
      	$app->options("{anything}", function () {
      	    return new \Symfony\Component\HttpFoundation\JsonResponse(null, 204);
      	})->assert("anything", ".*");

        /*
         * On Error
         */
        $app->error(function (\Exception $e, Request $request, $code) {
            switch ($code) {
                case 404:
                case 405:
                    $message = 'resource not found.';
                    $code = 404;
                    break;
                case 403:
                    $message = 'user only resource';
                    break;
                default:
                    $message = $e->getMessage(); //'Something went terribly wrong.';
            }

            return new JsonResponse([
                'status' => $code,
                'message'=> $message
              ], $status=$code);
        });

        /*
         * Load routes fron configuration
         */
        $app['routes'] = $app->extend('routes', function (RouteCollection $routes, Application $app) {
            $loader     = new YamlFileLoader(new FileLocator(__DIR__ . '/../config'));
            $collection = $loader->load('endpoints.yml');
            $routes->addCollection($collection);

            return $routes;
        });
    }

    /*
     * Initialize Doctrine
     */
    private static function initDB(Application $app){
        $dsp = [
          'db.options' => [
            'driver' => 'pdo_sqlite',
            'path' => 'db/database.db',
          ]
        ];
        if( getenv('APP_ENV') == 'testing' ){
          $dsp['db.options']['memory'] = true;
          unset($dsp['db.options']['path']);
        }
        $app->register(new DoctrineServiceProvider, $dsp);

        $app->register(new DoctrineOrmServiceProvider, array(
            'orm.proxies_dir' => 'db/proxies/',
            'orm.em.options' => array(
                'mappings' => array(
                    array(
                        'type' => 'annotation',
                        'namespace' => 'ImHere\Entities',
                        'path' => __DIR__.'/Entities/',
                    )
                ),
            ),
        ));
    }
}
