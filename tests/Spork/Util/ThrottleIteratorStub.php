<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Util;

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
