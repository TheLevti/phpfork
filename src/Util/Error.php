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

namespace TheLevti\phpfork\Util;

use Exception;
use Serializable;

class Error implements Serializable
{
    /** @var string $class */
    private $class;

    /** @var string $message */
    private $message;

    /** @var string $file */
    private $file;

    /** @var int $line */
    private $line;

    /** @var mixed $code */
    private $code;

    public static function fromException(Exception $exception): self
    {
        return new self(
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getCode()
        );
    }

    /**
     * @param string $class
     * @param string $message
     * @param string $file
     * @param int $line
     * @param mixed $code
     */
    public function __construct(string $class, string $message, string $file, int $line, $code)
    {
        $this->class = $class;
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        $this->code = $code;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    public function serialize(): string
    {
        return serialize([
            $this->class,
            $this->message,
            $this->file,
            $this->line,
            $this->code,
        ]);
    }

    /**
     * @param string $serialized
     * @return void
     */
    public function unserialize($serialized)
    {
        list(
            $this->class,
            $this->message,
            $this->file,
            $this->line,
            $this->code
        ) = unserialize($serialized);
    }
}
