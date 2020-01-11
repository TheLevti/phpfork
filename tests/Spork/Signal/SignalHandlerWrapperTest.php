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

namespace Spork\Signal;

use PHPUnit\Framework\TestCase;

class SignalHandlerWrapperTest extends TestCase
{
    public function testSignalHandlerWrapper()
    {
        $prevCalls = 0;
        $currCalls = 0;

        $previous = function () use (&$prevCalls) {
            ++$prevCalls;
        };

        $current = function () use (&$currCalls) {
            ++$currCalls;
        };

        $sigHandlerWrapper = new SignalHandlerWrapper($previous, $current);
        $prev = $sigHandlerWrapper->getPrevious();
        $curr = $sigHandlerWrapper->getCurrent();

        $this->assertEquals(0, $prevCalls);
        $this->assertEquals(0, $currCalls);
        $this->assertSame($previous, $prev);
        $this->assertSame($current, $curr);

        $prev();
        $curr();

        $this->assertEquals(1, $prevCalls);
        $this->assertEquals(1, $currCalls);

        $wrapper = $sigHandlerWrapper->getWrapper();
        $this->assertIsCallable($wrapper);

        $wrapper(SIGUSR1, null);

        $this->assertEquals(2, $prevCalls);
        $this->assertEquals(2, $currCalls);

        $sigHandlerWrapper(SIGUSR1, null);

        $this->assertEquals(3, $prevCalls);
        $this->assertEquals(3, $currCalls);
    }
}
