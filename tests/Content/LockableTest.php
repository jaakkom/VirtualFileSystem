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

namespace Twifty\VirtualFileSystem\Test\Content;

use PHPUnit\Framework\TestCase;
use Twifty\VirtualFileSystem\Content\LockableTrait;
use Twifty\VirtualFileSystem\Content\LockHolderInterface;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class LockableTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LockableTrait
     */
    protected $lock;

    /**
     * @var LockHolderInterface
     */
    protected $key1;

    /**
     * @var LockHolderInterface
     */
    protected $key2;

    protected function setUp()
    {
        $this->lock = $this->getMockForTrait(LockableTrait::class);
        $this->key1 = $this->createMock(LockHolderInterface::class);
        $this->key2 = $this->createMock(LockHolderInterface::class);
    }

    protected function tearDown()
    {
        $this->lock = null;
        $this->key1 = null;
        $this->key2 = null;
    }

    public function testExclusiveLock()
    {
        $this->assertTrue($this->lock->lock($this->key1, LOCK_EX | LOCK_NB));

        $this->assertSame(LOCK_EX, $this->lock->getLockType($this->key1));
        $this->assertSame(LOCK_UN, $this->lock->getLockType($this->key2));

        $this->assertTrue($this->lock->lock($this->key1, LOCK_UN));
        $this->assertFalse($this->lock->lock($this->key1, LOCK_UN));

        $this->assertSame(LOCK_UN, $this->lock->getLockType($this->key1));
    }

    public function testSharedLock()
    {
        $this->assertTrue($this->lock->lock($this->key1, LOCK_SH | LOCK_NB));
        $this->assertTrue($this->lock->lock($this->key2, LOCK_SH));

        $this->assertSame(LOCK_SH, $this->lock->getLockType($this->key1));
        $this->assertSame(LOCK_SH, $this->lock->getLockType($this->key2));

        $this->assertTrue($this->lock->lock($this->key1, LOCK_UN));
        $this->assertTrue($this->lock->lock($this->key2, LOCK_UN));

        $this->assertSame(LOCK_UN, $this->lock->getLockType($this->key1));
        $this->assertSame(LOCK_UN, $this->lock->getLockType($this->key2));
    }

    public function testLockAlreadyExclusive()
    {
        $this->assertTrue($this->lock->lock($this->key1, LOCK_EX | LOCK_NB));

        $this->assertFalse($this->lock->lock($this->key2, LOCK_EX));
        $this->assertFalse($this->lock->lock($this->key2, LOCK_SH));

        $this->assertTrue($this->lock->lock($this->key1, LOCK_SH), 'Exclusive is downgradable');
        $this->assertTrue($this->lock->lock($this->key2, LOCK_SH));
    }

    public function testLockAlreadyShared()
    {
        $this->assertTrue($this->lock->lock($this->key1, LOCK_SH | LOCK_NB));

        $this->assertFalse($this->lock->lock($this->key2, LOCK_EX), 'Lock is in shared mode');

        $this->assertTrue($this->lock->lock($this->key1, LOCK_EX), 'Shared is upgradable');
    }

    public function testInvalidLockOperation()
    {
        $this->assertFalse($this->lock->lock($this->key1, 8 | LOCK_NB));
    }
}
