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

use Spork\Signal\SignalHandlerWrapper;
use UnexpectedValueException;

/**
 * Partial common implementation of the signal related EventDispatcherInterface
 * interface.
 */
trait SignalEventDispatcherTrait
{
    /**
     * Holds signal handler wrappers to preserve a potentially already existing
     * signal handler.
     *
     * @var array<int,\Spork\Signal\SignalHandlerWrapper> $sigHandlerWrappers
     */
    private $sigHandlerWrappers = [];

    /**
     * Remove all installed signal handler wrappers.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->removeSignalHandlerWrappers();
    }

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
        $this->checkSignalHandler($signo);

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

    /**
     * Removes all signal handlers this class has attached.
     *
     * @return void
     */
    public function removeSignalHandlerWrappers(): void
    {
        foreach ($this->sigHandlerWrappers as $signo => $sigHandlerWrapper) {
            /** @var int|callable $prevSigHandler */
            $prevSigHandler = $this->getSignalHandler($signo);

            // Has not been overwritten in the meantime.
            if ($prevSigHandler === $sigHandlerWrapper) {
                $this->setSignalHandler($signo, $sigHandlerWrapper->getPrevious());
            }

            unset($this->sigHandlerWrappers[$signo]);
        }
    }

    /**
     * Checks whether an event dispatcher signal handler is installed.
     *
     * @param  int $signo The signal being handled.
     * @return void
     */
    private function checkSignalHandler(int $signo): void
    {
        if (array_key_exists($signo, $this->sigHandlerWrappers)) {
            return;
        }

        // 1. Backup previous signal handler.
        $this->sigHandlerWrappers[$signo] = new SignalHandlerWrapper(
            $this->getSignalHandler($signo),
            [$this, 'dispatchSignal']
        );

        // 2. Install the event dispatcher signal handler.
        $this->setSignalHandler($signo, $this->sigHandlerWrappers[$signo]);
    }

    /**
     * Get the current handler for a specific signal. Throws an exception on
     * error.
     *
     * @param  int $signo
     *             The signal for which to get the current handler.
     * @throws \UnexpectedValueException
     *             When the currently installed signal handler could not be
     *             retrieved.
     * @return int|callable
     *             Current signal handler. This may be an integer value that
     *             refers to SIG_DFL or SIG_IGN. If a custom handler was used it
     *             may be a string value containing the function name, an array
     *             containing the instance and method  name or a callable.
     */
    private function getSignalHandler(int $signo)
    {
        /** @var int|callable|false $signalHandler */
        $signalHandler = pcntl_signal_get_handler($signo);
        if ($signalHandler === false) {
            $error = pcntl_get_last_error();
            if ($error === 0) {
                $error = PCNTL_EINVAL;
            }
            $strerror = pcntl_strerror($error);

            throw new UnexpectedValueException(sprintf(
                'Could not get installed signal handler for signal %d. %d: %s',
                $signo,
                $error,
                (string)$strerror
            ), $error);
        }

        return $signalHandler;
    }

    /**
     * Sets a new signal handler for a specific signal. Throws an exception on
     * error.
     *
     * @param  int          $signo
     *             When the currently installed signal handler could not be
     *             retrieved.
     * @param  int|callable $handler
     *             Current signal handler. This may be an integer value that
     *             refers to SIG_DFL or SIG_IGN. If a custom handler was used it
     *             may be a string value containing the function name, an array
     *             containing the instance and method  name or a callable.
     * @throws \UnexpectedValueException
     *             When the new signal handler could not be set.
     * @return void
     */
    private function setSignalHandler(int $signo, $handler): void
    {
        if (!pcntl_signal($signo, $handler)) {
            $error = pcntl_get_last_error();
            if ($error === 0) {
                $error = PCNTL_EINVAL;
            }
            $strerror = pcntl_strerror($error);

            throw new UnexpectedValueException(sprintf(
                'Could not install signal handler for signal %d. %d: %s',
                $signo,
                $error,
                (string)$strerror
            ), $error);
        }
    }
}
