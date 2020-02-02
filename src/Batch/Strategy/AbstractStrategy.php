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

use Phpfork\Batch\BatchRunner;

abstract class AbstractStrategy implements StrategyInterface
{
    public function createRunner($batch, $callback)
    {
        return new BatchRunner($batch, $callback);
    }
}
