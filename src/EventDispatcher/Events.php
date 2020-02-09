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

final class Events
{
    /**
     * Dispatched in the parent process before forking.
     */
    public const PRE_FORK = 'phpfork.pre_fork';

    /**
     * Notifies in the child process after forking.
     */
    public const POST_FORK = 'phpfork.post_fork';
}
