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
use Twifty\VirtualFileSystem\Exception\RegistryException;
use Twifty\VirtualFileSystem\Node\Directory;
use Twifty\VirtualFileSystem\Node\Result;
use Twifty\VirtualFileSystem\Node\Root;
use Twifty\VirtualFileSystem\Wrapper\StreamWrapper;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class Registry
{
    const SCHEME_PREFIX = 'vfs';

    private static $registry = [];

    /**
     * Creates a new scheme protocol.
     *
     * @return string
     */
    public static function generateProtocol(): string
    {
        do {
            $protocol = uniqid(self::SCHEME_PREFIX);
        } while (isset(self::$registry[$protocol]));

        return $protocol;
    }

    /**
     * Registers the root with a scheme.
     *
     * @param Root $root
     *
     * @throws RegistryException
     */
    public static function register(Root $root)
    {
        $protocol = $root->getProtocol();

        if (isset(self::$registry[$protocol]) || false === @stream_wrapper_register($protocol, StreamWrapper::class)) {
            throw new RegistryException(sprintf('The %s protocol has already been registered.', $protocol));
        }

        self::$registry[$protocol] = $root;
    }

    /**
     * Unregisters the given protocol.
     *
     * @param string $protocol
     *
     * @throws RegistryException
     */
    public static function unregister(string $protocol)
    {
        if (isset(self::$registry[$protocol])) {
            @stream_wrapper_unregister($protocol);
            unset(self::$registry[$protocol]);
        }
    }

    /**
     * Returns the root node associated with the protocol.
     *
     * @param string $path
     *
     * @throws RegistryException
     *
     * @return Root
     */
    public static function resolveRoot(string $path): Root
    {
        $protocol = static::getProtocol($path);

        if (!isset(self::$registry[$protocol])) {
            throw new RegistryException(sprintf('The %s protocol is not registered.', $protocol));
        }

        return self::$registry[$protocol];
    }

    /**
     * Finds the lowest node in the path.
     *
     * @param string $path
     *
     * @throws NodeException
     * @throws RegistryException
     *
     * @return Result
     */
    public static function resolveNode(string $path): Result
    {
        $root = $node = self::resolveRoot($path);
        $dirs = self::getHierarchy($path);

        $maxDepth = count($dirs);
        $depth = 0;

        while ($depth < $maxDepth) {
            if (!$node->hasChild($dirs[$depth])) {
                break;
            }

            $node = $node->getChild($dirs[$depth]);
            ++$depth;

            if ($depth < $maxDepth && !$node instanceof Directory) {
                throw new NodeException(sprintf('Not a directory "%s"', $node->getPath()));
            }
        }

        return new Result($root->getFactory(), $node, array_slice($dirs, $depth));
    }

    /**
     * Returns the protocol part of the path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function getProtocol(string $path): string
    {
        list($scheme) = explode('://', $path, 2);

        return $scheme;
    }

    /**
     * Returns all path segments as an array.
     *
     * @param string $path
     *
     * @return string[]
     */
    public static function getHierarchy(string $path): array
    {
        list(, $path) = explode('://', $path, 2);

        $hierarchy = [];

        foreach (explode('/', $path) as $seg) {
            if ('..' === $seg) {
                array_pop($hierarchy);
            } elseif ('.' !== $seg && '' !== $seg) {
                $hierarchy[] = $seg;
            }
        }

        return $hierarchy;
    }
}
