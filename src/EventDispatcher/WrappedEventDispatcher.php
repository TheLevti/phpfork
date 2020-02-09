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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Wraps another event dispatcher, adding signal handling capabilities to it.
 */
class WrappedEventDispatcher implements SignalEventDispatcherInterface
{
    use SignalEventDispatcherTrait;

    /**
     * The wrapped event dispatcher.
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $delegate
     */
    private $delegate;

    /**
     * Constructs a new instance of the WrappedEventDispatcher class.
     *
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $delegate
     *            The wrapped event dispatcher.
     */
    public function __construct(EventDispatcherInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($event, string $eventName = null): object
    {
        return call_user_func([$this->delegate, 'dispatch'], $event, $eventName);
    }

    /**
     * {@inheritDoc}
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        return $this->delegate->addListener($eventName, $listener, $priority);
    }

    /**
     * {@inheritDoc}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->delegate->addSubscriber($subscriber);
    }

    /**
     * {@inheritDoc}
     */
    public function removeListener($eventName, $listener)
    {
        return $this->delegate->removeListener($eventName, $listener);
    }

    /**
     * {@inheritDoc}
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->delegate->removeSubscriber($subscriber);
    }

    /**
     * {@inheritDoc}
     */
    public function getListeners($eventName = null)
    {
        return $this->delegate->getListeners($eventName);
    }

    /**
     * {@inheritDoc}
     */
    public function getListenerPriority($eventName, $listener)
    {
        return $this->delegate->getListenerPriority($eventName, $listener);
    }

    /**
     * {@inheritDoc}
     */
    public function hasListeners($eventName = null)
    {
        return $this->delegate->hasListeners($eventName);
    }
}
