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

namespace TheLevti\phpfork\Util;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use OuterIterator;
use TheLevti\phpfork\Exception\UnexpectedTypeException;

/**
 * Throttles iteration based on a system load threshold.
 */
class ThrottleIterator implements OuterIterator
{
    /** @var callable|iterable<int|string,mixed> $inner */
    private $inner;

    /** @var float $threshold */
    private $threshold;

    /** @var int|null $lastThrottle */
    private $lastThrottle;

    /**
     * @param callable|iterable<int|string,mixed> $inner
     * @param float $threshold
     * @throws UnexpectedTypeException
     */
    public function __construct($inner, float $threshold)
    {
        if (!is_callable($inner) && !is_iterable($inner)) {
            throw new UnexpectedTypeException($inner, 'callable, array, or Traversable');
        }

        $this->inner = $inner;
        $this->threshold = $threshold;
    }

    /**
     * Attempts to lazily resolve the supplied inner to an instance of Iterator.
     *
     * @return Iterator<int|string,mixed>
     */
    public function getInnerIterator(): Iterator
    {
        if (is_callable($this->inner)) {
            $this->inner = call_user_func($this->inner);
        }

        if (is_array($this->inner)) {
            $this->inner = new ArrayIterator($this->inner);
        } elseif ($this->inner instanceof IteratorAggregate) {
            while ($this->inner instanceof IteratorAggregate) {
                $this->inner = $this->inner->getIterator();
            }
        }

        if (!$this->inner instanceof Iterator) {
            throw new UnexpectedTypeException($this->inner, 'Iterator');
        }

        return $this->inner;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        // only throttle every 5s
        if ($this->lastThrottle < time() - 5) {
            $this->throttle();
        }

        return $this->getInnerIterator()->current();
    }

    /**
     *
     * @return scalar
     */
    public function key()
    {
        return $this->getInnerIterator()->key();
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->getInnerIterator()->next();
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->getInnerIterator()->rewind();
    }

    public function valid(): bool
    {
        return $this->getInnerIterator()->valid();
    }

    protected function getLoad(): float
    {
        list($load) = sys_getloadavg();

        return $load;
    }

    protected function sleep(int $period): void
    {
        sleep($period);
    }

    private function throttle(int $period = 1): void
    {
        $this->lastThrottle = time();

        if ($this->threshold <= $this->getLoad()) {
            $this->sleep($period);
            $this->throttle($period * 2);
        }
    }
}
