<?php
namespace ImHere\Routes\V1;

use Symfony\Component\HttpFoundation\JsonResponse ;
use Silex\Application as Application;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class Login
{
    public function login(Application $app, $email)
    {
            $entityManager = $app['orm.em'];
            $user = $entityManager->getRepository('\ImHere\Entities\User')->findOneBy(['email' => $email]);
            if( !isset($user) ){
              $user = new \ImHere\Entities\User();
              $user->setEmail($email);
              $entityManager->persist($user);
              $entityManager->flush();
            }

            $session = new \ImHere\Entities\Session($user);

            $entityManager->persist($session);
            $entityManager->flush();
            $mailer = $app['mail.transport'];

            $mailer->setTo($email);
            $mailer->setSubject('ImHere Login');
            $webappUrl = (getenv('webapp.url'))?getenv('webapp.url'):'http://imhere.localhost/token/';
            $loginUrl = $webappUrl.$session->getToken();
            $body = 'Click here <a href='.$loginUrl.'>'.$loginUrl.'</a>';
            $mailer->setBody($body);
            try{
              $mailer->send();
              return new JsonResponse([
                'message' => 'mail sent',
                'token' => $session->getToken()
              ], $status=201);
            } catch (\Exception $e){
              return new JsonResponse([
                'message' => 'mail not sent',
                'errors' => $e->getMessage()
              ], $status=503);
            }
    }

}
