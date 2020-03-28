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

class WrappedEventDispatcher implements SignalEventDispatcherInterface
{
    use SignalEventDispatcherTrait;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $delegate
     */
    private $delegate;

    public function __construct(EventDispatcherInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    public function dispatch($event, string $eventName = null): object
    {
        return call_user_func([$this->delegate, 'dispatch'], $event, $eventName);
    }

    /**
     * @param string $eventName
     * @param callable $listener
     * @param int $priority
     * @return mixed
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        return $this->delegate->addListener($eventName, $listener, $priority);
    }

    /**
     * @param EventSubscriberInterface $subscriber
     * @return mixed
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->delegate->addSubscriber($subscriber);
    }

    /**
     * @param string $eventName
     * @param callable $listener
     * @return mixed
     */
    public function removeListener($eventName, $listener)
    {
        return $this->delegate->removeListener($eventName, $listener);
    }

    /**
     * @param EventSubscriberInterface $subscriber
     * @return mixed
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->delegate->removeSubscriber($subscriber);
    }

    /**
     * @param string|null $eventName
     * @return array<string,callable>
     */
    public function getListeners($eventName = null)
    {
        return $this->delegate->getListeners($eventName);
    }

    /**
     * @param string $eventName
     * @param callable $listener
     * @return int|null
     */
    public function getListenerPriority($eventName, $listener)
    {
        return $this->delegate->getListenerPriority($eventName, $listener);
    }

    /**
     * @param string|null $eventName
     * @return bool
     */
    public function hasListeners($eventName = null)
    {
        return $this->delegate->hasListeners($eventName);
    }
}
