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

use Phpfork\Exception\UnexpectedTypeException;

class CallbackStrategy extends AbstractStrategy
{
    private $callback;

    public function __construct($callback)
    {
        if (!is_callable($callback)) {
            throw new UnexpectedTypeException($callback, 'callable');
        }

        $this->callback = $callback;
    }

    public function createBatches($data)
    {
        return call_user_func($this->callback, $data);
    }
}
