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

namespace TheLevti\phpfork\Batch;

use TheLevti\phpfork\SharedMemory;
use UnexpectedValueException;

class BatchRunner
{
    /**
     * @var array<int|string,mixed>|callable $batch
     */
    private $batch;

    /**
     * @var callable $callback
     */
    private $callback;

    /**
     * Constructor.
     *
     * The callback should be a callable with the following signature:
     *
     *     function($item, $index, $batch, $sharedMem)
     *
     * @param array<int|string,mixed>|callable $batch
     * @param callable $callback
     */
    public function __construct($batch, callable $callback)
    {
        $this->batch = $batch;
        $this->callback = $callback;
    }

    /**
     * @param \TheLevti\phpfork\SharedMemory $shm
     * @throws \UnexpectedValueException
     * @return array<int|string,mixed>
     */
    public function __invoke(SharedMemory $shm): array
    {
        // lazy batch...
        if (is_callable($this->batch)) {
            /** @var array<mixed> $batchArray */
            $batchArray = call_user_func($this->batch);
        } elseif (is_array($this->batch)) {
            /** @var array<mixed> $batchArray */
            $batchArray = $this->batch;
        } else {
            throw new UnexpectedValueException('Batch is not an array nor a callable.');
        }

        /** @var array<int|string,mixed> $results */
        $results = [];
        foreach ($batchArray as $index => $item) {
            $results[$index] = call_user_func($this->callback, $item, $index, $this->batch, $shm);
        }

        return $results;
    }
}
