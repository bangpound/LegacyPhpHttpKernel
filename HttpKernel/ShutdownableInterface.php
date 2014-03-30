<?php
namespace Bangpound\LegacyPhp\HttpKernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Interface ShutdownableInterface
 * @package Bangpound\Bundle\DrupalBundle\HttpKernel
 */
interface ShutdownableInterface
{
    /**
     * @return void
     */
    public function supressShutdown();

    /**
     * Completes a request/response cycle that has been interrupted with PHP shutdown.
     *
     * Should be called from a shutdown method.
     *
     * @param Request $request A Request instance
     * @param integer $type    The type of the request (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     *
     * @api
     * @return void
     */
    public function shutdown(Request $request, $type = HttpKernelInterface::MASTER_REQUEST);
}
