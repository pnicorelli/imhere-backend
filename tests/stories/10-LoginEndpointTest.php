<?php

use Silex\WebTestCase;

class LoginEndpointsTest extends WebTestCase
{
    private $em;

    public function createApplication()
    {
        $app = ImHere\App::App();
        $em = $app['orm.em'];
        $smtpMock = $this->getMockBuilder('ImHere\Services\Mail\GmailSmtpWrapper')
                          ->getMock();
        $app['mail.transport'] = $smtpMock;
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = array(
          $em->getClassMetadata('ImHere\Entities\User'),
          $em->getClassMetadata('ImHere\Entities\Session'),
        );
        $tool->createSchema($classes);
        $this->em = $em;
        return $app;
    }

    public function testPerformLogin()
    {
        $client = static::createClient();
        $client->request('POST', '/v1/login/user@domain.it');
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $data = $this->em->getRepository('ImHere\Entities\User')->findBy(['username' => 'user']);
        $this->assertEquals(1, count($data));
        $this->assertEquals('user@domain.it', $data[0]->getEmail());
        $data = $this->em->getRepository('ImHere\Entities\Session')->findBy(['user' => $data[0]->getId()]);
        $this->assertEquals(1, count($data));
        $this->assertRegExp('/[0-9a-f]/', $data[0]->getToken());
    }

    public function testSecondLoginDoesNotMakeANewUser()
    {
        $client = static::createClient();
        $client->request('POST', '/v1/login/user@domain.it');
        $client->request('POST', '/v1/login/user@domain.it');
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $userdata = $this->em->getRepository('ImHere\Entities\User')->findBy(['username' => 'user']);
        $this->assertEquals(1, count($userdata));
        $sessiondata = $this->em->getRepository('ImHere\Entities\Session')->findBy(['user' => $userdata[0]->getId()]);
        $this->assertEquals(2, count($sessiondata));
    }
}
