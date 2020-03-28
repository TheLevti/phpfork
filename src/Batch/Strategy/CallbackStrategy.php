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

class CallbackStrategy extends AbstractStrategy
{
    /**
     * @var callable $callback
     */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function createBatches($data): iterable
    {
        return call_user_func($this->callback, $data);
    }
}
