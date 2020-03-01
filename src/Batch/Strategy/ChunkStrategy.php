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
    /**
     * @var int $forks
     */
    private $forks;

    /**
     * @var bool $preserveKeys
     */
    private $preserveKeys;

    public function __construct(int $forks = 3, bool $preserveKeys = false)
    {
        $this->forks = $forks;
        $this->preserveKeys = $preserveKeys;
    }

    public function createBatches($data): iterable
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
