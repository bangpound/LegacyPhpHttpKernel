<?php
namespace Bangpound\LegacyPhp\Tests\EventListener;

use Bangpound\LegacyPhp\Event\GetResponseForShutdownEvent;
use Bangpound\LegacyPhp\EventListener\ShutdownListener;
use Bangpound\LegacyPhp\HttpKernel;
use Bangpound\LegacyPhp\KernelEvents as BangpoundKernelEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;

class ShutdownListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testControllerShutdown()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new ShutdownListener(new RequestMatcher()));
        $dispatcher->addListener(BangpoundKernelEvents::SHUTDOWN, function (GetResponseForShutdownEvent $event) use (&$called) {
            $called = true;
            $event->setResponse(new Response('Go away'));
        });

        $kernel = new HttpKernel($dispatcher, $this->getResolver());
        $request = new Request();

        $response = $kernel->handle($request);

        $this->assertEquals($response, new Response('Hello'));
        $this->assertNull($called);

        ob_start();
        $kernel->shutdown($request);
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
