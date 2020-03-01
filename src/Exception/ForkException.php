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

namespace TheLevti\phpfork\Exception;

use RuntimeException;
use TheLevti\phpfork\Util\Error;

/**
 * Turns an error passed through shared memory into an exception.
 */
class ForkException extends RuntimeException
{
    /** @var string $name */
    private $name;

    /** @var int $pid */
    private $pid;

    /** @var \TheLevti\phpfork\Util\Error|null $error */
    private $error;

    public function __construct(string $name, int $pid, ?Error $error = null)
    {
        $this->name = $name;
        $this->pid = $pid;
        $this->error = $error;

        if ($error) {
            if (__CLASS__ === $error->getClass()) {
                parent::__construct(sprintf('%s via "%s" fork (%d)', $error->getMessage(), $name, $pid));
            } else {
                parent::__construct(sprintf(
                    '%s (%d) thrown in "%s" fork (%d): "%s" (%s:%d)',
                    $error->getClass(),
                    $error->getCode(),
                    $name,
                    $pid,
                    $error->getMessage(),
                    $error->getFile(),
                    $error->getLine()
                ));
            }
        } else {
            parent::__construct(sprintf('An unknown error occurred in "%s" fork (%d)', $name, $pid));
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getError(): ?Error
    {
        return $this->error;
    }
}
