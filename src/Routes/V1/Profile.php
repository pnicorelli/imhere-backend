<?php
namespace ImHere\Routes\V1;

use Symfony\Component\HttpFoundation\JsonResponse ;
use Silex\Application as Application;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class Profile
{
    public function getProfile(Application $app)
    {
            $user =  $app['app.user'];
            if( !$user ){
              $app->abort(403);
            }
            return new JsonResponse([
              'id' => $user->getId(),
              'email' => $user->getEmail(),
              'domain' => $user->getDomain()
            ], $status=200);
    }

}
