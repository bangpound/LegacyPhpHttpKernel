<?php
namespace Bangpound\LegacyPhp\Tests;

use Symfony\Component\HttpKernel\Client;

class TestClient extends Client
{
    protected function getScript($request)
    {
        $script = parent::getScript($request);

        $autoload = file_exists(__DIR__.'/../vendor/autoload.php')
            ? __DIR__.'/../vendor/autoload.php'
            : __DIR__.'/../../../../../../vendor/autoload.php'
        ;

        $script = preg_replace('/(\->register\(\);)/', "$0\nrequire_once '$autoload';\n", $script);

        return $script;
    }
}
