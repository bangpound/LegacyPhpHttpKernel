<?php
namespace Bangpound\LegacyPhp\EventListener;

use Bangpound\LegacyPhp\Event\GetResponseForShutdownEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents as BaseKernelEvents;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Bangpound\LegacyPhp\KernelEvents;

/**
 * Class OutputBufferListener
 * @package Bangpound\LegacyPhp\EventListener
 */
class OutputBufferListener implements EventSubscriberInterface
{
    /**
     * @var RequestMatcherInterface Matches Drupal routes.
     */
    private $matcher;

    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    private $response;

    /**
     * @var \SplObjectStorage
     */
    private $buffers;

    /**
     * @param RequestMatcherInterface                    $matcher
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    public function __construct(RequestMatcherInterface $matcher = null, Response $response = null)
    {
        $this->matcher = $matcher;
        $this->response = $response;
        $this->buffers = new \SplObjectStorage();
    }

    /**
     * Request event sets up output buffering.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        if (null === $this->matcher || $this->matcher->matches($request)) {
            ob_start();
            $this->buffers->attach($request);
        }
    }

    /**
     * @param GetResponseForShutdownEvent $event
     */
    public function onKernelShutdown(GetResponseForShutdownEvent $event)
    {
        $request = $event->getRequest();
        if ($this->buffers->contains($request)) {
            $response = (null === $this->response) ? new Response() : $this->response;
            $result = (string) ob_get_clean();
            if (false !== $result) {
                $response->setContent($result);
            }
            $event->setResponse($response);
            $this->buffers->detach($request);
        }
    }

    /**
     * Get responses from output buffer.
     *
     * @param GetResponseForControllerResultEvent $event The event to handle
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        if ($this->buffers->contains($request)) {
            $response = (null === $this->response) ? new Response() : $this->response;
            $result = (string) ob_get_clean();
            if (false !== $result) {
                $response->setContent($result);
            }
            $event->setResponse($response);
            $this->buffers->detach($request);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            BaseKernelEvents::CONTROLLER => array('onKernelController'),
            BaseKernelEvents::VIEW => array('onKernelView'),
            KernelEvents::SHUTDOWN => array('onKernelShutdown'),
        );
    }
}
