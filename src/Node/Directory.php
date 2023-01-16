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

use Twifty\VirtualFileSystem\Exception\FileExistsException;
use Twifty\VirtualFileSystem\Exception\NodeException;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class Directory extends AbstractNode implements \IteratorAggregate
{
    /**
     * @var AbstractNode
     */
    protected $children = [];

    /**
     * {@inheritdoc}
     */
    public function getSize(): int
    {
        return 0;
    }

    /**
     * Adds child Node.
     *
     * @param AbstractNode $node
     * @param bool         $overwrite
     *
     * @throws FileExistsException
     *
     * @return NodeInterface The added node
     */
    public function addChild(AbstractNode $node, bool $overwrite = false): NodeInterface
    {
        $name = $node->getFilename();

        if (!$overwrite && $this->hasChild($name)) {
            throw new NodeException(sprintf('File exists "%s"', $this->getPath().'/'.$name));
        }

        $this->children[$name] = $node;
        $this->updateTimestamps();

        return $node->setParent($this);
    }

    /**
     * Checks if a child exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasChild(string $name): bool
    {
        return isset($this->children[$name]);
    }

    /**
     * Returns a child instance.
     *
     * @param string $name
     *
     * @throws NodeException
     *
     * @return NodeInterface
     */
    public function getChild(string $name): NodeInterface
    {
        if (!$this->hasChild($name)) {
            throw new NodeException(sprintf('File not found "%s"', $this->getPath().'/'.$name));
        }

        return $this->children[$name];
    }

    /**
     * Removes child Node.
     *
     * @param NodeInterface|string $child
     *
     * @return NodeInterface The removed child
     */
    public function removeChild($child): NodeInterface
    {
        if (is_string($child)) {
            $child = $this->getChild($child);
        } elseif (!$child instanceof NodeInterface || !in_array($child, $this->children, true)) {
            throw new NodeException('File not found');
        }

        unset($this->children[$child->getFilename()]);

        $child->setParent(null);

        return $child;
    }

    /**
     * Changes the name of a child node.
     *
     * @param string $old
     * @param string $new
     *
     * @throws NodeException
     */
    public function renameChild(string $old, string $new)
    {
        if ($this->hasChild($new)) {
            throw new NodeException(sprintf('File exists "%s"', $this->getPath().'/'.$new));
        }

        $child = $this->getChild($old);

        $this->removeChild($child);

        try {
            $child->setFilename($new);
        } catch (\LogicException $e) {
            throw $e;
        } finally {
            $this->addChild($child);
        }

        $this->updateTimestamps();
    }

    /**
     * Returns all children.
     *
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFileMode(): int
    {
        return 0040000;
    }

    /**
     * Updates the ctime and mtime timestamps.
     */
    private function updateTimestamps()
    {
        $time = time();
        $this->setChangedTime($time);
        $this->setModifiedTime($time);
    }
}
