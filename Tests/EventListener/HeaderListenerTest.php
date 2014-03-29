<?php

namespace Bangpound\LegacyPhp\Tests\EventListener;

use Bangpound\LegacyPhp\EventListener\HeaderListener;
use Bangpound\LegacyPhp\Tests\TestClient;
use Bangpound\LegacyPhp\Tests\TestHttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernel;

class HeaderListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testStatus()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new HeaderListener(new RequestMatcher()));

        $kernel = new TestHttpKernel($dispatcher);

        $client = new TestClient($kernel);
        $client->insulate();
        $client->request('GET', '/', array(
            'status' => 201,
        ));
        $response = $client->getResponse();

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testRedirectByHeader()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new HeaderListener(new RequestMatcher()));

        $kernel = new TestHttpKernel($dispatcher);

        $client = new TestClient($kernel);
        $client->insulate();
        $client->request('GET', '/', array(
            'header' => 'Location: /redirect',
        ));
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirection());
    }
}
