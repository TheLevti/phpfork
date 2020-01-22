<?php

/*
 * This file is part of the thelevti/spork package.
 *
 * (c) Petr Levtonov <petr@levtonov.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spork\EventDispatcher;

use PHPUnit\Framework\TestCase;
use Spork\ProcessManager;
use Spork\SharedMemory;
use Symfony\Component\EventDispatcher\EventDispatcher;

class WrappedEventDispatcherTest extends TestCase
{
    /**
     * Process manager instance.
     *
     * @var \Spork\ProcessManager $processManager
     */
    private $processManager;

    /**
     * Holds the previous pcntl async signals value.
     *
     * @var bool $async
     */
    private $async;

    /**
     * Holds the previous error reporting configuration.
     *
     * @var int $errorReporting
     */
    private $errorReporting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->errorReporting = error_reporting(E_ALL & ~E_WARNING);
        $this->async = pcntl_async_signals();
        pcntl_async_signals(true);

        $dispatcher = new EventDispatcher();
        $wrapped = new WrappedEventDispatcher($dispatcher);
        $this->processManager = new ProcessManager($wrapped);
    }

    protected function tearDown(): void
    {
        $this->processManager->getEventDispatcher()->removeSignalHandlerWrappers();

        pcntl_async_signals($this->async);
        $this->errorReporting = error_reporting($this->errorReporting);

        parent::tearDown();
    }

    public function testSingleListenerOneSignal()
    {
        $signaled = false;

        $this->processManager->addListener(
            SIGUSR1,
            function (
                SignalEvent $event,
                string $eventName
            ) use (&$signaled) {
                $signaled = true;

                $this->assertEquals(SIGUSR1, $event->getSigno());

                $signinfo = $event->getSigninfo();
                if (is_array($signinfo)) {
                    $this->assertArrayHasKey('signo', $signinfo);
                    if ($signinfo['signo'] !== SIGUSR1) {
                        var_dump($signinfo);
                    }
                    $this->assertEquals(SIGUSR1, $signinfo['signo']);
                    $this->assertArrayHasKey('errno', $signinfo);
                    $this->assertIsInt($signinfo['errno']);
                    $this->assertArrayHasKey('code', $signinfo);
                    $this->assertIsInt($signinfo['code']);
                    $this->assertArrayHasKey('pid', $signinfo);
                    $this->assertIsInt($signinfo['pid']);
                    $this->assertArrayHasKey('uid', $signinfo);
                    $this->assertIsInt($signinfo['uid']);
                }

                $this->assertEquals(SignalEvent::getEventName(SIGUSR1), $eventName);
            }
        );

        $this->processManager->fork(function (SharedMemory $sharedMem) {
            $sharedMem->signal(SIGUSR1);
        });

        $this->processManager->wait();

        $this->assertTrue($signaled);
    }
}
