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

use LogicException;
use PHPUnit\Framework\TestCase;

class DeferredTest extends TestCase
{
    /** @var \TheLevti\phpfork\Deferred\DeferredInterface $defer */
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
    public function testCallbackOrder(string $method, string $expected): void
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
    public function testThen(string $method, string $expected): void
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
    public function testMultipleResolve(string $method): void
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
    public function testInvalidResolve(string $method, string $invalid): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('that has already been');

        $this->defer->$method();
        $this->defer->$invalid();
    }

    /**
     * @dataProvider getMethodAndQueue
     */
    public function testAlreadyResolved(string $resolve, string $queue, bool $expect = true): void
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
     * @return array<int,array<int,string>>
     */
    public function getMethodAndKey(): array
    {
        return [
            ['resolve', 'done'],
            ['reject', 'fail'],
        ];
    }

    /**
     * @return array<int,array<int,string>>
     */
    public function getMethodAndInvalid(): array
    {
        return [
            ['resolve', 'reject'],
            ['reject', 'resolve'],
        ];
    }

    /**
     * @return array<int,array<int,string|bool>>
     */
    public function getMethodAndQueue(): array
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

    /**
     * @return array<int,array<int,string>>
     */
    public function getMethod(): array
    {
        return [
            ['resolve'],
            ['reject'],
        ];
    }
}
