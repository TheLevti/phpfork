<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork;

use PHPUnit\Framework\TestCase;
use Spork\ProcessManager;
use Spork\SharedMemory;

class SignalTest extends TestCase
{
    /** @var \Spork\ProcessManager $manager */
    private $manager;

    /** @var bool $async */
    private $async;

    protected function setUp(): void
    {
        $this->async = pcntl_async_signals();
        pcntl_async_signals(true);

        $this->manager = new ProcessManager();
    }

    protected function tearDown(): void
    {
        $this->manager = null;

        pcntl_async_signals($this->async);
    }

    public function testSignalParent()
    {
        $signaled = false;

        $this->manager->addListener(SIGUSR1, function () use (&$signaled) {
            $signaled = true;
        });

        $this->manager->fork(function (SharedMemory $sharedMem) {
            $sharedMem->signal(SIGUSR1);
        });

        $this->manager->wait();

        $this->assertTrue($signaled);
    }
}
