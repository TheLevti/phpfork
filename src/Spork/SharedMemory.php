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

namespace Spork;

use Spork\Exception\ProcessControlException;

/**
 * Sends messages between processes.
 */
class SharedMemory
{
    /** @var int $pid */
    private $pid;
    /** @var int|null $pid */
    private $ppid;
    private $signal;

    /**
     * Constructor.
     *
     * @param integer $pid    The child process id or null if this is the child
     * @param integer $signal The signal to send after writing to shared memory
     */
    public function __construct($pid = null, $signal = null)
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
            throw new ProcessControlException(sprintf(
                'Not able to read from shared memory segment for PID: %d',
                $this->pid
            ));
        }

        if (false === shmop_delete($shmId)) {
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
    public function send($message, $signal = null, $pause = 500)
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
        $shmId = shmop_open($this->pid, 'c', 0644, $termMsgsLen);
        if (!is_resource($shmId)) {
            throw new ProcessControlException(sprintf(
                'Not able to create shared memory segment for PID: %d',
                $this->pid
            ));
        } elseif (shmop_write($shmId, $terminatedMsgs, 0) !== $termMsgsLen) {
            throw new ProcessControlException(sprintf(
                'Not able to write to shared memory segment for PID: %d.',
                $this->pid
            ));
        }

        if (false === $signal) {
            return;
        }

        $this->signal(null === $signal ? $this->signal : $signal);

        usleep($pause);
    }

    /**
     * Sends a signal to the other process.
     */
    public function signal($signal)
    {
        $pid = null === $this->ppid ? $this->pid : $this->ppid;

        return posix_kill($pid, $signal);
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
