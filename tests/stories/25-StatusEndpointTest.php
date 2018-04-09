<?php

use Silex\WebTestCase;

class StatusEndpointsTest extends WebTestCase
{
    private $em;
    private $session;
    private $sessionOther;
    private $sessionThird;

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

        $userOther = new ImHere\Entities\User();
        $userOther->setEmail('other@domain.org');
        $em->persist($userOther);
        $sessionOther = new ImHere\Entities\Session($userOther);
        $em->persist($sessionOther);

        $userThree = new ImHere\Entities\User();
        $userThree->setEmail('again@domain.org');
        $em->persist($userThree);
        $sessionThird = new ImHere\Entities\Session($userThree);
        $em->persist($sessionThird);


        $tt = new ImHere\Entities\Timetable();
        $tt->setUser($user);
        $tt->checkIn();
        $em->persist($tt);

        $tt2 = new ImHere\Entities\Timetable();
        $tt2->setUser($userOther);
        $tt2->checkIn();
        $tt2->checkOut();
        $em->persist($tt2);


        $em->flush();
        $this->user = $user;
        $this->session = $session;
        $this->sessionOther = $sessionOther;
        $this->sessionThird = $sessionThird;

        return $app;
    }

    public function testStatusDeniedForUnregistered()
    {
        $client = static::createClient();
        $client->request(
          'GET',
          '/v1/status',
          [],
          [],
          ['HTTP_X-TOKEN' => 'notareallygoodone']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testStatusShouldBeInIfOnlyCheckin()
    {
      $client = static::createClient();
      $client->request(
        'GET',
        '/v1/status',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->session->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(200, $response->getStatusCode());
      $content = json_decode($response->getContent(), true);
      $this->assertArrayHasKey('status', $content);
      $this->assertArrayHasKey('checkIn', $content);
      $this->assertEquals( 'in', $content['status'] );
    }

    public function testStatusShouldBeOutOnCheckout()
    {
      $client = static::createClient();
      $client->request(
        'GET',
        '/v1/status',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->sessionOther->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(200, $response->getStatusCode());
      $content = json_decode($response->getContent(), true);
      $this->assertArrayHasKey('status', $content);
      $this->assertArrayHasKey('checkIn', $content);
      $this->assertEquals( 'out', $content['status'] );
    }

    public function testStatusShouldBeOutIfNoCheckin()
    {
      $client = static::createClient();
      $client->request(
        'GET',
        '/v1/status',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->sessionThird->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(200, $response->getStatusCode());
      $content = json_decode($response->getContent(), true);
      $this->assertArrayHasKey('status', $content);
      $this->assertArrayNotHasKey('checkIn', $content);
      $this->assertEquals( 'out', $content['status'] );
    }

}
