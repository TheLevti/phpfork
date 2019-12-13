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

use UnexpectedValueException;

/**
 * Common implementation of the signal related EventDispatcherInterface interface.
 */
trait EventDispatcherTrait
{
    /**
     * Holds previous signal handlers, which will be restored when a signal
     * handler is removed.
     *
     * @var array $prevSigHandlers
     */
    private $prevSigHandlers = [];

    /**
     * {@inheritDoc}
     */
    public function dispatchSignal(int $signo, $signinfo): SignalEvent
    {
        $event = new SignalEvent($signo, $signinfo);

        $this->dispatch($event, $event->getName());

        return $event;
    }

    /**
     * {@inheritDoc}
     */
    public function addSignalListener(
        int $signo,
        callable $listener,
        int $priority = 0
    ): void {
        // 1. Preserve previous signal handler.
        /** @var int|string|false $prevSigHandler */
        $prevSigHandler = pcntl_signal_get_handler($signo);
        if ($prevSigHandler === false) {
            $error = pcntl_get_last_error();
            if ($error === 0) {
                $error = PCNTL_EINVAL;
            }
            $strerror = pcntl_strerror($error);

            throw new UnexpectedValueException(sprintf(
                'Could not get currently installed signal handler for signal ' .
                '%d. %d: %s',
                $signo,
                $error,
                $strerror
            ));
        }
        $this->prevSigHandlers[$signo] = $prevSigHandler;

        // 2. Install our new signal handler.
        $newSigHandler = [$this, 'dispatchSignal'];
        if (is_callable($prevSigHandler)) {
            $newSigHandler = function (
                int $signo,
                $signinfo
            ) use (&$prevSigHandler) {
                $prevSigHandler($signo, $signinfo);
                call_user_func([$this, 'dispatchSignal'], $signo, $signinfo);
            };
        }
        if (!pcntl_signal($signo, $newSigHandler)) {
            $error = pcntl_get_last_error();
            if ($error === 0) {
                $error = PCNTL_EINVAL;
            }
            $strerror = pcntl_strerror($error);

            throw new UnexpectedValueException(sprintf(
                'Could not install signal handler for signal %d. %d: %s',
                $signo,
                $error,
                $strerror
            ));
        }

        // 3. Add listener to event.
        $this->addListener(
            SignalEvent::getEventName($signo),
            $listener,
            $priority
        );
    }

    /**
     * {@inheritDoc}
     */
    public function removeSignalListener(int $signo, callable $listener): void
    {
        $this->removeListener(SignalEvent::getEventName($signo), $listener);
    }
}
