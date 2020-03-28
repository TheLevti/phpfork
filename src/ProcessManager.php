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

namespace TheLevti\phpfork;

use Exception;
use InvalidArgumentException;
use Symfony\Contracts\EventDispatcher\Event;
use TheLevti\phpfork\Batch\BatchJob;
use TheLevti\phpfork\Batch\Strategy\StrategyInterface;
use TheLevti\phpfork\EventDispatcher\Events;
use TheLevti\phpfork\EventDispatcher\SignalEventDispatcher;
use TheLevti\phpfork\EventDispatcher\SignalEventDispatcherInterface;
use TheLevti\phpfork\Exception\ProcessControlException;
use TheLevti\phpfork\Util\Error;
use TheLevti\phpfork\Util\ExitMessage;

class ProcessManager
{
    /** @var \TheLevti\phpfork\EventDispatcher\SignalEventDispatcherInterface $dispatcher */
    private $dispatcher;

    /** @var \TheLevti\phpfork\Factory $factory */
    private $factory;

    /** @var bool $debug */
    private $debug;

    /** @var bool $zombieOkay */
    private $zombieOkay;

    /** @var int|null $signal */
    private $signal;

    /** @var array<int,\TheLevti\phpfork\Fork> $forks */
    private $forks;

    public function __construct(
        SignalEventDispatcherInterface $dispatcher = null,
        Factory $factory = null,
        bool $debug = false
    ) {
        $this->dispatcher = $dispatcher ?: new SignalEventDispatcher();
        $this->factory = $factory ?: new Factory();
        $this->debug = $debug;
        $this->zombieOkay = false;
        $this->signal = null;
        $this->forks = [];
    }

    /**
     * Does cleanup when the process manager is destroyed.
     *
     * @return void
     */
    public function __destruct()
    {
        if (!$this->zombieOkay) {
            $this->wait();
        }
    }

    public function getEventDispatcher(): SignalEventDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public function zombieOkay(bool $zombieOkay = true): void
    {
        $this->zombieOkay = $zombieOkay;
    }

    /**
     * @param mixed|null $data
     * @param \TheLevti\phpfork\Batch\Strategy\StrategyInterface|null $strategy
     * @return \TheLevti\phpfork\Batch\BatchJob
     */
    public function createBatchJob($data = null, ?StrategyInterface $strategy = null): BatchJob
    {
        return $this->factory->createBatchJob($this, $data, $strategy);
    }

    /**
     * @param mixed|null $data
     * @param callable|null $callable
     * @param \TheLevti\phpfork\Batch\Strategy\StrategyInterface|null $strategy
     * @return \TheLevti\phpfork\Fork
     */
    public function process($data = null, ?callable $callable = null, ?StrategyInterface $strategy = null): Fork
    {
        return $this->createBatchJob($data, $strategy)->execute($callable);
    }

    /**
     * Forks something into another process and returns a deferred object.
     *
     * @param callable $callable Code to execute in a fork.
     * @return \TheLevti\phpfork\Fork Newly created fork.
     */
    public function fork(callable $callable): Fork
    {
        // allow the system to cleanup before forking
        call_user_func([$this->dispatcher, 'dispatch'], new Event(), Events::PRE_FORK);

        if (-1 === ($pid = pcntl_fork())) {
            throw new ProcessControlException('Unable to fork a new process.');
        }

        if (0 === $pid) {
            // reset the list of child processes
            $this->forks = [];

            // dispatch an event so the system knows it's in a new process
            call_user_func([$this->dispatcher, 'dispatch'], new Event(), Events::POST_FORK);

            // setup the shared memory and exit message.
            $shm = $this->factory->createSharedMemory(null, $this->signal);
            $message = new ExitMessage();

            if (!$this->debug) {
                ob_start();
            }

            try {
                $result = call_user_func($callable, $shm);
                $message->setResult($result);
                $status = is_integer($result) ? $result : 0;
            } catch (Exception $exception) {
                $message->setError(Error::fromException($exception));
                $status = 1;
            }

            if (!$this->debug) {
                $message->setOutput(ob_get_clean());
            }

            try {
                $shm->send($message, false);
            } catch (Exception $exception) {
                // probably an error serializing the result
                $message->setResult(null);
                $message->setError(Error::fromException($exception));

                $shm->send($message, false);

                $status = 2;
            }

            exit($status);
        }

        return $this->forks[$pid] = $this->factory->createFork(
            $pid,
            $this->factory->createSharedMemory($pid),
            $this->debug
        );
    }

    public function monitor(int $signal = SIGUSR1): void
    {
        $this->signal = $signal;
        $this->dispatcher->addSignalListener($signal, [$this, 'check']);
    }

    public function check(): void
    {
        foreach ($this->forks as $fork) {
            foreach ($fork->receive() as $message) {
                $fork->notify($message);
            }
        }
    }

    public function wait(bool $hang = true): void
    {
        foreach ($this->forks as $fork) {
            $fork->wait($hang);
        }
    }

    public function waitForNext(bool $hang = true): ?Fork
    {
        if (-1 === $pid = pcntl_wait($status, ($hang ? WNOHANG : 0) | WUNTRACED)) {
            throw new ProcessControlException('Error while waiting for next fork to exit');
        }

        if (isset($this->forks[$pid])) {
            $this->forks[$pid]->processWaitStatus($status);

            return $this->forks[$pid];
        }

        return null;
    }

    public function waitFor(int $pid, bool $hang = true): Fork
    {
        if (!isset($this->forks[$pid])) {
            throw new InvalidArgumentException('There is no fork with PID ' . $pid);
        }

        return $this->forks[$pid]->wait($hang);
    }

    /**
     * Sends a signal to all forks.
     */
    public function killAll(int $signal = SIGINT): void
    {
        foreach ($this->forks as $fork) {
            $fork->kill($signal);
        }
    }
}
