<?php

namespace Bangpound\LegacyPhp\Tests;

use Bangpound\LegacyPhp\Event\GetResponseForShutdownEvent;
use Bangpound\LegacyPhp\HttpKernel;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents as BaseKernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Bangpound\LegacyPhp\KernelEvents;

class HttpKernelTest extends \PHPUnit_Framework_TestCase
{
    public function testShutdownView()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver());
        $dispatcher->addListener(BaseKernelEvents::VIEW, function (GetResponseForControllerResultEvent $event) use (&$called) {
            $called = true;
        });
        $dispatcher->addListener(KernelEvents::SHUTDOWN, function (GetResponseForShutdownEvent $event) {
            $event->setResponse(new Response('Hello'));
        });

        ob_start();
        $kernel->shutdown(new Request());
        $this->assertNull($called);
        $this->assertEquals('Hello', ob_get_clean());
    }

    public function testShutdownResponse()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver());
        $dispatcher->addListener(BaseKernelEvents::RESPONSE, function (FilterResponseEvent $event) use (&$called, &$capturedKernel, &$capturedRequest, &$capturedResponse) {
            $called = true;
            $capturedKernel = $event->getKernel();
            $capturedRequest = $event->getRequest();
            $capturedResponse = $event->getResponse();
        });
        $dispatcher->addListener(KernelEvents::SHUTDOWN, function (GetResponseForShutdownEvent $event) use (&$response) {
            $event->setResponse($response = new Response());
        });

        $kernel->shutdown($request = new Request());
        $this->assertTrue($called);
        $this->assertEquals($kernel, $capturedKernel);
        $this->assertEquals($request, $capturedRequest);
        $this->assertEquals($response, $capturedResponse);
    }

    public function testShutdownFinishRequest()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver());
        $dispatcher->addListener(BaseKernelEvents::FINISH_REQUEST, function (FinishRequestEvent $event) use (&$called, &$capturedKernel, &$capturedRequest) {
            $called = true;
            $capturedKernel = $event->getKernel();
            $capturedRequest = $event->getRequest();
        });
        $dispatcher->addListener(KernelEvents::SHUTDOWN, function (GetResponseForShutdownEvent $event) {
            $event->setResponse(new Response('Hello'));
        });

        ob_start();
        $kernel->shutdown($request = new Request());
        $this->assertTrue($called);
        $this->assertEquals($kernel, $capturedKernel);
        $this->assertEquals($request, $capturedRequest);
        $this->assertEquals('Hello', ob_get_clean());
    }

    public function testShutdownTerminate()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver());
        $dispatcher->addListener(BaseKernelEvents::TERMINATE, function (PostResponseEvent $event) use (&$called, &$capturedKernel, &$capturedRequest, &$capturedResponse) {
            $called = true;
            $capturedKernel = $event->getKernel();
            $capturedRequest = $event->getRequest();
            $capturedResponse = $event->getResponse();
        });
        $dispatcher->addListener(KernelEvents::SHUTDOWN, function (GetResponseForShutdownEvent $event) use (&$response) {
            $event->setResponse($response = new Response('Hello'));
        });

        ob_start();
        $kernel->shutdown($request = new Request());
        $this->assertTrue($called);
        $this->assertEquals($kernel, $capturedKernel);
        $this->assertEquals($request, $capturedRequest);
        $this->assertEquals($response, $capturedResponse);
        $this->assertEquals('Hello', ob_get_clean());
    }

    public function testHandleWhenControllerExits()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver());
        $request = new Request();
        $response = $kernel->handle($request);
        $dispatcher->addListener(KernelEvents::SHUTDOWN, function (GetResponseForShutdownEvent $event) use (&$response) {
            $event->setResponse($response);
        });
        ob_start();
        $kernel->shutdown($request);

        $this->assertEquals('Hello', $response->getContent());
        $this->assertEquals('Hello', ob_get_clean());
    }

    public function testHandleControllerResponse()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver());
        $request = new Request();
        $response = $kernel->handle($request);
        $dispatcher->addListener(KernelEvents::SHUTDOWN, function (GetResponseForShutdownEvent $event) use (&$response) {
            $event->setResponse($response = new Response('Go away'));
        });
        $this->assertEquals('Hello', $response->getContent());

        ob_start();
        $kernel->shutdown($request);
        $this->assertEquals('Go away', $response->getContent());
        $this->assertEquals('Go away', ob_get_clean());
    }

    public function testSupressShutdown()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver());
        $request = new Request();
        $response = $kernel->handle($request);
        $dispatcher->addListener(KernelEvents::SHUTDOWN, function (GetResponseForShutdownEvent $event) use (&$response) {
            $event->setResponse($response = new Response('Go away'));
        });
        $this->assertEquals('Hello', $response->getContent());

        $kernel->supressShutdown();
        ob_start();
        $kernel->shutdown($request);
        $this->assertEquals('Hello', $response->getContent());
        $this->assertEmpty(ob_get_clean());
    }

    protected function getResolver($controller = null)
    {
        if (null === $controller) {
            $controller = function () { return new Response('Hello'); };
        }

        $resolver = $this->getMock('Symfony\\Component\\HttpKernel\\Controller\\ControllerResolverInterface');
        $resolver->expects($this->any())
            ->method('getController')
            ->will($this->returnValue($controller));
        $resolver->expects($this->any())
            ->method('getArguments')
            ->will($this->returnValue(array()));

        return $resolver;
    }
}
