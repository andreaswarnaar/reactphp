<?php
/**
 * User: Andreas Warnaar
 * Date: 26-3-18
 * Time: 15:12
 */

namespace App\Tests\Kernel;


use App\Kernel;
use PHPUnit\Framework\TestCase;

class AppKernelTest extends TestCase
{
    public function testCustomKernelBoot() {
        $kernel = new Kernel('test',false);

        $kernel->boot();


        $container = $kernel->getContainer();

        $this->assertTrue($container->getParameter('kernel.secret'));

    }
}