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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TheLevti\phpfork\Deferred\Deferred;
use TheLevti\phpfork\Deferred\DeferredInterface;
use TheLevti\phpfork\Deferred\PromiseInterface;
use TheLevti\phpfork\Exception\ProcessControlException;
use TheLevti\phpfork\Util\ExitMessage;

class Fork implements DeferredInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var int $pid */
    protected $pid;

    /** @var \TheLevti\phpfork\SharedMemory $shm */
    protected $shm;

    /** @var \TheLevti\phpfork\Deferred\DeferredInterface $defer */
    private $defer;

    /** @var string $name */
    private $name;

    /** @var int|null $status */
    private $status;

    /** @var \TheLevti\phpfork\Util\ExitMessage|null $message */
    private $message;

    /** @var array<int,mixed> $messages */
    private $messages;

    public function __construct(int $pid, SharedMemory $shm, ?LoggerInterface $logger = null)
    {
        $this->pid = $pid;
        $this->shm = $shm;
        $this->logger = $logger ?? new NullLogger();
        $this->defer = new Deferred();
        $this->name = '<anonymous>';
        $this->status = null;
        $this->message = null;
        $this->messages = [];
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function wait(bool $hang = true): self
    {
        if ($this->isExited()) {
            return $this;
        }

        $status = 0;
        $pid = pcntl_waitpid($this->pid, $status, ($hang ? 0 : WNOHANG) | WUNTRACED);
        if (-1 === $pid) {
            throw ProcessControlException::pcntlError(
                sprintf('Failed to wait for process %d', $this->pid),
                $this->logger
            );
        }

        if ($this->pid === $pid) {
            $this->processWaitStatus($status);
        }

        return $this;
    }

    /**
     * Processes a status value retrieved while waiting for this fork to exit.
     */
    public function processWaitStatus(int $status): void
    {
        if ($this->isExited()) {
            throw new \LogicException('Cannot set status on an exited fork');
        }

        $this->status = $status;

        if ($this->isExited()) {
            $this->receive();

            $this->shm->cleanup();

            $this->isSuccessful() ? $this->resolve() : $this->reject();

            if (!$this->isSuccessful() || $this->getError()) {
                $this->logger->error(
                    'Processed wait status with not successful result.',
                    [
                        'fork_pid' => $this->pid,
                        'fork_name' => $this->name,
                    ]
                );
            }
        }
    }

    /**
     * @return array<int,mixed>
     */
    public function receive(): array
    {
        foreach ($this->shm->receive() as $message) {
            if ($message instanceof ExitMessage) {
                $this->message = $message;
            } else {
                $this->messages[] = $message;
            }
        }

        return $this->messages;
    }

    public function kill(int $signal = SIGINT): self
    {
        $this->shm->signal($signal);

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getResult()
    {
        if ($this->message) {
            return $this->message->getResult();
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    public function getOutput()
    {
        if ($this->message) {
            return $this->message->getOutput();
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    public function getError()
    {
        if ($this->message) {
            return $this->message->getError();
        }

        return null;
    }

    /**
     * @return array<int,mixed>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function isSuccessful(): bool
    {
        return 0 === $this->getExitStatus();
    }

    public function isExited(): bool
    {
        return null !== $this->status && pcntl_wifexited($this->status);
    }

    public function isStopped(): bool
    {
        return null !== $this->status && pcntl_wifstopped($this->status);
    }

    public function isSignaled(): bool
    {
        return null !== $this->status && pcntl_wifsignaled($this->status);
    }

    public function getExitStatus(): ?int
    {
        if (null !== $this->status) {
            return pcntl_wexitstatus($this->status);
        }

        return null;
    }

    public function getTermSignal(): ?int
    {
        if (null !== $this->status) {
            return pcntl_wtermsig($this->status);
        }

        return null;
    }

    public function getStopSignal(): ?int
    {
        if (null !== $this->status) {
            return pcntl_wstopsig($this->status);
        }

        return null;
    }

    public function getState(): string
    {
        return $this->defer->getState();
    }

    public function progress(callable $progress): PromiseInterface
    {
        $this->defer->progress($progress);

        return $this;
    }

    public function always(callable $always): PromiseInterface
    {
        $this->defer->always($always);

        return $this;
    }

    public function done(callable $done): PromiseInterface
    {
        $this->defer->done($done);

        return $this;
    }

    public function fail(callable $fail): PromiseInterface
    {
        $this->defer->fail($fail);

        return $this;
    }

    public function then(callable $done, callable $fail = null): PromiseInterface
    {
        $this->defer->then($done, $fail);

        return $this;
    }

    public function notify(...$args): DeferredInterface
    {
        array_unshift($args, $this);

        call_user_func_array([$this->defer, 'notify'], $args);

        return $this;
    }

    public function resolve(...$args): DeferredInterface
    {
        array_unshift($args, $this);

        call_user_func_array([$this->defer, 'resolve'], $args);

        return $this;
    }

    public function reject(...$args): DeferredInterface
    {
        array_unshift($args, $this);

        call_user_func_array([$this->defer, 'reject'], $args);

        return $this;
    }
}
