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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Extends the core event dispatcher interface with signal handling
 * capabilities. Add and remove signal listeners or dispatch a signal directly.
 */
interface SignalEventDispatcherInterface extends EventDispatcherInterface
{
    /**
     * Signal handler that dispatches events.
     *
     * @param  int   $signo    The signal being handled.
     * @param  mixed $signinfo If operating systems supports siginfo_t
     *                         structures, this will be an array of signal
     *                         information dependent on the signal.
     * @return \Phpfork\EventDispatcher\SignalEvent Holds the signal information.
     */
    public function dispatchSignal(int $signo, $signinfo): SignalEvent;

    /**
     * Adds a signal listener that listens on the specified signal.
     *
     * @param  int      $signo    The signal number.
     * @param  callable $listener The listener.
     * @param  int      $priority The higher this value, the earlier an event
     *                            listener will be triggered in the chain
     *                            (defaults to 0)
     * @return void
     */
    public function addSignalListener(
        int $signo,
        callable $listener,
        int $priority = 0
    ): void;

    /**
     * Removes a signal listener from the specified signal.
     *
     * @param  int      $signo    The signal to remove a listener from.
     * @param  callable $listener The listener to remove.
     * @return void
     */
    public function removeSignalListener(int $signo, callable $listener): void;
}
