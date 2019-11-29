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

use Symfony\Component\EventDispatcher\EventDispatcher as BaseClass;

/**
 * Extends the core event dispatcher with signal handling capabilities. Add and
 * remove signal listeners or dispatch a signal directly.
 */
class EventDispatcher extends BaseClass implements EventDispatcherInterface
{
    use EventDispatcherTrait;
}
