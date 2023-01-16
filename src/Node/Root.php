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

use Twifty\VirtualFileSystem\System\Factory;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class Root extends Directory
{
    /**
     * @var string
     */
    private $protocol;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * Constructor.
     *
     * @param string  $protocol
     * @param Factory $factory
     */
    public function __construct(string $protocol, Factory $factory)
    {
        $this->protocol = $protocol;
        $this->factory = $factory;

        AbstractNode::__construct('', 0, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->getProtocol().'://';
    }

    /**
     * Returns the stream protocol.
     *
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * Returns the factory instance.
     *
     * @return Factory
     */
    public function getFactory(): Factory
    {
        return $this->factory;
    }

    /**
     * Defined to prevent setting parent on Root.
     *
     * @param Directory|null $parent
     *
     * @throws \LogicException
     *
     * @return NodeInterface
     */
    protected function setParent(Directory $parent = null): NodeInterface
    {
        if (null !== $parent) {
            throw new \LogicException('A Root node cannot have a parent.');
        }

        return $this; // @codeCoverageIgnore
    }
}
