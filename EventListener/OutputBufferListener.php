<?php
namespace Bangpound\LegacyPhp\EventListener;

use Bangpound\LegacyPhp\Event\GetResponseForShutdownEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Bangpound\LegacyPhp\KernelEvents as BangpoundKernelEvents;;

class OutputBufferListener implements EventSubscriberInterface
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
     * Request event sets up output buffering after ending all open buffers.
     *
     * This supercedes all ob_ functions in Bootstrap.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        if ($this->matcher->matches($request)) {
            ob_start();
        }
    }

    /**
     * Replaces null responses with output buffer.
     *
     * @param GetResponseForControllerResultEvent $event The event to handle
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $response = $event->getControllerResult();
        $request = $event->getRequest();

        if (null === $response && $this->matcher->matches($request)) {
            $response = ob_get_clean();
            $event->setResponse(new Response((string) $response));
        }
    }

    /**
     * Shutdown handler for exceptions
     *
     * An access denied or not found exception might be thrown early, and those
     * should be handled the same way as if the controller exited.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        if ($this->matcher->matches($request)) {
            $response = ob_get_clean();
            $event->setResponse(new Response((string) $response));
        }
    }

    public function onKernelShutdown(GetResponseForShutdownEvent $event)
    {
        $request = $event->getRequest();
        if ($this->matcher->matches($request)) {
            $response = ob_get_clean();
            $event->setResponse(new Response((string) $response));
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => array('onKernelController', -512),
            KernelEvents::EXCEPTION => array('onKernelException', 512),
            KernelEvents::VIEW => array('onKernelView', 512),
            BangpoundKernelEvents::SHUTDOWN => array('onKernelShutdown', 512),
        );
    }
}
