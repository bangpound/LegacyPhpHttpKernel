<?php

namespace Bangpound\LegacyPhp\Tests\EventListener;

use Bangpound\LegacyPhp\EventListener\OutputBufferListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernel;

class OutputBufferListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testControllerReturnsNothing()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new OutputBufferListener(new RequestMatcher()));
        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () { echo 'Hello'; }));
        $request = new Request();
        $response = $kernel->handle($request);
        $this->assertEquals(new Response('Hello'), $response);
    }

    public function testControllerNoContents()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new OutputBufferListener(new RequestMatcher()));
        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () { }));
        $request = new Request();
        $response = $kernel->handle($request);
        $this->assertEquals(new Response(), $response);
    }

    /**
     * @param \Closure $controller
     *
     * @return \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface
     */
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
