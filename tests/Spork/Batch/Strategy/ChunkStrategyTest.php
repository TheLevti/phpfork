<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Batch\Strategy;

use PHPUnit\Framework\TestCase;

class ChunkStrategyTest extends TestCase
{
    /**
     * @dataProvider provideNumber
     */
    public function testChunkArray($number, $expectedCounts)
    {
        $strategy = new ChunkStrategy($number);
        $batches = $strategy->createBatches(range(1, 100));

        $this->assertEquals(count($expectedCounts), count($batches));
        foreach ($batches as $i => $batch) {
            $this->assertCount($expectedCounts[$i], $batch);
        }
    }

    public function provideNumber()
    {
        return [
            [1, [100]],
            [2, [50, 50]],
            [3, [34, 34, 32]],
            [4, [25, 25, 25, 25]],
            [5, [20, 20, 20, 20, 20]],
        ];
    }
}
