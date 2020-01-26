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

use ReflectionObject;
use Spork\SharedMemory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use UnexpectedValueException;

/**
 * Common tests for signal event dispatchers.
 */
trait SignalEventDispatcherTestTrait
{
    public function testSingleListenerOneSignal()
    {
        $signaled = false;

        $this->processManager->getEventDispatcher()->addSignalListener(
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

                $this->assertEquals(
                    SignalEvent::getEventName(SIGUSR1),
                    $eventName
                );
                $this->assertTrue($dispatcher instanceof EventDispatcher);
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

        $this->processManager->getEventDispatcher()->addSignalListener(
            SIGUSR1,
            function () use (&$sigFirst) {
                $sigFirst = true;
            }
        );

        $this->processManager->getEventDispatcher()->addSignalListener(
            SIGUSR1,
            function () use (&$sigSecond) {
                $sigSecond = true;
            }
        );

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

        $this->assertEquals(
            $origSigHandler,
            pcntl_signal_get_handler($testSig)
        );
        $this->assertEquals(0, $sigOrig);
        $this->assertEquals(0, $sigNew);

        posix_kill(posix_getpid(), $testSig);

        $this->assertEquals(1, $sigOrig);
        $this->assertEquals(0, $sigNew);

        $this->processManager->getEventDispatcher()->addSignalListener(
            $testSig,
            $newSigHandler
        );

        $currSigHandler = pcntl_signal_get_handler($testSig);
        $this->assertNotEquals($origSigHandler, $currSigHandler);
        $this->assertEquals(1, $sigOrig);
        $this->assertEquals(0, $sigNew);

        $this->processManager->fork(
            function (SharedMemory $sharedMem) use (&$testSig) {
                $sharedMem->signal($testSig);
            }
        );

        $this->processManager->wait();

        $this->assertEquals(2, $sigOrig);
        $this->assertEquals(1, $sigNew);

        posix_kill(posix_getpid(), $testSig);

        $this->assertEquals(3, $sigOrig);
        $this->assertEquals(2, $sigNew);

        $this->processManager->getEventDispatcher()->removeSignalListener(
            $testSig,
            $newSigHandler
        );

        $currSigHandler = pcntl_signal_get_handler($testSig);
        $this->assertNotEquals($origSigHandler, $currSigHandler);
        $this->assertEquals(3, $sigOrig);
        $this->assertEquals(2, $sigNew);

        posix_kill(posix_getpid(), $testSig);

        $this->assertEquals(4, $sigOrig);
        $this->assertEquals(2, $sigNew);

        $this->processManager->getEventDispatcher()->addSignalListener(
            $testSig,
            $newSigHandler
        );

        $currSigHandler = pcntl_signal_get_handler($testSig);
        $this->assertNotEquals($origSigHandler, $currSigHandler);
        $this->assertEquals(4, $sigOrig);
        $this->assertEquals(2, $sigNew);

        posix_kill(posix_getpid(), $testSig);

        $this->assertEquals(5, $sigOrig);
        $this->assertEquals(3, $sigNew);

        $this->processManager
            ->getEventDispatcher()
            ->removeSignalHandlerWrappers()
        ;

        $this->assertEquals(
            $origSigHandler,
            pcntl_signal_get_handler($testSig)
        );
        $this->assertEquals(5, $sigOrig);
        $this->assertEquals(3, $sigNew);

        posix_kill(posix_getpid(), $testSig);

        $this->assertEquals(6, $sigOrig);
        $this->assertEquals(3, $sigNew);
    }

    public function testSignalHandlerInstallFailure(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageRegExp(
            '/Could not get installed signal handler for signal 255./'
        );
        $this->expectExceptionCode(22);

        $this->processManager->getEventDispatcher()->addSignalListener(
            255,
            function () {
                // Do nothing.
            }
        );
    }

    public function testSignalHandlerInstallErrorHandling(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageRegExp(
            '/Could not install signal handler for signal 255./'
        );
        $this->expectExceptionCode(22);

        $reflection = new ReflectionObject(
            $this->processManager->getEventDispatcher()
        );
        $method = $reflection->getMethod('setSignalHandler');
        $method->setAccessible(true);

        $method->invokeArgs(
            $this->processManager->getEventDispatcher(),
            [255, function () {
                // Do nothing.
            }]
        );
    }
}
