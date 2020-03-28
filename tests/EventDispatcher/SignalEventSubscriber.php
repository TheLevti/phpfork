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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SignalEventSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string,array<int,string|int>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SignalEvent::getEventName(SIGUSR1) => ['onSigusr1', -128],
        ];
    }

    public function onSigusr1(): void
    {
        // Do nothing.
    }
}
