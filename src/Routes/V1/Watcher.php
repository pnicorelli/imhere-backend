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

}
