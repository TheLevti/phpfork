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
use Symfony\Component\EventDispatcher\EventDispatcher;

class WrappedEventDispatcherTest extends TestCase
{
    use SignalEventDispatcherTestTrait;

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

    public function testDelegate()
    {
        $eventDispatcher = $this->processManager->getEventDispatcher();
        $sigEventSubscriber = new SignalEventSubscriber();
        $eventName = SignalEvent::getEventName(SIGUSR1);

        $this->assertFalse($eventDispatcher->hasListeners($eventName));

        $eventDispatcher->addSubscriber($sigEventSubscriber);

        $this->assertTrue($eventDispatcher->hasListeners($eventName));

        $eventListeners = $eventDispatcher->getListeners($eventName);
        $this->assertIsArray($eventListeners);
        $this->assertNotEmpty($eventListeners);

        $eventListener = $eventListeners[0];

        $this->assertIsCallable($eventListeners[0]);
        $this->assertEquals(
            -128,
            $eventDispatcher->getListenerPriority($eventName, $eventListener)
        );

        $eventDispatcher->removeSubscriber($sigEventSubscriber);

        $this->assertFalse($eventDispatcher->hasListeners($eventName));
    }
}
