<?php

use Silex\WebTestCase;

class CheckOutEndpointsTest extends WebTestCase
{
    private $em;
    private $session;
    private $user;
    private $session_2;
    private $user_2;


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

        $user_2 = new ImHere\Entities\User();
        $user_2->setEmail('test_2@domain_2.org');
        $em->persist($user_2);
        $session_2 = new ImHere\Entities\Session($user_2);
        $em->persist($session_2);

        $tt = new ImHere\Entities\Timetable();
        $tt->setUser($user);
        $tt->checkIn();
        $em->persist($tt);

        $em->flush();
        $this->user = $user;
        $this->session = $session;
        $this->user_2 = $user_2;
        $this->session_2 = $session_2;

        return $app;
    }

    public function testCheckOutDeniedForUnregistered()
    {
        $client = static::createClient();
        $client->request(
          'POST',
          '/v1/checkout',
          [],
          [],
          ['HTTP_X-TOKEN' => 'notareallygoodone']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testCheckOutShouldCreateARecord()
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
      $content = json_decode($response->getContent(), true);
      $this->assertArrayHasKey('message', $content);
      $this->assertArrayHasKey('checkin', $content);
      $this->assertArrayHasKey('checkout', $content);
      $this->assertEquals($content['message'], 'OK');
      $now = new \DateTime('now');
      $checkout = new \DateTime($content['checkout']);
      $this->assertEquals($checkout, $now, '', 10);
      $data = $this->em->getRepository('ImHere\Entities\Timetable')->findOneBy(['user' => $this->user]);
      $this->assertEquals($content['checkout'], $data->getCheckOut());
    }

    public function testCheckOutShouldNotWorkWithoutCheckIn()
    {
      $client = static::createClient();
      $client->request(
        'POST',
        '/v1/checkout',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->session_2->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(400, $response->getStatusCode());
      $content = json_decode($response->getContent(), true);
      $this->assertEquals($content['message'], 'KO');
      $this->assertEquals($content['errors'], 'You never checkin');
    }

}
