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

use TheLevti\phpfork\Batch\Strategy\ChunkStrategy;
use TheLevti\phpfork\Batch\Strategy\StrategyInterface;
use TheLevti\phpfork\Fork;
use TheLevti\phpfork\ProcessManager;

class BatchJob
{
    /**
     * @var \TheLevti\phpfork\ProcessManager $processManager
     */
    protected $processManager;

    /**
     * @var mixed $data
     */
    private $data;

    /**
     * @var \TheLevti\phpfork\Batch\Strategy\StrategyInterface $strategy
     */
    private $strategy;

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var callable $callback
     */
    private $callback;

    /**
     * @param \TheLevti\phpfork\ProcessManager $processManager
     * @param mixed|null $data
     * @param \TheLevti\phpfork\Batch\Strategy\StrategyInterface|null $strategy
     * @return void
     */
    public function __construct(ProcessManager $processManager, $data = null, StrategyInterface $strategy = null)
    {
        $this->processManager = $processManager;
        $this->data = $data;
        $this->strategy = $strategy ?: new ChunkStrategy();
        $this->name = '<anonymous>';
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setStrategy(StrategyInterface $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * @param mixed $data
     * @return \TheLevti\phpfork\Batch\BatchJob
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function execute(?callable $callback = null): Fork
    {
        if (null !== $callback) {
            $this->setCallback($callback);
        }

        return $this->processManager->fork($this)->setName($this->name . ' batch');
    }

    /**
     * Runs in a child process.
     *
     * @see \TheLevti\phpfork\Batch\BatchJob::execute()
     *
     * @return array<mixed> Result from the batch job.
     */
    public function __invoke(): array
    {
        /** @var array<int,\TheLevti\phpfork\Fork> $forks */
        $forks = [];
        foreach ($this->strategy->createBatches($this->data) as $index => $batch) {
            $forks[] = $this->processManager
                ->fork($this->strategy->createRunner($batch, $this->callback))
                ->setName(sprintf('%s batch #%d', $this->name, $index))
            ;
        }

        $this->processManager->wait();

        /** @var array<int,mixed> $results */
        $results = [];
        foreach ($forks as $fork) {
            $batchResult = $fork->getResult();

            if (is_array($batchResult)) {
                $results = array_merge($results, $batchResult);
            } else {
                $results[] = $batchResult;
            }
        }

        return $results;
    }
}
