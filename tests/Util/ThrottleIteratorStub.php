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
    public $loads = [];
    public $sleeps = [];

    protected function getLoad()
    {
        return (int) array_shift($this->loads);
    }

    protected function sleep($period)
    {
        $this->sleeps[] = $period;
    }
}
