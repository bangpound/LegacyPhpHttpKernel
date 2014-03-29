<?php
namespace Bangpound\LegacyPhp\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents as BaseKernelEvents;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Bangpound\LegacyPhp\KernelEvents;

class OutputBufferListener implements EventSubscriberInterface
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
     * Get responses from output buffer.
     *
     * @param GetResponseEvent $event The event to handle
     */
    public function onKernelPostController(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($this->buffers->contains($request)) {
            $event->setResponse($this->getResponse());
            $this->buffers->detach($request);
        }
    }

    /**
     * Captures a response from output buffers.
     *
     * Override this method in a subclass to set response status and headers.
     *
     * @return Response
     */
    protected function getResponse()
    {
        return new Response((string) ob_get_clean());
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            BaseKernelEvents::CONTROLLER => array('onKernelController'),
            BaseKernelEvents::VIEW => array('onKernelPostController'),
            KernelEvents::SHUTDOWN => array('onKernelPostController'),
        );
    }
}
