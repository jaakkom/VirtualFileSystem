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

use Twifty\VirtualFileSystem\System\Emulator;
use Twifty\VirtualFileSystem\System\Factory;

/**
 * Simple class to hold a search status.
 *
 * @author Owen Parry <waldermort@gmail.com>
 */
class Result
{
    /**
     * @var Factory
     */
    public $factory;

    /**
     * @var Emulator
     */
    public $emulator;

    /**
     * @var NodeInterface
     */
    public $node;

    /**
     * @var File|null
     */
    public $file;

    /**
     * @var Directory|null
     */
    public $directory;

    /**
     * @var Root|null
     */
    public $root;

    /**
     * @var Symlink|null
     */
    public $symlink;

    /**
     * @var string[]
     */
    public $remainder;

    /**
     * @var bool
     */
    public $exists;

    /**
     * Constructor.
     *
     * @param Factory       $factory
     * @param NodeInterface $node
     * @param array         $remainder
     */
    public function __construct(Factory $factory, NodeInterface $node, array $remainder)
    {
        $this->factory = $factory;
        $this->emulator = $factory->getEmulator();
        $this->node = $node;
        $this->remainder = $remainder;
        $this->exists = empty($remainder);

        switch (get_class($node)) {
            case Root::class:
                $this->root = $node;
            case Directory::class:
                $this->directory = $node;
                break;
            case File::class:
                $this->file = $node;
                break;
            case Symlink::class:
                $this->symlink = $node;
                break;
        }
    }
}
