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
class Symlink extends AbstractNode
{
    /**
     * @var NodeInterface
     */
    protected $target;

    /**
     * Constructor.
     *
     * @param string                                       $filename
     * @param \Twifty\VirtualFileSystem\Node\NodeInterface $target
     * @param int                                          $owner
     * @param int                                          $group
     * @param int|null                                     $mode
     */
    public function __construct(string $filename, NodeInterface $target, int $owner, int $group, int $mode = null)
    {
        parent::__construct($filename, $owner, $group, $mode);

        $this->target = $target;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): int
    {
        return strlen($this->target->getPath());
    }

    /**
     * @return NodeInterface
     */
    public function getTarget(): NodeInterface
    {
        return $this->target;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFileMode(): int
    {
        return 0120000;
    }
}
