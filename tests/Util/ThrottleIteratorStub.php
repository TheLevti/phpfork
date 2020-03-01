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

class ThrottleIteratorStub extends ThrottleIterator
{
    /**
     * @var array<int,float> $loads
     */
    public $loads = [];

    /**
     * @var array<int,int> $sleeps
     */
    public $sleeps = [];

    protected function getLoad(): float
    {
        return (float)array_shift($this->loads);
    }

    protected function sleep(int $period): void
    {
        $this->sleeps[] = $period;
    }
}
