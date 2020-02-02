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

namespace Phpfork;

use Exception;
use PHPUnit\Framework\TestCase;
use stdClass;

class ProcessManagerTest extends TestCase
{
    /**
     * Process Manager object
     *
     * @var ProcessManager
     */
    private $manager;

    protected function setUp(): void
    {
        $this->manager = new ProcessManager();
    }

    protected function tearDown(): void
    {
        unset($this->manager);
    }

    public function testDoneCallbacks()
    {
        $success = null;

        $fork = $this->manager->fork(function () {
            echo 'output';

            return 'result';
        })->done(function () use (&$success) {
            $success = true;
        })->fail(function () use (&$success) {
            $success = false;
        });

        $this->manager->wait();

        $this->assertTrue($success);
        $this->assertEquals('output', $fork->getOutput());
        $this->assertEquals('result', $fork->getResult());
    }

    public function testFailCallbacks()
    {
        $success = null;

        $fork = $this->manager->fork(function () {
            throw new \Exception('child error');
        })->done(function () use (&$success) {
            $success = true;
        })->fail(function () use (&$success) {
            $success = false;
        });

        $this->manager->wait();

        $this->assertFalse($success);
        $this->assertNotEmpty($fork->getError());
    }

    public function testObjectReturn()
    {
        $mock = $this->getMockBuilder(stdClass::class)->setMethods(['__sleep'])->getMock();
        $mock->method('__sleep')->willThrowException(new Exception("Hey, don\'t serialize me!"));

        $fork = $this->manager->fork(function () use (&$mock) {
            return $mock;
        });

        $this->manager->wait();

        $this->assertNull($fork->getResult());
        $this->assertFalse($fork->isSuccessful());
    }

    public function testBatchProcessing()
    {
        $expected = range(100, 109);

        $fork = $this->manager->process($expected, function ($item) {
            return $item;
        });

        $this->manager->wait();

        $this->assertEquals($expected, $fork->getResult());
    }

    /**
     * Test batch processing with return values containing a newline character
     */
    public function testBatchProcessingWithNewlineReturnValues()
    {
        $range = range(100, 109);
        $expected = [
            0 => "SomeString\n100",
            1 => "SomeString\n101",
            2 => "SomeString\n102",
            3 => "SomeString\n103",
            4 => "SomeString\n104",
            5 => "SomeString\n105",
            6 => "SomeString\n106",
            7 => "SomeString\n107",
            8 => "SomeString\n108",
            9 => "SomeString\n109",
        ];

        $this->manager->setDebug(true);
        $fork = $this->manager->process($range, function ($item) {
            return "SomeString\n$item";
        });

        $this->manager->wait();

        $this->assertEquals($expected, $fork->getResult());
    }

    /**
     * Data provider for `testLargeBatchProcessing()`
     *
     * @return array
     */
    public function batchProvider()
    {
        return [
            [10],
            [1000],
            [6941],
            [6942],
            [6000],
            [10000],
            [20000],
        ];
    }

    /**
     * Test large batch sizes
     *
     * @dataProvider batchProvider
     */
    public function testLargeBatchProcessing($rangeEnd)
    {
        $expected = array_fill(0, $rangeEnd, null);

        /** @var Fork $fork */
        $fork = $this->manager->process($expected, function ($item) {
            return $item;
        });

        $this->manager->wait();

        $this->assertEquals($expected, $fork->getResult());
    }
}
