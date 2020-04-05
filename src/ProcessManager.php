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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\Event;
use TheLevti\phpfork\Batch\BatchJob;
use TheLevti\phpfork\Batch\Strategy\StrategyInterface;
use TheLevti\phpfork\EventDispatcher\Events;
use TheLevti\phpfork\EventDispatcher\SignalEventDispatcher;
use TheLevti\phpfork\EventDispatcher\SignalEventDispatcherInterface;
use TheLevti\phpfork\Exception\OutputBufferingException;
use TheLevti\phpfork\Exception\ProcessControlException;
use TheLevti\phpfork\Util\Error;
use TheLevti\phpfork\Util\ExitMessage;

class ProcessManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var \TheLevti\phpfork\EventDispatcher\SignalEventDispatcherInterface $dispatcher */
    protected $dispatcher;

    /** @var \TheLevti\phpfork\Factory $factory */
    protected $factory;

    /** @var bool $zombieOkay */
    private $zombieOkay;

    /** @var int|null $signal */
    private $signal;

    /** @var array<int,\TheLevti\phpfork\Fork> $forks */
    private $forks;

    public function __construct(
        SignalEventDispatcherInterface $dispatcher = null,
        Factory $factory = null,
        ?LoggerInterface $logger = null
    ) {
        $this->dispatcher = $dispatcher ?? new SignalEventDispatcher();
        $this->factory = $factory ?? new Factory();
        $this->logger = $logger ?? new NullLogger();

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
        call_user_func([$this->dispatcher, 'dispatch'], new Event(), Events::PRE_FORK);

        $pid = pcntl_fork();

        // Error case.
        if (-1 === $pid) {
            throw ProcessControlException::pcntlError('Failed to fork process.', $this->logger);
        }

        // Child case.
        if (0 === $pid) {
            // reset the list of child processes
            $this->forks = [];

            // dispatch an event so the system knows it's in a new process
            call_user_func([$this->dispatcher, 'dispatch'], new Event(), Events::POST_FORK);

            // setup the shared memory and exit message.
            $shm = $this->factory->createSharedMemory(null, $this->signal, $this->logger);
            $message = new ExitMessage();

            if (ob_start() === false) {
                throw new OutputBufferingException('Failed to start output buffering.');
            }

            try {
                $result = call_user_func($callable, $shm);
                $message->setResult($result);
                $status = is_integer($result) ? $result : 0;
            } catch (Exception $exception) {
                $message->setError(Error::fromException($exception));
                $status = 1;
            }

            $output = ob_get_clean();
            if ($output === false) {
                throw new OutputBufferingException('Failed to get and clean output buffer.');
            }

            $message->setOutput($output);

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

        // Parent case.
        return $this->forks[$pid] = $this->factory->createFork(
            $pid,
            $this->factory->createSharedMemory($pid, null, $this->logger),
            $this->logger
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
