<?php

/*
 * This file is part of the thelevti/phpfork package.
 *
 * (c) Petr Levtonov <petr@levtonov.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phpfork\EventDispatcher;

use Phpfork\ProcessManager;
use PHPUnit\Framework\TestCase;

class SignalEventDispatcherTest extends TestCase
{
    use SignalEventDispatcherTestTrait;

    /**
     * Process manager instance.
     *
     * @var \Phpfork\ProcessManager $processManager
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
        $this->processManager = new ProcessManager();
    }

    protected function tearDown(): void
    {
        $this->processManager->getEventDispatcher()->removeSignalHandlerWrappers();

        pcntl_async_signals($this->async);
        $this->errorReporting = error_reporting($this->errorReporting);

        parent::tearDown();
    }
}
