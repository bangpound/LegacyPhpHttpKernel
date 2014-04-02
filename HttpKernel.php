<?php

namespace Bangpound\LegacyPhp;

use Bangpound\LegacyPhp\Event\GetResponseForShutdownEvent;
use Symfony\Component\HttpKernel\HttpKernel as BaseHttpKernel;
use Bangpound\LegacyPhp\HttpKernel\ShutdownableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents as BaseKernelEvents;

/**
 * Adds a shutdown function that captures the response and completes handling it.
 *
 * @author Benjamin Doherty <bjd@bangpound.org>
 */
class HttpKernel extends BaseHttpKernel implements ShutdownableInterface
{
    private $shutdown = true;

    public function supressShutdown()
    {
        $this->shutdown = false;
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(Request $request, $type = HttpKernelInterface::MASTER_REQUEST)
    {
        if ($this->shutdown) {
            // shutdown
            $event = new GetResponseForShutdownEvent($this, $request, $type, null);
            $this->dispatcher->dispatch(KernelEvents::SHUTDOWN, $event);

            if ($event->hasResponse()) {
                $response = $event->getResponse();
            } else {
                $msg = sprintf('The shutdown event must find a response.');
                throw new \LogicException($msg);
            }

            $response = $this->filterShutdownResponse($response, $request, $type);
            $response->send();
            $this->terminate($request, $response);
        }
    }

    /**
     * Filters a response object.
     *
     * @param Response $response A Response instance
     * @param Request  $request  An error message in case the response is not a Response object
     * @param integer  $type     The type of the request (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     *
     * @return Response The filtered Response instance
     *
     * @throws \RuntimeException if the passed object is not a Response instance
     */
    private function filterShutdownResponse(Response $response, Request $request, $type)
    {
        $event = new FilterResponseEvent($this, $request, $type, $response);

        $this->dispatcher->dispatch(BaseKernelEvents::RESPONSE, $event);

        $this->finishShutdownRequest($request, $type);

        return $event->getResponse();
    }

    /**
     * Publishes the finish request event, then pop the request from the stack.
     *
     * Note that the order of the operations is important here, otherwise
     * operations such as {@link RequestStack::getParentRequest()} can lead to
     * weird results.
     *
     * @param Request $request
     * @param int     $type
     */
    private function finishShutdownRequest(Request $request, $type)
    {
        $this->dispatcher->dispatch(BaseKernelEvents::FINISH_REQUEST, new FinishRequestEvent($this, $request, $type));
        $this->requestStack->pop();
    }
}
