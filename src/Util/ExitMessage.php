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
    /** @var mixed $result */
    private $result;

    /** @var mixed $output */
    private $output;

    /** @var \TheLevti\phpfork\Util\Error|null $error */
    private $error;

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     * @return void
     */
    public function setResult($result): void
    {
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param mixed $output
     * @return void
     */
    public function setOutput($output): void
    {
        $this->output = $output;
    }

    public function getError(): ?Error
    {
        return $this->error;
    }

    public function setError(Error $error): void
    {
        $this->error = $error;
    }

    public function serialize(): string
    {
        return serialize([
            $this->result,
            $this->output,
            $this->error,
        ]);
    }

    /**
     * @param string $serialized
     * @return void
     */
    public function unserialize($serialized)
    {
        list(
            $this->result,
            $this->output,
            $this->error
        ) = unserialize($serialized);
    }
}
