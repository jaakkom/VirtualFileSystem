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

use Twifty\VirtualFileSystem\Content\ContentInterface;
use Twifty\VirtualFileSystem\Content\LockableInterface;
use Twifty\VirtualFileSystem\Content\LockableTrait;
use Twifty\VirtualFileSystem\Content\StringContentTrait;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class File extends AbstractNode implements ContentInterface, LockableInterface
{
    use LockableTrait;
    use StringContentTrait {
        open as protected openContent;
    }

    public function __construct(string $filename, string $data, int $owner, int $group, int $mode = null)
    {
        parent::__construct($filename, $owner, $group, $mode);

        $this->initializeData($data);
    }

    /**
     * Overrides trait method to return a value.
     *
     * Specifying the return type within the trait will break the contract.
     *
     * @param int $flags
     *
     * @return ContentInterface
     */
    public function open(int $flags): ContentInterface
    {
        $this->openContent($flags);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): int
    {
        return $this->getDataLength();
    }

    /**
     * {@inheritdoc}
     */
    public function setOption(int $option, int $arg1, int $arg2): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function stat(): array
    {
        return [
            'mode' => $this->getMode(),
            'uid' => $this->getOwner(),
            'gid' => $this->getGroup(),
            'size' => $this->getSize(),
            'atime' => $this->getAccessedTime(),
            'mtime' => $this->getModifiedTime(),
            'ctime' => $this->getChangedTime(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function cast(int $castAs)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFileMode(): int
    {
        return 0100000;
    }

    /**
     * Implementation of trait method.
     *
     * @param int $time
     */
    protected function updateAccessedTime(int $time)
    {
        $this->setAccessedTime($time);
    }

    /**
     * Implementation of trait method.
     *
     * @param int $time
     */
    protected function updateModifiedTime(int $time)
    {
        $this->setModifiedTime($time);
    }
}
