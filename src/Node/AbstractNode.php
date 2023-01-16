<?php

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
abstract class AbstractNode implements NodeInterface
{
    const DEFAULT_MODE = 0755;

    const MODE_USER_READ = 0400;
    const MODE_USER_WRITE = 0200;

    const MODE_GROUP_READ = 0040;
    const MODE_GROUP_WRITE = 0020;

    const MODE_PUBLIC_READ = 0004;
    const MODE_PUBLIC_WRITE = 0002;

    protected $filename;
    protected $parent;

    protected $owner;
    protected $group;

    protected $atime;
    protected $mtime;
    protected $ctime;

    protected $mode;

    /**
     * Constructor.
     *
     * @param string   $filename
     * @param int      $owner
     * @param int      $group
     * @param int|null $mode
     */
    public function __construct(string $filename, int $owner, int $group, int $mode = null)
    {
        $time = time();

        $this->setFilename($filename);
        $this->setOwner($owner);
        $this->setGroup($group);
        $this->setMode($mode ?? self::DEFAULT_MODE);
        $this->setAccessedTime($time);
        $this->setModifiedTime($time);
        $this->setChangedTime($time);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->getPath();
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(int $uid, int $gid): bool
    {
        if ($this->owner === $uid) {
            $check = self::MODE_USER_READ;
        } elseif ($this->group === $gid) {
            $check = self::MODE_GROUP_READ;
        } else {
            $check = self::MODE_PUBLIC_READ;
        }

        return (bool) ($this->mode & $check);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(int $uid, int $gid): bool
    {
        if ($this->owner === $uid) {
            $check = self::MODE_USER_WRITE;
        } elseif ($this->group === $gid) {
            $check = self::MODE_GROUP_WRITE;
        } else {
            $check = self::MODE_PUBLIC_WRITE;
        }

        return (bool) ($this->mode & $check);
    }

    /**
     * {@inheritdoc}
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * {@inheritdoc}
     */
    public function setMode(int $mode): NodeInterface
    {
        $this->mode = $mode | $this->getFileMode();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwner(): int
    {
        return $this->owner;
    }

    /**
     * {@inheritdoc}
     */
    public function setOwner(int $uid): NodeInterface
    {
        $this->owner = $uid;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup(): int
    {
        return $this->group;
    }

    /**
     * {@inheritdoc}
     */
    public function setGroup(int $gid): NodeInterface
    {
        $this->group = $gid;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): Directory
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * {@inheritdoc}
     */
    public function setFilename(string $filename): NodeInterface
    {
        if (false !== strpos($filename, '/')) {
            throw new \LogicException(sprintf('Filenames cannot contain the "/" character ("%s")', $filename));
        }

        $this->filename = $filename;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        if (!isset($this->parent)) {
            $prefix = '::';
        } else {
            $prefix = $this->getParent()->getPath().($this->parent instanceof Root ? '' : '/');
        }

        return $prefix.$this->getFilename();
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessedTime(): int
    {
        return $this->atime;
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessedTime(int $time): NodeInterface
    {
        $this->atime = $time;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getModifiedTime(): int
    {
        return $this->mtime;
    }

    /**
     * {@inheritdoc}
     */
    public function setModifiedTime(int $time): NodeInterface
    {
        $this->mtime = $time;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChangedTime(): int
    {
        return $this->ctime;
    }

    /**
     * {@inheritdoc}
     */
    public function setChangedTime(int $time): NodeInterface
    {
        $this->ctime = $time;

        return $this;
    }

    /**
     * Sets parent Node.
     *
     * @param Directory|null $parent
     */
    protected function setParent(Directory $parent = null): NodeInterface
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Returns the type of system file as an integer.
     *
     * @return int
     */
    abstract protected function getFileMode(): int;
}
