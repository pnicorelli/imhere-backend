<?php
namespace ImHere\Routes\V1;

use Symfony\Component\HttpFoundation\JsonResponse ;
use Silex\Application as Application;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class Badger
{
    /*
     * Create a checkIn time for registered user.
     * return
     * - HTTP 201 if ok
     * - HTTP 400 if a record today users already has a checkIn time.
     */
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
                'checkin' => $tt->getCheckIn()
              ], $status=201);
            } else {
              return new JsonResponse([
                'message' => 'KO',
                'errors' => 'You already made a checkin'
              ], $status=400);
            }
    }

    /*
     * Create a checkOut time for registered user.
     * return
     * - HTTP 201 if ok
     * - HTTP 400 if user has not checkIn
     */
    public function checkout(Application $app)
    {
            $user =  $app['app.user'];
            if( !$user ){
              $app->abort(403);
            }

            $midnight = new \DateTime();
            $midnight->setTime(0,0);

            $em = $app['orm.em'];
            $res = $em->getRepository('\ImHere\Entities\Timetable')->createQueryBuilder('t')
                    ->select('t.id')
                    ->andWhere('t.checkin > :current')
                    ->andWhere('t.checkout IS NULL')
                    ->andWhere('t.user = :userId')
                    ->setParameter('current', $midnight)
                    ->setParameter('userId', $user->getId())
                    ->getQuery()
                    ->execute();
            if( count($res) == 1 ){
              $tt = $em->find('\ImHere\Entities\Timetable', $res[0]['id']);
              $tt->checkOut();

              $em->persist($tt);
              $em->flush();
              return new JsonResponse([
                'message' => 'OK',
                'checkin' => $tt->getCheckIn(),
                'checkout' => $tt->getCheckOut()
              ], $status=201);
            } else {
              return new JsonResponse([
                'message' => 'KO',
                'errors' => 'You never checkin'
              ], $status=400);
            }
    }


        /*
         * Check the current user status
         */
        public function status(Application $app)
        {
                $user =  $app['app.user'];
                if( !$user ){
                  $app->abort(403);
                }

                $midnight = new \DateTime();
                $midnight->setTime(0,0);

                $em = $app['orm.em'];
                $res = $em->getRepository('\ImHere\Entities\Timetable')->createQueryBuilder('t')
                        ->select('t.id')
                        ->andWhere('t.checkin > :current')
                        ->andWhere('t.user = :userId')
                        ->setParameter('current', $midnight)
                        ->setParameter('userId', $user->getId())
                        ->orderBy('t.id', 'DESC')
                        ->setMaxResults(1)
                        ->getQuery()
                        ->execute();
                if( count($res) == 0 ){
                  return new JsonResponse([
                    'status' => 'out'
                  ], $status=200);
                } else {
                  $tt = $em->find('\ImHere\Entities\Timetable', $res[ 0 ]['id']);

                  $status = [
                    'status' => 'in',
                    'checkIn' => $tt->getCheckIn()
                  ];
                  if( $tt->getCheckOut() !== null){
                    $status['status'] = 'out';
                  }
                  return new JsonResponse($status, $status=200);
                }
        }
}
