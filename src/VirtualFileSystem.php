<?php

/*
 * This file is part of Twifty Virtual Filesystem.
 *
 * (c) Owen Parry <waldermort@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Twifty\VirtualFileSystem;

use Twifty\VirtualFileSystem\Inject\FunctionInjector;
use Twifty\VirtualFileSystem\Inject\InjectableInterface;
use Twifty\VirtualFileSystem\Node\Directory;
use Twifty\VirtualFileSystem\Node\File;
use Twifty\VirtualFileSystem\Node\NodeInterface;
use Twifty\VirtualFileSystem\Node\Root;
use Twifty\VirtualFileSystem\Node\Symlink;
use Twifty\VirtualFileSystem\System\Emulator;
use Twifty\VirtualFileSystem\System\Factory;
use Twifty\VirtualFileSystem\System\Registry;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class VirtualFileSystem
{
    /**
     * @var Root
     */
    private $root;

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
     * @param array         $structure
     * @param Emulator|null $emulator
     * @param callable|null $filenameValidator
     */
    public function __construct(array $structure = null, Emulator $emulator = null, callable $filenameValidator = null)
    {
        if (null === $emulator && function_exists('posix_getuid') && function_exists('posix_getpwuid')) {
            $user = posix_getpwuid(posix_getuid());
            $emulator = new Emulator($user['name']);
        }

        $this->factory = new Factory($emulator, $filenameValidator);
        $this->protocol = Registry::generateProtocol();
        $this->root = new Root($this->protocol, $this->factory);

        Registry::register($this->root);

        if (null !== $structure) {
            $this->createStructure($structure);
        }
    }

    /**
     * Remoces wrapper registered for scheme associated with FileSystem instance.
     */
    public function __destruct()
    {
        Registry::unregister($this->protocol);
    }

    /**
     * Returns the Root instance.
     *
     * @return Root
     */
    public function root(): Root
    {
        return $this->root;
    }

    /**
     * Prefixes the path with the current scheme.
     *
     * @param string $path
     *
     * @return string
     */
    public function path(string $path): string
    {
        return $this->protocol.'://'.ltrim($path, '/');
    }

    /**
     * Creates and returns a directory instance.
     *
     * @param string      $path
     * @param string|null $user
     * @param string|null $group
     * @param int|null    $mode
     *
     * @return Directory
     */
    public function createDirectory(string $path, string $user = null, string $group = null, int $mode = null): Directory
    {
        return $this->factory->createDirectory($this->root(), explode('/', ltrim($path, '/')), $user, $group, $mode);
    }

    /**
     * Creates and returns a file instance.
     *
     * @param string      $path
     * @param string|null $data
     * @param string|null $user
     * @param string|null $group
     * @param int|null    $mode
     *
     * @return File
     */
    public function createFile(string $path, string $data = null, string $user = null, string $group = null, int $mode = null): File
    {
        return $this->factory->createFile($this->root(), explode('/', ltrim($path, '/')), $data, $user, $group, $mode);
    }

    /**
     * Creates and returns a symlink instance.
     *
     * @param string               $path
     * @param NodeInterface|string $target
     *
     * @return Symlink
     */
    public function createSymlink(string $path, $target, string $user = null, string $group = null, int $mode = null): Symlink
    {
        if (is_string($target)) {
            $result = Registry::resolveNode(strpos($target, '://') ? $target : $this->protocol.'://'.$target);

            if (!$result->exists) {
                throw new \InvalidArgumentException(sprintf('Failed to resolve path "%s" to a node', $target));
            }

            $target = $result->node;
        }

        return $this->factory->createSymlink($this->root(), explode('/', ltrim($path, '/')), $target, $user, $group, $mode);
    }

    /**
     * Creates a directory structure from an array.
     *
     * @param array $structure
     *
     * @return Root
     */
    public function createStructure(array $structure): Root
    {
        return $this->addStructure($this->root(), $structure);
    }

    /**
     * Creates a stream aware function within the namespace of another class.
     *
     * @param string              $namespace
     * @param InjectableInterface $thunk
     *
     * @return bool
     */
    public function createFunction(string $namespace, InjectableInterface $thunk): bool
    {
        return FunctionInjector::inject($namespace, $thunk);
    }

    /**
     * Recursively adds array entries as files and directories.
     *
     * @param Directory $where
     * @param array     $structure
     *
     * @return Directory
     */
    protected function addStructure(Directory $where, array $structure)
    {
        foreach ($structure as $name => $entry) {
            if ($entry instanceof NodeInterface) {
                $where->addChild($entry);
            } elseif (is_int($name)) {
                $this->factory->createFile($where, [$entry]);
            } elseif (is_array($entry)) {
                if ($where->hasChild($name)) {
                    $this->addStructure($where->getChild($name), $entry);
                } else {
                    $this->addStructure($this->factory->createDirectory($where, [$name]), $entry);
                }
            } else {
                $this->factory->createFile($where, [$name], $entry);
            }
        }

        return $where;
    }
}
