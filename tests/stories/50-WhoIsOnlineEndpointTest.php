<?php

use Silex\WebTestCase;

class WhoIsOnlineEndpointsTest extends WebTestCase
{
    private $em;
    private $session;
    private $sessionOffline;
    private $user;
    private $userSameDomain;
    private $userDifferentDomain;


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

        $userSameDomainOnline = new ImHere\Entities\User();
        $userSameDomainOnline->setEmail('imonline@domain.org');
        $em->persist($userSameDomainOnline);

        $userSameDomainOffline = new ImHere\Entities\User();
        $userSameDomainOffline->setEmail('imoffline@domain.org');
        $em->persist($userSameDomainOffline);

        $sessionOffline = new ImHere\Entities\Session($userSameDomainOffline);
        $em->persist($sessionOffline);

        $userDifferentDomain = new ImHere\Entities\User();
        $userDifferentDomain->setEmail('test_4@org.org');
        $em->persist($userDifferentDomain);

        $tt = new ImHere\Entities\Timetable();
        $tt->setUser($user);
        $tt->checkIn();
        $em->persist($tt);

        $tt2 = new ImHere\Entities\Timetable();
        $tt2->setUser($userSameDomainOnline);
        $tt2->checkIn();
        $em->persist($tt2);

        $tt3 = new ImHere\Entities\Timetable();
        $tt3->setUser($userSameDomainOffline);
        $tt3->checkIn();
        $tt3->checkOut();
        $em->persist($tt3);

        $tt4 = new ImHere\Entities\Timetable();
        $tt4->setUser($userDifferentDomain);
        $tt4->checkIn();
        $em->persist($tt4);

        $em->flush();
        $this->user = $user;
        $this->session = $session;
        $this->sessionOffline = $sessionOffline;

        return $app;
    }

    public function testWhoDeniedForUnregistered()
    {
        $client = static::createClient();
        $client->request(
          'GET',
          '/v1/who',
          [],
          [],
          ['HTTP_X-TOKEN' => 'notareallygoodone']);
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testWhoShouldRetriveOnlineColleaguesOnly()
    {
      $client = static::createClient();
      $client->request(
        'GET',
        '/v1/who',
        [],
        [],
        ['HTTP_X-TOKEN' => $this->session->getToken()]);
      $response = $client->getResponse();
      $this->assertEquals(200, $response->getStatusCode());
      $content = json_decode($response->getContent(), true);
      $this->assertArrayHasKey('colleagues', $content);
      $this->assertTrue( in_array('test@domain.org', $content['colleagues']) );
      $this->assertTrue( in_array('imonline@domain.org', $content['colleagues']) );
      $this->assertFalse( in_array('offline@domain.org', $content['colleagues']) ); //made checkout
      $this->assertFalse( in_array('test_4@org.org', $content['colleagues']) );    // checked-in but different domain
    }

}
