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
trait LockableTrait
{
    /**
     * @var LockHolderInterface|null
     */
    protected $exclusiveLock;

    /**
     * @var \SplObjectStorage|null
     */
    protected $sharedLock;

    /**
     * Locks or unlocks to the given resource.
     *
     * @param LockHolderInterface $resource
     * @param int                 $operation
     *
     * @return bool
     */
    public function lock(LockHolderInterface $resource, int $operation): bool
    {
        // All operations are non-blocking
        if ((LOCK_NB & $operation) === LOCK_NB) {
            $operation -= LOCK_NB;
        }

        switch ($operation) {
            case LOCK_EX:
                return $this->setExclusiveLock($resource);
            case LOCK_SH:
                return $this->addSharedLock($resource);
            case LOCK_UN:
                return $this->unlock($resource);
        }

        return false;
    }

    /**
     * Removes a lock from the resource.
     *
     * @param LockHolderInterface $resource
     *
     * @return bool
     */
    public function unlock(LockHolderInterface $resource): bool
    {
        if (LOCK_UN !== $this->getLockType($resource)) {
            if ($this->exclusiveLock === $resource) {
                $this->exclusiveLock = null;
            } else {
                $this->getSharedLockContainer()->detach($resource);
            }

            return true;
        }

        return false;
    }

    /**
     * Returns the type of lock held by the resource.
     *
     * @param LockHolderInterface $resource
     *
     * @return int
     */
    public function getLockType(LockHolderInterface $resource): int
    {
        if ($this->exclusiveLock === $resource) {
            return LOCK_EX;
        }

        return (isset($this->sharedLock) && $this->sharedLock->contains($resource)) ? LOCK_SH : LOCK_UN;
    }

    /**
     * Applies an exclusive lock to the resource.
     *
     * @param LockHolderInterface $resource
     *
     * @return bool
     */
    protected function setExclusiveLock(LockHolderInterface $resource): bool
    {
        $container = $this->getSharedLockContainer();
        $shared = $container->count();

        if (isset($this->exclusiveLock) && $this->exclusiveLock !== $resource) {
            return false;
        }

        if (1 === $shared && $container->contains($resource)) {
            $container->detach($resource);
        } elseif (0 !== $shared) {
            return false;
        }

        $this->exclusiveLock = $resource;

        return true;
    }

    /**
     * Applies a shared lock to the resource.
     *
     * @param LockHolderInterface $resource
     *
     * @return bool
     */
    protected function addSharedLock(LockHolderInterface $resource): bool
    {
        // An exclusive lock by the resource is downgraded
        if ($this->exclusiveLock === $resource) {
            $this->exclusiveLock = null;
        } elseif (isset($this->exclusiveLock)) {
            return false;
        }

        $this->getSharedLockContainer()->attach($resource);

        return true;
    }

    /**
     * Returns the shared lock container.
     *
     * @return \SplObjectStorage
     */
    private function getSharedLockContainer(): \SplObjectStorage
    {
        if (!isset($this->sharedLock)) {
            $this->sharedLock = new \SplObjectStorage();
        }

        return $this->sharedLock;
    }
}
