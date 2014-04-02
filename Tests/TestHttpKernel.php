<?php

namespace Bangpound\LegacyPhp\Tests;

use Bangpound\LegacyPhp\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class TestHttpKernel extends HttpKernel implements ControllerResolverInterface
{
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        parent::__construct($dispatcher, $this);
    }

    public function getController(Request $request)
    {
        return array($this, 'callController');
    }

    public function getArguments(Request $request, $controller)
    {
        return array(
            $request->get('content', ''),
            $request->get('status', 200),
            $request->get('header', null),
        );
    }

    public function callController($content = '', $status = 200, $header = null)
    {

        header(sprintf('HTTP/1.0 %s', $status));
        if ($header) {
            header($header, false);
        }

        return new Response($content);
    }
}
