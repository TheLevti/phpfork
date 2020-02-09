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
