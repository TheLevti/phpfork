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

use Serializable;

class Error implements Serializable
{
    private $class;
    private $message;
    private $file;
    private $line;
    private $code;

    public static function fromException(\Exception $e)
    {
        $flat = new self();
        $flat->setClass(get_class($e));
        $flat->setMessage($e->getMessage());
        $flat->setFile($e->getFile());
        $flat->setLine($e->getLine());
        $flat->setCode($e->getCode());

        return $flat;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setClass($class)
    {
        $this->class = $class;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file)
    {
        $this->file = $file;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function setLine($line)
    {
        $this->line = $line;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function serialize()
    {
        return serialize([
            $this->class,
            $this->message,
            $this->file,
            $this->line,
            $this->code,
        ]);
    }

    public function unserialize($str)
    {
        list(
            $this->class,
            $this->message,
            $this->file,
            $this->line,
            $this->code
        ) = unserialize($str);
    }
}
