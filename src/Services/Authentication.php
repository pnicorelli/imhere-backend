<?php
namespace ImHere\Services;
use Pimple\Container as Container;
use Pimple\ServiceProviderInterface as ServiceProviderInterface;

class Authentication implements ServiceProviderInterface
{
    public function register(Container $app)
    {
      $app['app.user'] = function($app){
        $em = $app['orm.em'];
        $session = $em->getRepository('ImHere\Entities\Session')->findOneBy(['token' => $app['app.token']]);
        $user = ($session)? $session->getUser() : false;
        return $user;
      };
    }

}
