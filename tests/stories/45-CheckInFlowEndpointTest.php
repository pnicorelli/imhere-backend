<?php

use Silex\WebTestCase;

class CheckInFlowEndpointsTest extends WebTestCase
{
    private $em;
    private $session;
    private $user;

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

        $tt = new ImHere\Entities\Timetable();
        $tt->setUser($user);
        $tt->checkIn();
        $em->persist($tt);

        $em->flush();
        $this->user = $user;
        $this->session = $session;


        return $app;
    }

    public function testIShouldCheckInAfterACheckOutInTheSameDay()
    {
      $client = static::createClient();
      $client->request(
        'POST',
        '/v1/checkout',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->session->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(201, $response->getStatusCode());

      $client->request(
        'POST',
        '/v1/checkin',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->session->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(201, $response->getStatusCode());

    }

}
