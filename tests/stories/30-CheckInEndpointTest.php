<?php

use Silex\WebTestCase;

class CheckInEndpointsTest extends WebTestCase
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
        $em->flush();
        $this->user = $user;
        $this->session = $session;

        return $app;
    }

    public function testCheckinDeniedForUnregistered()
    {
        $client = static::createClient();
        $client->request(
          'POST',
          '/v1/checkin',
          [],
          [],
          ['HTTP_X-TOKEN' => 'notareallygoodone']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testCheckInShouldCreateARecord()
    {
      $client = static::createClient();
      $client->request(
        'POST',
        '/v1/checkin',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->session->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(201, $response->getStatusCode());
      $content = json_decode($response->getContent(), true);
      $this->assertArrayHasKey('message', $content);
      $this->assertArrayHasKey('checkin', $content);
      $this->assertEquals($content['message'], 'OK');
      $now = new \DateTime('now');
      $checkin = new \DateTime($content['checkin']);
      $this->assertEquals($checkin, $now, '', 10);
      $data = $this->em->getRepository('ImHere\Entities\Timetable')->findOneBy(['user' => $this->user]);
      $this->assertEquals($content['checkin'], $data->getCheckIn());
    }

    /*
     * I can checkin only 1 time... before make another one I need to checkout
     */
    public function testCheckInShouldBeUniqueToday()
    {
      $client = static::createClient();
      $client->request(
        'POST',
        '/v1/checkin',
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
      $this->assertEquals(400, $response->getStatusCode());
    }

}
