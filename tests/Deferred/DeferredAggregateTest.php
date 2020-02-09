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

use PHPUnit\Framework\TestCase;
use TheLevti\phpfork\Exception\UnexpectedTypeException;

class DeferredAggregateTest extends TestCase
{
    public function testInvalidChild()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectDeprecationMessage('PromiseInterface');

        $defer = new DeferredAggregate(['asdf']);
    }

    public function testNoChildren()
    {
        $defer = new DeferredAggregate([]);

        $log = [];
        $defer->done(function () use (&$log) {
            $log[] = 'done';
        });

        $this->assertEquals(['done'], $log);
    }

    public function testResolvedChildren()
    {
        $child = new Deferred();
        $child->resolve();

        $defer = new DeferredAggregate([$child]);

        $log = [];
        $defer->done(function () use (&$log) {
            $log[] = 'done';
        });

        $this->assertEquals(['done'], $log);
    }

    public function testResolution()
    {
        $child1 = new Deferred();
        $child2 = new Deferred();

        $defer = new DeferredAggregate([$child1, $child2]);

        $log = [];
        $defer->done(function () use (&$log) {
            $log[] = 'done';
        });

        $this->assertEquals([], $log);

        $child1->resolve();
        $this->assertEquals([], $log);

        $child2->resolve();
        $this->assertEquals(['done'], $log);
    }

    public function testRejection()
    {
        $child1 = new Deferred();
        $child2 = new Deferred();
        $child3 = new Deferred();

        $defer = new DeferredAggregate([$child1, $child2, $child3]);

        $log = [];
        $defer->then(function () use (&$log) {
            $log[] = 'done';
        }, function () use (&$log) {
            $log[] = 'fail';
        });

        $this->assertEquals([], $log);

        $child1->resolve();
        $this->assertEquals([], $log);

        $child2->reject();
        $this->assertEquals(['fail'], $log);

        $child3->resolve();
        $this->assertEquals(['fail'], $log);
    }

    public function testNested()
    {
        $child1a = new Deferred();
        $child1b = new Deferred();
        $child1 = new DeferredAggregate([$child1a, $child1b]);
        $child2 = new Deferred();

        $defer = new DeferredAggregate([$child1, $child2]);

        $child1a->resolve();
        $child1b->resolve();
        $child2->resolve();

        $this->assertEquals('resolved', $defer->getState());
    }

    public function testFail()
    {
        $child = new Deferred();
        $defer = new DeferredAggregate([$child]);

        $log = [];
        $defer->fail(function () use (&$log) {
            $log[] = 'fail';
        });

        $child->reject();

        $this->assertEquals(['fail'], $log);
    }
}
