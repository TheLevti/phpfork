<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Deferred;

use LogicException;
use PHPUnit\Framework\TestCase;
use Spork\Exception\UnexpectedTypeException;

class DeferredTest extends TestCase
{
    private $defer;

    protected function setUp(): void
    {
        $this->defer = new Deferred();
    }

    protected function tearDown(): void
    {
        unset($this->defer);
    }

    /**
     * @dataProvider getMethodAndKey
     */
    public function testCallbackOrder($method, $expected)
    {
        $log = [];

        $this->defer->always(function () use (&$log) {
            $log[] = 'always';
            $log[] = func_get_args();
        })->done(function () use (&$log) {
            $log[] = 'done';
            $log[] = func_get_args();
        })->fail(function () use (&$log) {
            $log[] = 'fail';
            $log[] = func_get_args();
        });

        $this->defer->$method(1, 2, 3);

        $this->assertEquals([
            'always',
            [1, 2, 3],
            $expected,
            [1, 2, 3],
        ], $log);
    }

    /**
     * @dataProvider getMethodAndKey
     */
    public function testThen($method, $expected)
    {
        $log = [];

        $this->defer->then(function () use (&$log) {
            $log[] = 'done';
        }, function () use (&$log) {
            $log[] = 'fail';
        });

        $this->defer->$method();

        $this->assertEquals([$expected], $log);
    }

    /**
     * @dataProvider getMethod
     */
    public function testMultipleResolve($method)
    {
        $log = [];

        $this->defer->always(function () use (&$log) {
            $log[] = 'always';
        });

        $this->defer->$method();
        $this->defer->$method();

        $this->assertEquals(['always'], $log);
    }

    /**
     * @dataProvider getMethodAndInvalid
     */
    public function testInvalidResolve($method, $invalid)
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('that has already been');

        $this->defer->$method();
        $this->defer->$invalid();
    }

    /**
     * @dataProvider getMethodAndQueue
     */
    public function testAlreadyResolved($resolve, $queue, $expect = true)
    {
        // resolve the object
        $this->defer->$resolve();

        $log = [];
        $this->defer->$queue(function () use (&$log, $queue) {
            $log[] = $queue;
        });

        $this->assertEquals($expect ? [$queue] : [], $log);
    }

    /**
     * @dataProvider getMethodAndInvalidCallback
     */
    public function testInvalidCallback($method, $invalid)
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('callable');

        $this->defer->$method($invalid);
    }

    // providers

    public function getMethodAndKey()
    {
        return [
            ['resolve', 'done'],
            ['reject', 'fail'],
        ];
    }

    public function getMethodAndInvalid()
    {
        return [
            ['resolve', 'reject'],
            ['reject', 'resolve'],
        ];
    }

    public function getMethodAndQueue()
    {
        return [
            ['resolve', 'always'],
            ['resolve', 'done'],
            ['resolve', 'fail', false],
            ['reject', 'always'],
            ['reject', 'done', false],
            ['reject', 'fail'],
        ];
    }

    public function getMethodAndInvalidCallback()
    {
        return [
            ['always', 'foo!'],
            ['always', ['foo!']],
            ['done', 'foo!'],
            ['done', ['foo!']],
            ['fail', 'foo!'],
            ['fail', ['foo!']],
        ];
    }

    public function getMethod()
    {
        return [
            ['resolve'],
            ['reject'],
        ];
    }
}
