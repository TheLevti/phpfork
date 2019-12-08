<?php

/*
 * This file is part of Spork, an OpenSky project.
 *
 * (c) OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spork\Deferred;

interface DeferredInterface extends PromiseInterface
{
    /**
     * Notifies the promise of progress. Any arguments will be passed along to
     * the callbacks.
     *
     * @throws \LogicException   If the promise is not pending
     * @return DeferredInterface The current promise
     */
    public function notify();

    /**
     * Marks the current promise as successful.
     *
     * Calls "always" callbacks first, followed by "done" callbacks. Any
     * arguments will be passed along to the callbacks
     *
     * @throws \LogicException   If the promise was previously rejected
     * @return DeferredInterface The current promise
     */
    public function resolve();

    /**
     * Marks the current promise as failed.
     *
     * Calls "always" callbacks first, followed by "fail" callbacks. Any
     * arguments will be passed along to the callbacks.
     *
     * @throws \LogicException   If the promise was previously resolved
     * @return DeferredInterface The current promise
     */
    public function reject();
}
