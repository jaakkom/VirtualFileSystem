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

namespace Twifty\VirtualFileSystem\Node;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
interface NodeInterface
{
    /**
     * Returns node absolute path.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Checks if the given or current user has read permissions.
     *
     * @param int $uid
     * @param int $gid
     *
     * @return bool
     */
    public function isReadable(int $uid, int $gid): bool;

    /**
     * Checks if the given or current user has write permissions.
     *
     * @param int $uid
     * @param int $gid
     *
     * @return bool
     */
    public function isWritable(int $uid, int $gid): bool;

    /**
     * Returns file mode.
     *
     * @return int
     */
    public function getMode(): int;

    /**
     * Changes access to file.
     *
     * @param int $mode
     *
     * @return NodeInterface
     */
    public function setMode(int $mode): NodeInterface;

    /**
     * Returns ownership.
     *
     * @return int
     */
    public function getOwner(): int;

    /**
     * Changes ownership.
     *
     * @param int $uid
     *
     * @return NodeInterface
     */
    public function setOwner(int $uid): NodeInterface;

    /**
     * Returns group ownership.
     *
     * @return int
     */
    public function getGroup(): int;

    /**
     * Changes group ownership.
     *
     * @param int $gid
     *
     * @return NodeInterface
     */
    public function setGroup(int $gid): NodeInterface;

    /**
     * Returns the containing directory.
     *
     * @return Directory
     */
    public function getParent(): Directory;

    /**
     * Returns the Node filename.
     *
     * @return string
     */
    public function getFilename(): string;

    /**
     * Modifies the filename.
     *
     * @param string $filename
     *
     * @return NodeInterface
     */
    public function setFilename(string $filename): NodeInterface;

    /**
     * Returns node path.
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Returns the byte size of any contents.
     *
     * @return int
     */
    public function getSize(): int;

    /**
     * Returns last access time.
     *
     * @return int
     */
    public function getAccessedTime(): int;

    /**
     * Sets last access time.
     *
     * @param int $time
     *
     * @return NodeInterface
     */
    public function setAccessedTime(int $time): NodeInterface;

    /**
     * Returns last modification time.
     *
     * @return int
     */
    public function getModifiedTime(): int;

    /**
     * Sets last modification time.
     *
     * @param int $time
     *
     * @return NodeInterface
     */
    public function setModifiedTime(int $time): NodeInterface;

    /**
     * Returns last inode change time (chown etc.).
     *
     * @return int
     */
    public function getChangedTime(): int;

    /**
     * Sets last inode change time.
     *
     * @param int $time
     *
     * @return NodeInterface
     */
    public function setChangedTime(int $time): NodeInterface;
}
