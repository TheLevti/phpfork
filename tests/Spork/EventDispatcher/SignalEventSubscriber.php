<?php

/*
 * This file is part of the thelevti/spork package.
 *
 * (c) Petr Levtonov <petr@levtonov.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spork\EventDispatcher;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SignalEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            SignalEvent::getEventName(SIGUSR1) => ['onSigusr1', -128],
        ];
    }

    public function onSigusr1()
    {
        // Do nothing.
    }
}
