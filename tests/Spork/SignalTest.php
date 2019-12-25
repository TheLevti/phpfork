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

namespace Spork;

use PHPUnit\Framework\TestCase;
use Spork\EventDispatcher\EventDispatcher;
use Spork\EventDispatcher\SignalEvent;

class SignalTest extends TestCase
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
        $this->errorReporting = error_reporting(E_ALL & ~E_WARNING);
        $this->async = pcntl_async_signals();
        pcntl_async_signals(true);

        $this->processManager = new ProcessManager();
    }

    protected function tearDown(): void
    {
        $this->processManager->getEventDispatcher()->removeSignalHandlerWrappers();

        pcntl_async_signals($this->async);
        $this->errorReporting = error_reporting($this->errorReporting);
    }

    public function testSingleListenerOneSignal()
    {
        $signaled = false;

        $this->processManager->addListener(
            SIGUSR1,
            function (
                SignalEvent $event,
                string $eventName,
                EventDispatcher $dispatcher
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
                $this->assertEquals($this->processManager->getEventDispatcher(), $dispatcher);
            }
        );

        $this->processManager->fork(function (SharedMemory $sharedMem) {
            $sharedMem->signal(SIGUSR1);
        });

        $this->processManager->wait();

        $this->assertTrue($signaled);
    }

    public function testManyListenersOneSignal(): void
    {
        $sigFirst = false;
        $sigSecond = false;

        $this->processManager->addListener(SIGUSR1, function () use (&$sigFirst) {
            $sigFirst = true;
        });

        $this->processManager->addListener(SIGUSR1, function () use (&$sigSecond) {
            $sigSecond = true;
        });

        $this->processManager->fork(function (SharedMemory $sharedMem) {
            $sharedMem->signal(SIGUSR1);
        });

        $this->processManager->wait();

        $this->assertTrue($sigFirst);
        $this->assertTrue($sigSecond);
    }

    public function testPreviousSignalHandler(): void
    {
        $testSig = SIGUSR1;
        $sigOrig = 0;
        $sigNew = 0;

        $origSigHandler = function () use (&$sigOrig) {
            ++$sigOrig;
        };

        $newSigHandler = function () use (&$sigNew) {
            ++$sigNew;
        };

        pcntl_signal($testSig, $origSigHandler);

        $this->assertEquals($origSigHandler, pcntl_signal_get_handler($testSig));
        $this->assertEquals(0, $sigOrig);
        $this->assertEquals(0, $sigNew);

        posix_kill(posix_getpid(), $testSig);

        $this->assertEquals(1, $sigOrig);
        $this->assertEquals(0, $sigNew);

        $this->processManager->addListener($testSig, $newSigHandler);

        $currSigHandler = pcntl_signal_get_handler($testSig);
        $this->assertNotEquals($origSigHandler, $currSigHandler);
        $this->assertEquals(1, $sigOrig);
        $this->assertEquals(0, $sigNew);

        $this->processManager->fork(function (SharedMemory $sharedMem) use (&$testSig) {
            $sharedMem->signal($testSig);
        });

        $this->processManager->wait();

        $this->assertEquals(2, $sigOrig);
        $this->assertEquals(1, $sigNew);

        posix_kill(posix_getpid(), $testSig);

        $this->assertEquals(3, $sigOrig);
        $this->assertEquals(2, $sigNew);

        $this->processManager->getEventDispatcher()->removeSignalListener($testSig, $newSigHandler);

        $currSigHandler = pcntl_signal_get_handler($testSig);
        $this->assertNotEquals($origSigHandler, $currSigHandler);
        $this->assertEquals(3, $sigOrig);
        $this->assertEquals(2, $sigNew);

        posix_kill(posix_getpid(), $testSig);

        $this->assertEquals(4, $sigOrig);
        $this->assertEquals(2, $sigNew);

        $this->processManager->addListener($testSig, $newSigHandler);

        $currSigHandler = pcntl_signal_get_handler($testSig);
        $this->assertNotEquals($origSigHandler, $currSigHandler);
        $this->assertEquals(4, $sigOrig);
        $this->assertEquals(2, $sigNew);

        posix_kill(posix_getpid(), $testSig);

        $this->assertEquals(5, $sigOrig);
        $this->assertEquals(3, $sigNew);

        $this->processManager->getEventDispatcher()->removeSignalHandlerWrappers();

        $this->assertEquals($origSigHandler, pcntl_signal_get_handler($testSig));
        $this->assertEquals(5, $sigOrig);
        $this->assertEquals(3, $sigNew);

        posix_kill(posix_getpid(), $testSig);

        $this->assertEquals(6, $sigOrig);
        $this->assertEquals(3, $sigNew);
    }
}
