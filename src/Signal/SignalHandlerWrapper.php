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

namespace Phpfork\Signal;

/**
 * Wraps an existing signal handler with a new signal handler.
 */
class SignalHandlerWrapper
{
    /**
     * Previous signal handler. This may be an integer value that refers to
     * SIG_DFL or SIG_IGN. If a custom handler was used it may be a string value
     * containing the function name, an array containing the instance and method
     * name or a callable.
     *
     * @var int|callable $previous
     */
    private $previous;

    /**
     * Current signal handler to execute. This may be an integer value that
     * refers to SIG_DFL or SIG_IGN. If a custom handler should be used it may
     * be a string value containing the function name, an array containing the
     * instance and method name or a callable.
     *
     * @var int|callable $current
     */
    private $current;

    /**
     * Wrapper callable, which can be used as the new signal handler. It will
     * execute the previous and then the new signal handler.
     *
     * @var callable
     */
    private $wrapper;

    /**
     * Constructs a new instance of the SignalHandlerWrapper class.
     *
     * @param int|callable $previous
     * @param int|callable $current
     */
    public function __construct($previous, $current)
    {
        $this->previous = $previous;
        $this->current = $current;

        $this->wrapper = function (int $signo, $signinfo) {
            if (is_callable($this->previous)) {
                call_user_func($this->previous, $signo, $signinfo);
            }

            if (is_callable($this->current)) {
                call_user_func($this->current, $signo, $signinfo);
            }
        };
    }

    /**
     * Invokes the signal handler wrapper.
     *
     * @param  int   $signo
     *             The signal being handled.
     * @param  mixed $signinfo
     *             If operating systems supports siginfo_t structures, this will
     *             be an array of signal information dependent on the signal.
     * @return void
     */
    public function __invoke(int $signo, $signinfo): void
    {
        call_user_func($this->wrapper, $signo, $signinfo);
    }

    /**
     * Gets the previous signal handler.
     *
     * @return int|callable Previous signal handler.
     */
    public function getPrevious()
    {
        return $this->previous;
    }

    /**
     * Gets the current signal handler.
     *
     * @return int|callable Current signal handler.
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * Gets the wrapper signal handler.
     *
     * @return callable Wrapper signal handler.
     */
    public function getWrapper(): callable
    {
        return $this->wrapper;
    }
}
