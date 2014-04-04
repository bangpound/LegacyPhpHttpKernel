<?php
namespace Bangpound\LegacyPhp\EventListener;

use Bangpound\LegacyPhp\Event\GetResponseForShutdownEvent;
use Symfony\Component\DependencyInjection\ContainerAware;
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
class OutputBufferListener extends ContainerAware implements EventSubscriberInterface
{
    /**
     * @var RequestMatcherInterface Matches Drupal routes.
     */
    private $matcher;

    /**
     * @var \SplObjectStorage
     */
    private $buffers;

    /**
     * @param RequestMatcherInterface $matcher
     */
    public function __construct(RequestMatcherInterface $matcher = null)
    {
        $this->matcher = $matcher;
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
            $response = $this->container->get('response');
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
            $response = $this->container->get('response');
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
