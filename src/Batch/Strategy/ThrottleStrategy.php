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

namespace TheLevti\phpfork\Batch\Strategy;

use TheLevti\phpfork\Util\ThrottleIterator;

class ThrottleStrategy implements StrategyInterface
{
    /**
     * @var \TheLevti\phpfork\Batch\Strategy\StrategyInterface $delegate
     */
    private $delegate;

    /**
     * @var int $threshold
     */
    private $threshold;

    public function __construct(StrategyInterface $delegate, int $threshold = 3)
    {
        $this->delegate = $delegate;
        $this->threshold = $threshold;
    }

    public function createBatches($data): iterable
    {
        $batches = $this->delegate->createBatches($data);

        // wrap each batch in the throttle iterator
        foreach ($batches as &$batch) {
            $batch = new ThrottleIterator($batch, $this->threshold);
        }

        return $batches;
    }

    public function createRunner($batch, $callback): callable
    {
        return $this->delegate->createRunner($batch, $callback);
    }
}
