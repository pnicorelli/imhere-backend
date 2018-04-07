<?php
namespace ImHere\Routes\V1;

use Symfony\Component\HttpFoundation\JsonResponse ;
use Silex\Application as Application;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Query\Expr as Expr;

class Watcher
{
    /*
     * return all user from the same domain who checkIn but not checkOut
     */
    public function whoIsOnline(Application $app)
    {
            $user =  $app['app.user'];
            if( !$user ){
              $app->abort(403);
            }

            $midnight = new \DateTime();
            $midnight->setTime(0,0);

            $em = $app['orm.em'];
            $res = $em->createQueryBuilder()
                    ->select('DISTINCT u.email')
                    ->from('ImHere\Entities\Timetable', 't')
                    ->innerJoin('t.user', 'u', Expr\Join::WITH, 'u.id = t.user')
                    ->andWhere('t.checkin > :current')
                    ->andWhere('t.checkout IS NULL')
                    ->andWhere('u.domain = :domain')
                    ->setParameter('current', $midnight)
                    ->setParameter('domain', $user->getDomain())
                    ->getQuery()
                    ->execute();
            $team = [];
            foreach ($res as $key => $value) {
              $team[] = $value['email'];
            }
            return new JsonResponse([
              'colleagues' => $team
            ], $status=200);
    }

    /*
     * Create a montly report by domain
     * Only full checkIn / checkOut are counted
     *
     *  - yearmonth: YYYY-MM, default current month
     */
    public function reportMontly(Application $app, $yearmonth){
            $user =  $app['app.user'];
            if( !$user ){
              $app->abort(403);
            }
            $yearmonth = preg_match('/^(20)\d{2}-(0?[1-9]|1[012])$/', $yearmonth) ? $yearmonth :  date('Y-m');

            $firstDayOfMonth = new \DateTime($yearmonth.'-01');
            $firstDayOfMonth->setTime(0,0,0);
            $lastDayOfMonth = new \DateTime($yearmonth.'-01');
            $lastDayOfMonth->modify('last day of this month');
            $lastDayOfMonth->setTime(23,59,59);

            $em = $app['orm.em'];
            $res = $em->createQueryBuilder()
                    ->select('u.email, t.id')
                    ->from('ImHere\Entities\Timetable', 't')
                    ->innerJoin('t.user', 'u', Expr\Join::WITH, 'u.id = t.user')
                    ->andWhere('t.checkin BETWEEN :in AND :out')
                    ->andWhere('t.checkout IS NOT NULL')
                    ->andWhere('u.domain = :domain')
                    ->setParameter('in', $firstDayOfMonth)
                    ->setParameter('out', $lastDayOfMonth)
                    ->setParameter('domain', $user->getDomain())
                    ->getQuery()
                    ->execute();
            $data = [];
            foreach ($res as $key => $value) {
              $tt = $em->find('\ImHere\Entities\Timetable', $value['id']);
              $data[$value['email']][] = [
                'checkin' => $tt->getCheckIn(),
                'checkout'=> $tt->getCheckOut(),
                'hours'   => $tt->totalTime()
              ];
            }

            return new JsonResponse([
              'month' => $yearmonth,
              'report' => $data
            ], $status=200);
    }
}
