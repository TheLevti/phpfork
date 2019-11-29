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
    /** @var \Spork\ProcessManager $manager */
    private $manager;

    /** @var bool $async */
    private $async;

    /** @var int $errorReporting */
    private $errorReporting;

    protected function setUp(): void
    {
        $this->errorReporting = error_reporting(E_ALL & ~E_WARNING);
        $this->async = pcntl_async_signals();
        pcntl_async_signals(true);

        $this->manager = new ProcessManager();
    }

    protected function tearDown(): void
    {
        $this->manager = null;

        pcntl_async_signals($this->async);
        $this->errorReporting = error_reporting($this->errorReporting);
    }

    public function testSignalParent()
    {
        $signaled = false;

        $this->manager->addListener(
            SIGUSR1,
            function (
                SignalEvent $event,
                string $eventName,
                EventDispatcher $dispatcher
            ) use (&$signaled) {
                $signaled = true;

                $this->assertEquals(SIGUSR1, $event->getSigno());

                $signoInfo = $event->getSigninfo();
                if (is_array($signoInfo)) {
                    $this->assertIsArray($signoInfo);
                    $this->assertArrayHasKey('signo', $signoInfo);
                    $this->assertEquals(SIGUSR1, $signoInfo['signo']);
                    $this->assertArrayHasKey('errno', $signoInfo);
                    $this->assertIsInt($signoInfo['errno']);
                    $this->assertArrayHasKey('code', $signoInfo);
                    $this->assertIsInt($signoInfo['code']);
                    $this->assertArrayHasKey('pid', $signoInfo);
                    $this->assertIsInt($signoInfo['pid']);
                    $this->assertArrayHasKey('uid', $signoInfo);
                    $this->assertIsInt($signoInfo['uid']);
                }

                $this->assertEquals(SignalEvent::getEventName(SIGUSR1), $eventName);
                $this->assertEquals($this->manager->getEventDispatcher(), $dispatcher);
            }
        );

        $this->manager->fork(function (SharedMemory $sharedMem) {
            $sharedMem->signal(SIGUSR1);
        });

        $this->manager->wait();

        $this->assertTrue($signaled);
    }
}
