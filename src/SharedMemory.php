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
use TheLevti\phpfork\Exception\PosixException;
use TheLevti\phpfork\Exception\ProcessControlException;

/**
 * Sends messages between processes.
 */
class SharedMemory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var int $pid */
    protected $pid;

    /** @var int|null $signal */
    protected $signal;

    /** @var int|null $ppid */
    private $ppid;

    /**
     * Constructor.
     *
     * @param int|null                 $pid    The child process id or null if this is the child
     * @param int|null                 $signal The signal to send after writing to shared memory
     * @param \Psr\Log\LoggerInterface $logger PSR-3 logger instance.
     */
    public function __construct(?int $pid = null, ?int $signal = null, ?LoggerInterface $logger = null)
    {
        if (null === $pid) {
            // child
            $pid = posix_getpid();
            $ppid = posix_getppid();
        } else {
            // parent
            $ppid = null;
        }

        $this->pid = $pid;
        $this->ppid = $ppid;
        $this->signal = $signal;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Clean up shared memory when not needed any longer.
     *
     * @return void
     */
    public function cleanup(): void
    {
        if (false === ($shmId = @shmop_open($this->pid, 'a', 0, 0))) {
            return;
        }

        shmop_delete($shmId);
        shmop_close($shmId);
    }

    /**
     * Reads all messages from shared memory.
     *
     * @return mixed Any data that has been serialized into the shared memory.
     */
    public function receive()
    {
        if (false === ($shmId = @shmop_open($this->pid, 'a', 0, 0))) {
            return [];
        }

        /** @var string|false $sharedMemory */
        $sharedMemory = shmop_read($shmId, 0, 0);
        if (false === $sharedMemory) {
            shmop_close($shmId);

            throw new ProcessControlException(sprintf(
                'Not able to read from shared memory segment for PID: %d',
                $this->pid
            ));
        } elseif (false === shmop_delete($shmId)) {
            shmop_close($shmId);

            throw new ProcessControlException(sprintf(
                'Not able to delete shared memory segment for PID: %d',
                $this->pid
            ));
        }

        shmop_close($shmId);

        return unserialize($this->strFromMem($sharedMemory));
    }

    /**
     * Writes a message to the shared memory.
     *
     * @param mixed          $message The message to send.
     * @param int|null|false $signal  The signal to send afterward. Null to use.
     * @param int            $pause   The number of microseconds to pause after
     *                                signalling.
     */
    public function send($message, $signal = null, int $pause = 500): void
    {
        $messages = $this->receive();
        if (!is_array($message)) {
            $messages = [
                $messages,
            ];
        }

        // Add the current message to the end of the array, and serialize it
        $messages[] = $message;
        $serializedMsgs = serialize($messages);
        $terminatedMsgs = $this->strToMem($serializedMsgs);
        $termMsgsLen = strlen($terminatedMsgs);

        // Write new serialized message to shared memory
        if (false === ($shmId = @shmop_open($this->pid, 'c', 0644, $termMsgsLen))) {
            throw new ProcessControlException(sprintf(
                'Not able to create shared memory segment for PID: %d',
                $this->pid
            ));
        } elseif (shmop_write($shmId, $terminatedMsgs, 0) !== $termMsgsLen) {
            shmop_close($shmId);

            throw new ProcessControlException(sprintf(
                'Not able to write to shared memory segment for PID: %d.',
                $this->pid
            ));
        }

        shmop_close($shmId);

        if (false === $signal) {
            return;
        }

        if (null === $signal) {
            $signal = $this->signal;

            if (null == $signal) {
                return;
            }
        }

        $this->signal($signal);

        usleep($pause);
    }

    /**
     * Sends a signal to the other process.
     *
     * @param int $signal Signal to send to the process.
     * @return static Current shared memory instance.
     */
    public function signal(int $signal)
    {
        $pid = null === $this->ppid ? $this->pid : $this->ppid;

        if (posix_kill($pid, $signal) === false) {
            throw PosixException::posixError(
                sprintf('Failed to send signal %u to process %d.', $signal, $pid),
                $this->logger
            );
        };

        return $this;
    }

    /**
     * Prepares a string to be stored in memory by appending a terminating zero.
     *
     * @param  string $string String to be prepared.
     * @return string String safe to be directly put in memory.
     */
    private function strToMem(string &$string): string
    {
        return "{$string}\0";
    }

    /**
     * Reads a string from memory by stopping at the first terminating zero.
     *
     * @param  string $rawString String from memory.
     * @return string String ending without the first terminating zero.
     */
    private function strFromMem(string &$rawString): string
    {
        $pos = strpos($rawString, "\0");
        if (false === $pos) {
            return $rawString;
        }

        return substr($rawString, 0, $pos);
    }
}
