<?php

namespace ImHere;

use Symfony\Component\HttpFoundation\Request ;
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
        $app->register( new Services\MailTransport() );
        self::registerDB($app);
        self::registerRoutes($app);
        return $app;
    }

    private static function registerRoutes($app)
    {

        $app->error(function (\Exception $e, Request $request, $code) {
            switch ($code) {
                case 404:
                    $message = 'resource not found.';
                    break;
                default:
                    $message = $e->getMessage(); //'Something went terribly wrong.';
            }

            return new JsonResponse([
                'status' => $code,
                'message'=> $message
                ]);
        });

        $app['routes'] = $app->extend('routes', function (RouteCollection $routes, Application $app) {
            $loader     = new YamlFileLoader(new FileLocator(__DIR__ . '/../config'));
            $collection = $loader->load('endpoints.yml');
            $routes->addCollection($collection);

            return $routes;
        });
    }

    private static function registerDB(Application $app){
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
