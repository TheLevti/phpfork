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

namespace Phpfork\Batch\Strategy;

use Phpfork\Util\ThrottleIterator;

class ThrottleStrategy implements StrategyInterface
{
    private $delegate;
    private $threshold;

    public function __construct(StrategyInterface $delegate, $threshold = 3)
    {
        $this->delegate = $delegate;
        $this->threshold = $threshold;
    }

    public function createBatches($data)
    {
        $batches = $this->delegate->createBatches($data);

        // wrap each batch in the throttle iterator
        foreach ($batches as $i => $batch) {
            $batches[$i] = new ThrottleIterator($batch, $this->threshold);
        }

        return $batches;
    }

    public function createRunner($batch, $callback)
    {
        return $this->delegate->createRunner($batch, $callback);
    }
}
