<?php

declare(strict_types=1);

/*
 * This file is part of Twifty Virtual Filesystem.
 *
 * (c) Owen Parry <waldermort@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Twifty\VirtualFileSystem\Content;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
interface LockableInterface
{
    /**
     * Acquire a shared lock (multiple readers, single writer).
     */
    const LOCK_SHARED = LOCK_SH;

    /**
     * Acquire an exclusive lock (single writer).
     */
    const LOCK_EXCLUSIVE = LOCK_EX;

    /**
     * Release a lock.
     */
    const LOCK_UNLOCK = LOCK_UN;

    /**
     * Acquire lock without blocking.
     */
    const LOCK_NON_BLOCKING = LOCK_NB;

    /**
     * Locks or unlocks the file content.
     *
     * @param LockHolderInterface $resource
     * @param int                 $operation
     *
     * @return bool
     */
    public function lock(LockHolderInterface $resource, int $operation): bool;
}
