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

use TheLevti\phpfork\Exception\UnexpectedTypeException;

/**
 * Creates the batch iterator using array_chunk().
 */
class ChunkStrategy extends AbstractStrategy
{
    private $forks;
    private $preserveKeys;

    public function __construct($forks = 3, $preserveKeys = false)
    {
        $this->forks = $forks;
        $this->preserveKeys = $preserveKeys;
    }

    public function createBatches($data)
    {
        if (!is_array($data) && !$data instanceof \Traversable) {
            throw new UnexpectedTypeException($data, 'array or Traversable');
        }

        if ($data instanceof \Traversable) {
            $data = iterator_to_array($data);
        }

        $size = (int)ceil(count($data) / $this->forks);

        return array_chunk($data, $size, $this->preserveKeys);
    }
}
