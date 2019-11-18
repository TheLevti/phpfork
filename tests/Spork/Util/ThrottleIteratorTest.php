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

use PHPUnit\Framework\TestCase;

class ThrottleIteratorTest extends TestCase
{
    private $iterator;

    protected function setUp(): void
    {
        $this->iterator = new ThrottleIteratorStub([1, 2, 3, 4, 5], 3);
        $this->iterator->loads = [4, 4, 4, 1, 1];
    }

    protected function tearDown(): void
    {
        unset($this->iterator);
    }

    public function testIteration()
    {
        iterator_to_array($this->iterator);
        $this->assertEquals([1, 2, 4], $this->iterator->sleeps);
    }
}
