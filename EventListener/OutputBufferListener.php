<?php
namespace Bangpound\LegacyPhp\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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
        if ($this->matcher->matches($request)) {
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
            $response = ob_get_clean();
            $event->setResponse(new Response((string) $response));
            $this->buffers->detach($request);
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => array('onKernelController'),
            KernelEvents::EXCEPTION => array('onKernelPostController'),
            KernelEvents::VIEW => array('onKernelPostController'),
            BangpoundKernelEvents::SHUTDOWN => array('onKernelPostController'),
        );
    }
}
