<?php

namespace Bangpound\LegacyPhp\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ShutdownListener implements EventSubscriberInterface
{
    /**
     * @var RequestMatcherInterface Matches Drupal routes.
     */
    private $matcher;

    /**
     * @param RequestMatcherInterface $matcher
     */
    public function __construct(RequestMatcherInterface $matcher = null)
    {
        $this->matcher = $matcher;
    }

    /**
     * Prior to calling controller, set shutdown flag to trap exits form controllers.
     *
     * Also capture the request type, though I don't know if it's useful or relevant.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        if ($this->matcher->matches($request)) {
            register_shutdown_function(array($event->getKernel(), 'shutdown'), $request, $event->getRequestType());
        }
    }

    /**
     * All kernel events after KernelEvents::CONTROLLER should remind the shutdown
     * controller that it is not needed because the request is being handled correctly.
     *
     * @param KernelEvent $event
     */
    public function onKernelPostController(KernelEvent $event)
    {
        $request = $event->getRequest();
        if ($this->matcher->matches($request)) {
            $event->getKernel()->supressShutdown();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => array('onKernelController', -512),
            KernelEvents::EXCEPTION => array('onKernelPostController', 512),
            KernelEvents::VIEW => array('onKernelPostController', 512),
            KernelEvents::RESPONSE => array('onKernelPostController', 512),
        );
    }
}
