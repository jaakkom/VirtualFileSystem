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

namespace Twifty\VirtualFileSystem\Wrapper;

use Twifty\VirtualFileSystem\Exception\FilesystemException;
use Twifty\VirtualFileSystem\System\Registry;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
trait DirectoryTrait
{
    /**
     * @var \Iterator
     */
    private $dirIterator;

    /**
     * {@inheritdoc}
     */
    public function dir_opendir(string $path, int $options): bool
    {
        $handleError = function (string $message) use ($path) {
            trigger_error(sprintf('Failed to open dir "%s" (%s)', $path, $message), E_USER_WARNING);

            return false;
        };

        try {
            $result = Registry::resolveNode($path);

            if (!$result->exists) {
                return $handleError('File not found');
            }

            if (!$result->directory) {
                return $handleError('Not a directory');
            }

            $node = $result->directory;

            if (!$node->isReadable($result->emulator->getCurrentUserId(), $result->emulator->getCurrentGroupId())) {
                return $handleError('Permission denied');
            }

            $this->dirIterator = $node->getIterator();
        } catch (FilesystemException $e) {
            return $handleError($e->getMessage());
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function dir_closedir(): bool
    {
        if ($this->dirIterator) {
            $this->dirIterator = null;

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function dir_readdir()
    {
        $dir = $this->dirIterator->current();
        if (null === $dir) {
            return false;
        }

        $this->dirIterator->next();

        return $dir->getFilename();
    }

    /**
     * {@inheritdoc}
     */
    public function dir_rewinddir(): bool
    {
        if (isset($this->dirIterator)) {
            $this->dirIterator->rewind();

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir(string $path, int $mode, int $options): bool
    {
        $recursive = (bool) ($options & STREAM_MKDIR_RECURSIVE);
        $handleError = function (string $message) use ($path) {
            trigger_error(sprintf('Failed to create dir "%s" (%s)', $path, $message), E_USER_WARNING);

            return false;
        };

        try {
            $result = Registry::resolveNode($path);
            $node = $result->node;

            if ($result->exists) {
                return $handleError(sprintf('File exists "%s"', $node->getPath()));
            }

            if (!$node->isWritable($uid = $result->emulator->getCurrentUserId(), $gid = $result->emulator->getCurrentGroupId())) {
                return $handleError(sprintf('Permission denied "%s"', $node->getPath()));
            }

            if (!$recursive && 1 < count($result->remainder)) {
                return $handleError(sprintf('File not found "%s"', $node->getPath().'/'.$result->remainder[0]));
            }

            if ($result->factory->createDirectory($node, $result->remainder, $uid, $gid, $mode)) {
                return true;
            }
        } catch (FilesystemException $e) {
            $error = $e->getMessage();
        }

        return $handleError($error ?? 'Unknown reason');
    }

    /**
     * {@inheritdoc}
     */
    public function rmdir(string $path, int $options): bool
    {
        $handleError = function (string $message) use ($path) {
            trigger_error(sprintf('Failed to remove dir "%s" (%s)', $path, $message), E_USER_WARNING);

            return false;
        };

        try {
            $result = Registry::resolveNode($path);
            $node = $result->node;

            if (!$result->exists) {
                return $handleError('File not found');
            }

            if (!$result->directory) {
                return $handleError('Not a directory');
            }

            if ($result->root) {
                return $handleError('Cannot remove root');
            }

            if (!empty($node->getChildren())) {
                return $handleError('Directory not empty');
            }

            $parent = $node->getParent();

            if (!$parent->isWritable($result->emulator->getCurrentUserId(), $result->emulator->getCurrentGroupId())) {
                return $handleError(sprintf('Permission denied "%s"', $parent->getPath()));
            }

            $parent->removeChild($node->getFilename());

            return true;
        } catch (FilesystemException $e) {
            $error = $e->getMessage();
        }

        return $handleError($error);
    }
}
