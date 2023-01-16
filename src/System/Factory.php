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

namespace Twifty\VirtualFileSystem\System;

use Twifty\VirtualFileSystem\Exception\NodeException;
use Twifty\VirtualFileSystem\Node\Directory;
use Twifty\VirtualFileSystem\Node\File;
use Twifty\VirtualFileSystem\Node\NodeInterface;
use Twifty\VirtualFileSystem\Node\Symlink;

/**
 * A collection of common methods for adding nodes to the tree.
 *
 * These methods are intended to be used by the StreamWrapper only.
 *
 * @author Owen Parry <waldermort@gmail.com>
 */
class Factory
{
    /**
     * @var callable
     */
    private $filenameValidator;

    /**
     * @var Emulator
     */
    private $emulator;

    /**
     * Constructor.
     *
     * @param Emulator|null $emulator
     * @param callable      $filenameValidator
     */
    public function __construct(Emulator $emulator = null, callable $filenameValidator = null)
    {
        $this->emulator = $emulator ?? new Emulator('guest');
        $this->filenameValidator = $filenameValidator;
    }

    /**
     * Returns the emulator instance.
     *
     * @return Emulator
     */
    public function getEmulator(): Emulator
    {
        return $this->emulator;
    }

    /**
     * Creates the missing directories and adds a file to the tree.
     *
     * @param Directory       $where
     * @param string[]        $names
     * @param string|null     $data
     * @param string|int|null $user
     * @param string|int|null $group
     * @param int             $mode
     *
     * @return File
     */
    public function createFile(Directory $where, array $names, string $data = null, $user = null, $group = null, int $mode = null): File
    {
        $uid = $this->getUserId($user);
        $gid = $this->getGroupId($group);

        $file = new File(self::finalName($names), $data ?? '', $uid, $gid, $mode);

        return $this->createTree($where, $file, self::parentNames($names), $uid, $gid, $mode);
    }

    /**
     * Creates the missing directories and adds a directory to the tree.
     *
     * @param Directory       $where
     * @param string[]        $names
     * @param string|int|null $user
     * @param string|int|null $group
     * @param int             $mode
     *
     * @return Directory
     */
    public function createDirectory(Directory $where, array $names, $user = null, $group = null, int $mode = null): Directory
    {
        $uid = $this->getUserId($user);
        $gid = $this->getGroupId($group);

        $dir = new Directory(self::finalName($names), $uid, $gid, $mode);

        return $this->createTree($where, $dir, self::parentNames($names), $uid, $gid, $mode);
    }

    /**
     * Creates the missing directories and adds symlink to the tree.
     *
     * @param Directory       $where
     * @param string[]        $names
     * @param NodeInterface   $target
     * @param string|int|null $user
     * @param string|int|null $group
     * @param int             $mode
     *
     * @return Symlink
     */
    public function createSymlink(Directory $where, array $names, NodeInterface $target, $user = null, $group = null, int $mode = null): Symlink
    {
        $uid = $this->getUserId($user);
        $gid = $this->getGroupId($group);

        $link = new Symlink(self::finalName($names), $target, $uid, $gid, $mode);

        return $this->createTree($where, $link, self::parentNames($names), $uid, $gid, $mode);
    }

    /**
     * Checks the filename for illegal characters.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function validateFilename(string $filename): bool
    {
        $validate = $this->filenameValidator ?? function (string $filename) {
            return !in_array($filename, ['.', '..'], true) && false === strpos($filename, '/');
        };

        return (bool) $validate($filename);
    }

    /**
     * Creates the missing directories and add $child to the tree.
     *
     * @param Directory     $where
     * @param NodeInterface $leaf
     * @param string[]      $names
     * @param int           $uid
     * @param int           $gid
     * @param int           $mode
     *
     * @return NodeInterface
     */
    protected function createTree(Directory $where, NodeInterface $leaf, \Traversable $names, int $uid, int $gid, int $mode = null): NodeInterface
    {
        foreach ($names as $name) {
            if (!$where->hasChild($name)) {
                $child = new Directory($name, $uid, $gid, $mode);
                $where->addChild($child);
            } else {
                $child = $where->getChild($name);
                if (!$child instanceof Directory) {
                    throw new NodeException('Not a directory');
                }
            }

            $where = $child;
        }

        $where->addChild($leaf);

        return $leaf;
    }

    /**
     * Returns the emulated user ID.
     *
     * @param null|mixed $user
     *
     * @return int
     */
    protected function getUserId($user = null): int
    {
        return null !== $user ? $this->getEmulator()->getUserId($user) : $this->getEmulator()->getCurrentUserId();
    }

    /**
     * Returns the emulated group ID.
     *
     * @param null|mixed $group
     *
     * @return int
     */
    protected function getGroupId($group = null): int
    {
        return null !== $group ? $this->getEmulator()->getGroupId($group) : $this->getEmulator()->getCurrentGroupId();
    }

    /**
     * Validates and yields each but the last name in the array.
     *
     * @param string[] $names
     *
     * @throws NodeException
     *
     * @return string[]
     */
    protected function parentNames(array $names): \Traversable
    {
        foreach (array_slice($names, 0, -1) as $name) {
            if (!$this->validateFilename($name)) {
                throw new NodeException('Not a valid filename');
            }

            yield $name;
        }
    }

    /**
     * Validates and returns the last name in the array.
     *
     * @param string[] $names
     *
     * @throws NodeException
     *
     * @return string
     */
    protected function finalName(array $names): string
    {
        $name = end($names);

        if (!$this->validateFilename($name)) {
            throw new NodeException('Not a valid filename');
        }

        return $name;
    }
}
