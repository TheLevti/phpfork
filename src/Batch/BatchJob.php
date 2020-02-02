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

namespace Phpfork\Batch;

use Phpfork\Batch\Strategy\ChunkStrategy;
use Phpfork\Batch\Strategy\StrategyInterface;
use Phpfork\Exception\UnexpectedTypeException;
use Phpfork\ProcessManager;

class BatchJob
{
    private $manager;
    private $data;
    private $strategy;
    private $name;
    private $callback;

    public function __construct(ProcessManager $manager, $data = null, StrategyInterface $strategy = null)
    {
        $this->manager = $manager;
        $this->data = $data;
        $this->strategy = $strategy ?: new ChunkStrategy();
        $this->name = '<anonymous>';
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setStrategy(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;

        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function setCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new UnexpectedTypeException($callback, 'callable');
        }

        $this->callback = $callback;

        return $this;
    }

    public function execute($callback = null)
    {
        if (null !== $callback) {
            $this->setCallback($callback);
        }

        return $this->manager->fork($this)->setName($this->name . ' batch');
    }

    /**
     * Runs in a child process.
     *
     * @see execute()
     */
    public function __invoke()
    {
        $forks = [];
        foreach ($this->strategy->createBatches($this->data) as $index => $batch) {
            $forks[] = $this->manager
                ->fork($this->strategy->createRunner($batch, $this->callback))
                ->setName(sprintf('%s batch #%d', $this->name, $index))
            ;
        }

        // block until all forks have exited
        $this->manager->wait();

        $results = [];
        foreach ($forks as $fork) {
            $results = array_merge($results, $fork->getResult());
        }

        return $results;
    }
}
