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

namespace TheLevti\phpfork;

use Psr\Log\LoggerInterface;
use TheLevti\phpfork\Batch\BatchJob;
use TheLevti\phpfork\Batch\Strategy\StrategyInterface;

class Factory
{
    /**
     * Creates a new batch job instance.
     *
     * @param \TheLevti\phpfork\ProcessManager                        $manager  The process manager.
     * @param mixed|null                                              $data     Data for the batch job.
     * @param \TheLevti\phpfork\Batch\Strategy\StrategyInterface|null $strategy The strategy.
     *
     * @return \TheLevti\phpfork\Batch\BatchJob A new batch job instance
     */
    public function createBatchJob(ProcessManager $manager, $data = null, StrategyInterface $strategy = null)
    {
        return new BatchJob($manager, $data, $strategy);
    }

    public function createSharedMemory(?int $pid = null, ?int $signal = null, ?LoggerInterface $logger = null): SharedMemory
    {
        return new SharedMemory($pid, $signal, $logger);
    }

    public function createFork(int $pid, SharedMemory $shm, ?LoggerInterface $logger = null): Fork
    {
        return new Fork($pid, $shm, $logger);
    }
}
