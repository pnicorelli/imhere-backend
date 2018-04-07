<?php

use Silex\WebTestCase;
use Doctrine\ORM\Query\Expr as Expr;
class ReportEndpointsTest extends WebTestCase
{
    private $em;
    private $session;
    private $user;
    private $fakePeople;

    public function createApplication()
    {
        $app = ImHere\App::App();
        $em = $app['orm.em'];
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = array(
          $em->getClassMetadata('ImHere\Entities\User'),
          $em->getClassMetadata('ImHere\Entities\Session'),
          $em->getClassMetadata('ImHere\Entities\Timetable'),
        );
        $tool->createSchema($classes);
        $this->em = $em;

        $user = new ImHere\Entities\User();
        $user->setEmail('test@domain.org');
        $em->persist($user);
        $session = new ImHere\Entities\Session($user);
        $em->persist($session);

        $this->fakePeople = 5;
        $this->generateFakeData($this->fakePeople, 'domain.org');
        $this->generateFakeData($this->fakePeople, 'other.org');


        $em->flush();
        $this->user = $user;
        $this->session = $session;

        return $app;
    }

    /*
     * create $howMany users in the domain $domain with some record in the Timetable
     *
     * for each user there is:
     *  - Jan   15th, from 09:00 to 13:00 + from 14:00 to 18:00
     *  - March 01st, from 09:00 to 12:00
     *  - March 15th, from 09:00 to 10:00
     *  - March 20th, from 09:00 to NULL
     *
     * So... 4 hour in March, 8 in January
     */
    public function generateFakeData($howMany, $domain){
      $em = $this->em;

      $year = date('Y');
      for($i=0; $i<$howMany; $i++){
        $tmpUser = new ImHere\Entities\User();
        $tmpUser->setEmail('test-'.$i.'@'.$domain);
        $em->persist($tmpUser);

        // March, 1st full checkIn/checkOut (3 hours)
        $ttTmp1 = new ImHere\Entities\Timetable();
        $ttTmp1->setUser($tmpUser);
        $ttTmp1->checkIn( $year.'-03-01 09:00:00' );
        $ttTmp1->checkOut( $year.'-03-01 12:00:00' );
        $em->persist($ttTmp1);

        // March, 15th with full checkIn/checkOut (1 hour)
        $ttTmp2 = new ImHere\Entities\Timetable();
        $ttTmp2->setUser($tmpUser);
        $ttTmp2->checkIn( $year.'-03-15 09:00:00' );
        $ttTmp2->checkOut( $year.'-03-15 10:00:00' );
        $em->persist($ttTmp2);

        // March, 20th just checkIn
        $ttTmp3 = new ImHere\Entities\Timetable();
        $ttTmp3->setUser($tmpUser);
        $ttTmp3->checkIn( $year.'-03-20 09:00:00' );
        $em->persist($ttTmp3);

        // January 15 with full checkIn/checkOut in 2 times (8 hour)
        $ttTmp4 = new ImHere\Entities\Timetable();
        $ttTmp4->setUser($tmpUser);
        $ttTmp4->checkIn( $year.'-01-15 09:00:00' );
        $ttTmp4->checkOut( $year.'-01-15 13:00:00' );
        $em->persist($ttTmp4);

        $ttTmp5 = new ImHere\Entities\Timetable();
        $ttTmp5->setUser($tmpUser);
        $ttTmp5->checkIn( $year.'-01-15 14:00:00' );
        $ttTmp5->checkOut( $year.'-01-15 18:00:00' );
        $em->persist($ttTmp5);
      }
    }

    public function testReportDeniedForUnregistered()
    {
        $client = static::createClient();
        $client->request(
          'GET',
          '/v1/report/montly/current',
          [],
          [],
          ['HTTP_X-TOKEN' => 'notareallygoodone']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testReportShouldGetTheCurrentMonthByDefault()
    {
      $client = static::createClient();
      $client->request(
        'GET',
        '/v1/report/montly/current',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->session->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(200, $response->getStatusCode());
      $content = json_decode($response->getContent(), true);
      $this->assertArrayHasKey('month', $content);
      $this->assertEquals( date('Y-m'), $content['month'] );

      $client = static::createClient();
      $client->request(
        'GET',
        '/v1/report/montly/justnoise',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->session->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(200, $response->getStatusCode());
      $content = json_decode($response->getContent(), true);
      $this->assertArrayHasKey('month', $content);
      $this->assertEquals( date('Y-m'), $content['month'] );
    }

    public function testReportShouldRetriveDataOnlyFromDomain()
    {
      $client = static::createClient();
      $client->request(
        'GET',
        '/v1/report/montly/'.date('Y').'-03',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->session->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(200, $response->getStatusCode());
      $content = json_decode($response->getContent(), true);
      $this->assertArrayHasKey('month', $content);
      $this->assertEquals( date('Y').'-03', $content['month'] );
      $this->assertArrayHasKey('report', $content);
      $this->assertEquals( $this->fakePeople , count($content['report']) );
      $acc = 0;
      foreach ($content['report'] as $key => $value) {
        $this->assertEquals( 2 , count($value) ); //On only 2 valid records
        foreach ($value as $k => $v) {
          $acc += (int) $v['hours'];
        }
      }
      $this->assertEquals( $this->fakePeople * 4 , $acc ); //all of them worked for 4 hours
    }

    public function testReportShouldRetriveDataPerMonth()
    {
      $client = static::createClient();
      $client->request(
        'GET',
        '/v1/report/montly/'.date('Y').'-01',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->session->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(200, $response->getStatusCode());
      $content = json_decode($response->getContent(), true);
      $this->assertArrayHasKey('month', $content);
      $this->assertEquals( date('Y').'-01', $content['month'] );
      $this->assertArrayHasKey('report', $content);
      $this->assertEquals( $this->fakePeople , count($content['report']) );
      $acc = 0;
      foreach ($content['report'] as $key => $value) {
        $this->assertEquals( 2 , count($value) ); //On January 2 valid records
        foreach ($value as $k => $v) {
          $acc += (int) $v['hours'];
        }
      }
      $this->assertEquals( $this->fakePeople * 8 , $acc ); //all of them worked for 8 hours
    }

}
