<?php
namespace ImHere\Routes\V1;

use Symfony\Component\HttpFoundation\JsonResponse ;
use Silex\Application as Application;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class Badger
{
    public function checkin(Application $app)
    {
            $user =  $app['app.user'];
            if( !$user ){
              $app->abort(403);
            }

            $midnight = new \DateTime();
            $midnight->setTime(0,0);

            $em = $app['orm.em'];
            $res = $em->getRepository('\ImHere\Entities\Timetable')->createQueryBuilder('t')
                    ->select('count(t.id)')
                    ->andWhere('t.checkin > :current')
                    ->setParameter('current', $midnight)
                    ->getQuery()
                    ->execute();

            if( $res[0][1] == 0 ){
              $tt = new \ImHere\Entities\Timetable();
              $tt->setUser($user);
              $tt->checkIn();

              $em->persist($tt);
              $em->flush();
              return new JsonResponse([
                'message' => 'OK',
                'datetime' => $tt->getCheckIn()
              ], $status=201);
            } else {
              return new JsonResponse([
                'message' => 'KO',
                'errors' => 'You already made a checkin'
              ], $status=400);
            }
    }


}
