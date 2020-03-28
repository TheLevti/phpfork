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

namespace TheLevti\phpfork\Deferred;

interface PromiseInterface
{
    public const STATE_PENDING = 'pending';
    public const STATE_RESOLVED = 'resolved';
    public const STATE_REJECTED = 'rejected';

    /**
     * Returns the promise state.
     *
     *  * PromiseInterface::STATE_PENDING:  The promise is still open
     *  * PromiseInterface::STATE_RESOLVED: The promise completed successfully
     *  * PromiseInterface::STATE_REJECTED: The promise failed
     *
     * @return string A promise state constant
     */
    public function getState(): string;

    /**
     * Adds a callback to be called upon progress.
     *
     * @param callable $progress The callback
     *
     * @return \TheLevti\phpfork\Deferred\PromiseInterface The current promise
     */
    public function progress(callable $progress): self;

    /**
     * Adds a callback to be called whether the promise is resolved or rejected.
     *
     * The callback will be called immediately if the promise is no longer
     * pending.
     *
     * @param callable $always The callback
     *
     * @return \TheLevti\phpfork\Deferred\PromiseInterface The current promise
     */
    public function always(callable $always): self;

    /**
     * Adds a callback to be called when the promise completes successfully.
     *
     * The callback will be called immediately if the promise state is resolved.
     *
     * @param callable $done The callback
     *
     * @return \TheLevti\phpfork\Deferred\PromiseInterface The current promise
     */
    public function done(callable $done): self;

    /**
     * Adds a callback to be called when the promise fails.
     *
     * The callback will be called immediately if the promise state is rejected.
     *
     * @param callable $fail The callback
     *
     * @return \TheLevti\phpfork\Deferred\PromiseInterface The current promise
     */
    public function fail(callable $fail): self;

    /**
     * Adds done and fail callbacks.
     *
     * @param callable $done The done callback
     * @param callable|null $fail The fail callback
     *
     * @return \TheLevti\phpfork\Deferred\PromiseInterface The current promise
     */
    public function then(callable $done, callable $fail = null): self;
}
