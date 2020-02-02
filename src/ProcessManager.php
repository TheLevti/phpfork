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

namespace Phpfork;

use Exception;
use InvalidArgumentException;
use Phpfork\Batch\Strategy\StrategyInterface;
use Phpfork\EventDispatcher\Events;
use Phpfork\EventDispatcher\SignalEventDispatcher;
use Phpfork\EventDispatcher\SignalEventDispatcherInterface;
use Phpfork\Exception\ProcessControlException;
use Phpfork\Util\Error;
use Phpfork\Util\ExitMessage;
use Symfony\Contracts\EventDispatcher\Event;

class ProcessManager
{
    private $dispatcher;
    private $factory;
    private $debug;
    /** @var bool $zombieOkay */
    private $zombieOkay;
    private $signal;

    /** @var array<int,\Phpfork\Fork> $forks */
    private $forks;

    public function __construct(
        SignalEventDispatcherInterface $dispatcher = null,
        Factory $factory = null,
        $debug = false
    ) {
        $this->dispatcher = $dispatcher ?: new SignalEventDispatcher();
        $this->factory = $factory ?: new Factory();
        $this->debug = $debug;
        $this->zombieOkay = false;
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

    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    public function zombieOkay($zombieOkay = true)
    {
        $this->zombieOkay = $zombieOkay;
    }

    public function createBatchJob($data = null, StrategyInterface $strategy = null)
    {
        return $this->factory->createBatchJob($this, $data, $strategy);
    }

    public function process($data, $callable, StrategyInterface $strategy = null)
    {
        return $this->createBatchJob($data, $strategy)->execute($callable);
    }

    /**
     * Forks something into another process and returns a deferred object.
     *
     * @param callable $callable Code to execute in a fork.
     * @return \Phpfork\Fork Newly created fork.
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

    public function monitor($signal = SIGUSR1)
    {
        $this->signal = $signal;
        $this->dispatcher->addSignalListener($signal, [$this, 'check']);
    }

    public function check()
    {
        foreach ($this->forks as $fork) {
            foreach ($fork->receive() as $message) {
                $fork->notify($message);
            }
        }
    }

    public function wait($hang = true)
    {
        foreach ($this->forks as $fork) {
            $fork->wait($hang);
        }
    }

    public function waitForNext($hang = true)
    {
        if (-1 === $pid = pcntl_wait($status, ($hang ? WNOHANG : 0) | WUNTRACED)) {
            throw new ProcessControlException('Error while waiting for next fork to exit');
        }

        if (isset($this->forks[$pid])) {
            $this->forks[$pid]->processWaitStatus($status);

            return $this->forks[$pid];
        }
    }

    public function waitFor($pid, $hang = true)
    {
        if (!isset($this->forks[$pid])) {
            throw new InvalidArgumentException('There is no fork with PID ' . $pid);
        }

        return $this->forks[$pid]->wait($hang);
    }

    /**
     * Sends a signal to all forks.
     */
    public function killAll($signal = SIGINT)
    {
        foreach ($this->forks as $fork) {
            $fork->kill($signal);
        }
    }
}
