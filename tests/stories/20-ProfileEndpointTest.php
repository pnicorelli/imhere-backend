<?php

use Silex\WebTestCase;

class ProfileEndpointsTest extends WebTestCase
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

    public function testProfileDeniedForUnregistered()
    {
        $client = static::createClient();
        $client->request(
          'GET',
          '/v1/profile');
        $response = $client->getResponse();

        $this->assertEquals(403, $response->getStatusCode());
        $client->request(
          'GET',
          '/v1/profile',
          [],
          [],
          ['HTTP_X-TOKEN' => 'notareallygoodone']);
        $response = $client->getResponse();

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testProfileGetUserData()
    {
      $client = static::createClient();
      $client->request(
        'GET',
        '/v1/profile',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->session->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(200, $response->getStatusCode());
      $content = json_decode($response->getContent(), true);
      $this->assertArrayHasKey('id', $content);
      $this->assertArrayHasKey('email', $content);
      $this->assertArrayHasKey('domain', $content);
      $this->assertEquals($content['id'], $this->user->getId());
      $this->assertEquals($content['email'], $this->user->getEmail());
      $this->assertEquals($content['domain'], $this->user->GetDomain());
    }
}
