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

use TheLevti\phpfork\Exception\UnexpectedTypeException;

class DeferredAggregate implements PromiseInterface
{
    /**
     * @var array<int|string,\TheLevti\phpfork\Deferred\PromiseInterface> $children
     */
    private $children;

    /**
     * @var \TheLevti\phpfork\Deferred\Deferred $delegate
     */
    private $delegate;

    /**
     * @param array<int|string,\TheLevti\phpfork\Deferred\PromiseInterface> $children
     * @throws UnexpectedTypeException
     */
    public function __construct(array $children)
    {
        foreach ($children as $child) {
            if (!$child instanceof PromiseInterface) {
                throw new UnexpectedTypeException($child, 'TheLevti\phpfork\Deferred\PromiseInterface');
            }
        }

        $this->children = $children;
        $this->delegate = new Deferred();

        // connect to each child
        foreach ($this->children as $child) {
            $child->always([$this, 'tick']);
        }

        // always tick once now
        $this->tick();
    }

    public function getState(): string
    {
        return $this->delegate->getState();
    }

    /**
     * @return array<int|string,\TheLevti\phpfork\Deferred\PromiseInterface>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function progress(callable $progress): PromiseInterface
    {
        $this->delegate->progress($progress);

        return $this;
    }

    public function always(callable $always): PromiseInterface
    {
        $this->delegate->always($always);

        return $this;
    }

    public function done(callable $done): PromiseInterface
    {
        $this->delegate->done($done);

        return $this;
    }

    public function fail(callable $fail): PromiseInterface
    {
        $this->delegate->fail($fail);

        return $this;
    }

    public function then(callable $done, callable $fail = null): PromiseInterface
    {
        $this->delegate->then($done, $fail);

        return $this;
    }

    public function tick(): void
    {
        $pending = count($this->children);

        foreach ($this->children as $child) {
            switch ($child->getState()) {
                case PromiseInterface::STATE_REJECTED:
                    $this->delegate->reject($this);

                    return;
                case PromiseInterface::STATE_RESOLVED:
                    --$pending;
                    break;
            }
        }

        if (!$pending) {
            $this->delegate->resolve($this);
        }
    }
}
