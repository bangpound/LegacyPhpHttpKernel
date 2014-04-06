<?php

namespace Bangpound\LegacyPhp\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents as BaseKernelEvents;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class HeaderListener implements EventSubscriberInterface
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
     * Add response status and headers from legacy controllers.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        if (null === $this->matcher || $this->matcher->matches($request)) {

            $response = $event->getResponse();

            foreach (headers_list() as $header) {

                // Parse message headers.
                if (strpos($header, ':')) {
                    $parts = explode(':', $header, 2);
                    $key = trim($parts[0]);
                    $value = isset($parts[1]) ? trim($parts[1]) : '';

                    // Header set outside Symfony added to response.
                    $response->headers->set($key, $value);
                }
            }

            // If the Symfony response code is OK, dig deeper.
            if ($response->isOk()) {
                // In PHP 5.4.0, find status code using http_response_code function.
                if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
                    if (http_response_code() && 200 != http_response_code()) {
                        $response->setStatusCode(http_response_code());
                    }
                }

                // Lesser PHPs have to check for a location header set by legacy controller.
                else if ($response->headers->has('location')
                    && !$response->isRedirect($response->headers->get('location'))) {

                    // Fallback to default behavior of PHP.
                    $response->setStatusCode(302);
                }
            }
        }
    }

    /**
     * {@inheritDocs}
     */
    public static function getSubscribedEvents()
    {
        return array(
            BaseKernelEvents::RESPONSE => array('onKernelResponse'),
        );
    }
}
