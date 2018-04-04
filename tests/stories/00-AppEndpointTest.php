<?php

use Silex\WebTestCase;

class AppMainEndpointsTest extends WebTestCase
{
    public function createApplication()
    {
        $app = ImHere\App::App();
        return $app;
    }

    public function testBasePath()
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $remote = json_decode($response->getContent(), true);
        $local = json_decode(file_get_contents(__DIR__.'/../../composer.json'), $assoc=true);
        foreach($remote as $key => $value){
            $this->assertSame($local[$key], $value);
        }
    }

    public function test404OnError()
    {
        $client = static::createClient();
        $client->request('GET', '/idonotexist');
        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('404', $content['status']);
        $this->assertEquals('resource not found.', $content['message']);
    }
}
