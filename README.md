Legacy PHP HttpKernel
=====================

[![Build Status](https://travis-ci.org/bangpound/LegacyPhpHttpKernel.svg?branch=master)](https://travis-ci.org/bangpound/LegacyPhpHttpKernel)

Controllers in legacy PHP applications don't return Symfony Response objects, and they might return nothing or even exit. These low-level Symfony components provide a simple bridge to legacy PHP applications.

Usage
-----

Use the `HttpKernel` class and add the `ShutdownListener` and `OutputBufferListener`
subscribers to the `EventDispatcher`.


Working Example #1
------------------

This component is used in [Drufony](http://drufony.github.io/) to run Drupal 7 in the
Symfony2 framework.


Working Example #2
------------------

* Download and expand a recent version of [WordPress](http://wordpress.org).
* In the WordPress directory, create `composer.json`:
````json
{
	"require": {
		"bangpound/legacy-php-http-kernel": "1.0.*"
	}
}
````
* Run `composer install`.
* Replace WordPress's `index.php` front controller:
````php
<?php
require "vendor/autoload.php";

use Bangpound\LegacyPhp\EventListener\OutputBufferListener;
use Bangpound\LegacyPhp\EventListener\ShutdownListener;
use Bangpound\LegacyPhp\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

// Use a request attribute to identify requests which are legacy.
$matcher = new RequestMatcher();
$matcher->matchAttribute('_legacy', 'true');

$dispatcher = new EventDispatcher();

// Event listeners make Symfony reponses from output buffer and
// register a shutdown handler.
$dispatcher->addSubscriber(new OutputBufferListener($matcher));
$dispatcher->addSubscriber(new ShutdownListener($matcher));

// Add a listener that modifies every request to have a controller
// that imitates Wordpress index.php.
$dispatcher->addListener(KernelEvents::REQUEST,

    function (GetResponseEvent $event) {
        $request = $event->getRequest();
        $request->attributes->add(array(
            '_legacy' => 'true',
            '_controller' => function () {
                define('WP_USE_THEMES', true);
                require( dirname( __FILE__ ) . '/wp-blog-header.php' );
            },
        ));
    }

);

// This HttpKernel has a shutdown method which completes the kernel
// response cyle when a controller exits or dies.
$kernel = new HttpKernel($dispatcher, new ControllerResolver());

// Symfony front controller code
call_user_func(function () use ($kernel) {
    $request = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
});
````
* Run `php -S localhost:8000`
