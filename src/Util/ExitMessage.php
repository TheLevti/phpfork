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

class ExitMessage implements \Serializable
{
    private $result;
    private $output;
    private $error;

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function getError()
    {
        return $this->error;
    }

    public function setError(Error $error)
    {
        $this->error = $error;
    }

    public function serialize()
    {
        return serialize([
            $this->result,
            $this->output,
            $this->error,
        ]);
    }

    public function unserialize($str)
    {
        list(
            $this->result,
            $this->output,
            $this->error
        ) = unserialize($str);
    }
}
