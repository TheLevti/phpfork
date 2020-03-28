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

namespace TheLevti\phpfork\Deferred;

use LogicException;

class Deferred implements DeferredInterface
{
    /**
     * @var string $state
     */
    private $state;

    /**
     * @var array<int,callable> $progressCallbacks
     */
    private $progressCallbacks;

    /**
     * @var array<int,callable> $alwaysCallbacks
     */
    private $alwaysCallbacks;

    /**
     * @var array<int,callable> $doneCallbacks
     */
    private $doneCallbacks;

    /**
     * @var array<int,callable> $failCallbacks
     */
    private $failCallbacks;

    /**
     * @var array<int,mixed> $callbackArgs
     */
    private $callbackArgs;

    public function __construct()
    {
        $this->state = DeferredInterface::STATE_PENDING;

        $this->progressCallbacks = [];
        $this->alwaysCallbacks = [];
        $this->doneCallbacks = [];
        $this->failCallbacks = [];
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function progress(callable $progress): PromiseInterface
    {
        $this->progressCallbacks[] = $progress;

        return $this;
    }

    public function always(callable $always): PromiseInterface
    {
        switch ($this->state) {
            case DeferredInterface::STATE_PENDING:
                $this->alwaysCallbacks[] = $always;
                break;
            default:
                call_user_func_array($always, $this->callbackArgs);
                break;
        }

        return $this;
    }

    public function done(callable $done): PromiseInterface
    {
        switch ($this->state) {
            case DeferredInterface::STATE_PENDING:
                $this->doneCallbacks[] = $done;
                break;
            case DeferredInterface::STATE_RESOLVED:
                call_user_func_array($done, $this->callbackArgs);
        }

        return $this;
    }

    public function fail(callable $fail): PromiseInterface
    {
        switch ($this->state) {
            case DeferredInterface::STATE_PENDING:
                $this->failCallbacks[] = $fail;
                break;
            case DeferredInterface::STATE_REJECTED:
                call_user_func_array($fail, $this->callbackArgs);
                break;
        }

        return $this;
    }

    public function then(callable $done, callable $fail = null): PromiseInterface
    {
        $this->done($done);

        if (is_callable($fail)) {
            $this->fail($fail);
        }

        return $this;
    }

    public function notify(...$args): DeferredInterface
    {
        if (DeferredInterface::STATE_PENDING !== $this->state) {
            throw new LogicException('Cannot notify a deferred object that is no longer pending');
        }

        foreach ($this->progressCallbacks as $func) {
            call_user_func_array($func, $args);
        }

        return $this;
    }

    public function resolve(...$args): DeferredInterface
    {
        if (DeferredInterface::STATE_REJECTED === $this->state) {
            throw new LogicException('Cannot resolve a deferred object that has already been rejected');
        }

        if (DeferredInterface::STATE_RESOLVED === $this->state) {
            return $this;
        }

        $this->state = DeferredInterface::STATE_RESOLVED;
        $this->callbackArgs = $args;

        while ($func = array_shift($this->alwaysCallbacks)) {
            call_user_func_array($func, $this->callbackArgs);
        }

        while ($func = array_shift($this->doneCallbacks)) {
            call_user_func_array($func, $this->callbackArgs);
        }

        return $this;
    }

    public function reject(...$args): DeferredInterface
    {
        if (DeferredInterface::STATE_RESOLVED === $this->state) {
            throw new LogicException('Cannot reject a deferred object that has already been resolved');
        }

        if (DeferredInterface::STATE_REJECTED === $this->state) {
            return $this;
        }

        $this->state = DeferredInterface::STATE_REJECTED;
        $this->callbackArgs = $args;

        while ($func = array_shift($this->alwaysCallbacks)) {
            call_user_func_array($func, $this->callbackArgs);
        }

        while ($func = array_shift($this->failCallbacks)) {
            call_user_func_array($func, $this->callbackArgs);
        }

        return $this;
    }
}
