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

namespace TheLevti\phpfork\EventDispatcher;

use Symfony\Contracts\EventDispatcher\Event as BaseEvent;

/**
 * Holds the signal information.
 */
class SignalEvent extends BaseEvent
{
    /**
     * Event prefix used to construct the full event name, which contains the
     * signal number.
     *
     * @var string PREFIX
     */
    private const PREFIX = 'phpfork.signal.';

    /**
     * The signal being handled.
     *
     * @var int $signo
     */
    private $signo;

    /**
     * If operating systems supports siginfo_t structures, this will be an array
     * of signal information dependent on the signal.
     *
     * @var mixed
     */
    private $signinfo;

    /**
     * Gets the event name for a signal number.
     *
     * @param  int $signo A signal number.
     * @return string The event name for a signal number.
     */
    public static function getEventName(int $signo): string
    {
        return self::PREFIX . $signo;
    }

    /**
     * Constructs a new instance of the Event class.
     *
     * @param int   $signo    The signal being handled.
     * @param mixed $signinfo If operating systems supports siginfo_t
     *                        structures, this will be an array of signal
     *                        information dependent on the signal.
     */
    public function __construct(int $signo, $signinfo)
    {
        $this->signo = $signo;
        $this->signinfo = $signinfo;
    }

    /**
     * Gets the signal being handled.
     *
     * @return int The signal being handled.
     */
    public function getSigno(): int
    {
        return $this->signo;
    }

    /**
     * If operating systems supports siginfo_t structures, this will get the
     * array of signal information dependent on the signal.
     *
     * @return mixed If operating systems supports siginfo_t structures, this
     *               will be an array of signal information dependent on the
     *               signal.
     */
    public function getSigninfo()
    {
        return $this->signinfo;
    }

    /**
     * Gets the event name of the signal being handled.
     *
     * @return string The event name of the signal being handled.
     */
    public function getName(): string
    {
        return static::getEventName($this->getSigno());
    }
}
