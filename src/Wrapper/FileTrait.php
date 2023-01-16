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
use Twifty\VirtualFileSystem\Node\Symlink;
use Twifty\VirtualFileSystem\System\Registry;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
trait FileTrait
{
    /**
     * Unlike `mv src dst`, the dst directory MUST exist.
     *
     * {@inheritdoc}
     */
    public function rename(string $srcPath, string $dstPath): bool
    {
        $handleError = function (string $message) use ($srcPath) {
            trigger_error(sprintf('Failed to rename "%s" (%s)', $srcPath, $message), E_USER_WARNING);

            return false;
        };

        try {
            $srcResult = Registry::resolveNode($srcPath);
            $dstResult = Registry::resolveNode($dstPath);

            if (!$srcResult->exists) {
                return $handleError(sprintf('File not found "%s"', $srcPath));
            }

            $srcParent = $srcResult->node->getParent();
            $dstParent = null;

            $srcName = $srcResult->node->getFilename();
            $dstName = null;

            switch (count($dstResult->remainder)) {
                case 0: // destination exists
                    $dstParent = $dstResult->node->getParent();
                    if ($dstResult->directory) {
                        if (!$srcResult->directory) {
                            return $handleError(sprintf('Is a directory "%s"', $dstPath));
                        }
                        if (!empty($dstResult->node->getChildren())) {
                            return $handleError(sprintf('Directory not empty "%s"', $dstPath));
                        }
                    } elseif ($srcResult->directory) {
                        return $handleError(sprintf('Not a directory "%s"', $dstPath));
                    }
                    // safe to replace
                    $dstName = $dstResult->node->getFilename();
                    break;
                case 1: // destination doesn't exist, but parent does
                    $dstName = end($dstResult->remainder);
                    if (!$srcResult->factory->validateFilename($dstName)) {
                        return $handleError(sprintf('Not a valid filename "%s"', $dstName));
                    }
                    $dstParent = $dstResult->node;
                    $dstNode = null;
                    break;
                default: //destination parent doesn't exist
                    return $handleError(sprintf('File not found "%s"', dirname($dstPath)));
            }

            $emulator = $dstResult->emulator;
            if (!$srcParent->isWritable($emulator->getCurrentUserId(), $emulator->getCurrentGroupId())) {
                return $handleError(sprintf('Permission denied "%s"', $srcParent->getPath()));
            }

            if (!$dstParent->isWritable($emulator->getCurrentUserId(), $emulator->getCurrentGroupId())) {
                return $handleError(sprintf('Permission denied "%s"', $dstParent->getPath()));
            }

            $time = time();
            $node = $srcParent->removeChild($srcName)
                ->setFilename($dstName)
                ->setOwner($emulator->getCurrentUserId())
                ->setGroup($emulator->getCurrentGroupId())
                ->setChangedTime($time)
                ->setModifiedTime($time) // todo - does this change?
            ;

            clearstatcache(true, $srcPath);
            clearstatcache(true, $dstPath);

            $dstParent->addChild($node, true);

            return true;
        } catch (FilesystemException $e) {
            $error = $e->getMessage();
        }

        return $handleError($error);
    }

    /**
     * {@inheritdoc}
     */
    public function unlink(string $path): bool
    {
        $handleError = function (string $message) use ($path) {
            trigger_error(sprintf('Failed to unlink "%s" (%s)', $path, $message), E_USER_WARNING);

            return false;
        };

        try {
            $result = Registry::resolveNode($path);

            if (!$result->exists) {
                return $handleError('No such file or directory');
            }

            if ($result->directory) {
                return $handleError('Is a directory');
            }

            $emulator = $result->emulator;
            if (!$result->node->getParent()->isWritable($emulator->getCurrentUserId(), $emulator->getCurrentGroupId())) {
                return $handleError('Permission denied');
            }

            $result->node->getParent()->removeChild($result->node);

            clearstatcache(true, $path);
        } catch (FilesystemException $e) {
            return $handleError($e->getMessage());
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stream_metadata(string $path, int $option, $value): bool
    {
        $handleError = function (string $message) use ($path) {
            trigger_error(sprintf('Failed to touch "%s": %s', $path, $message), E_USER_WARNING);

            return false;
        };

        try {
            $result = Registry::resolveNode($path);

            if (STREAM_META_TOUCH === $option) {
                if (!$result->exists) {
                    if (1 !== count($result->remainder)) {
                        return $handleError('Directory not found');
                    }

                    $node = $result->factory->createFile($result->node, $result->remainder);
                } else {
                    $node = $result->node;
                }

                $time = time();
                $node->setAccessedTime($time);
                $node->setModifiedTime($time);

                clearstatcache(true, $path);

                return true;
            }

            if (!$result->exists) {
                return $handleError('File not found');
            }

            $emulator = $result->emulator;
            $node = $result->node;

            switch ($option) {
                case STREAM_META_OWNER_NAME: // chown
                case STREAM_META_OWNER: // chown
                    // Only root user may change file ownership
                    if (!$emulator->isSuperUser()) {
                        return $handleError('Permission denied');
                    }

                    $uid = $emulator->getUserId($value);
                    $node->setOwner($uid);

                    clearstatcache(true, $path);

                    return true;
                case STREAM_META_GROUP_NAME: // chgrp
                case STREAM_META_GROUP: // chgrp
                    $gid = $emulator->getGroupId($value);
                    $uid = $emulator->getCurrentUserId();

                    // Root can change to any group, users can only change to groups to which they are a member
                    if (!$emulator->isSuperUser() && ($node->getOwner() !== $uid || !in_array($gid, $emulator->getUserGroups($uid), false))) {
                        return $handleError('Permission denied');
                    }

                    $node->setGroup($gid);

                    clearstatcache(true, $path);

                    return true;
                case STREAM_META_ACCESS: // chmod
                    while ($node instanceof Symlink) {
                        $node = $node->getTarget();
                    }

                    // Only available to root or owner
                    if (!$emulator->isSuperUser() && $node->getOwner() !== $emulator->getCurrentUserId()) {
                        return $handleError('Permission denied');
                    }

                    $node->setMode($value);

                    clearstatcache(true, $path);

                    return true;
                default:
                    $error = sprintf('Unknown option "%d"', $option);
            }
        } catch (FilesystemException $e) {
            $error = $e->getMessage();
        }

        return $handleError($error);
    }

    /**
     * {@inheritdoc}
     */
    public function url_stat(string $path, int $flags)
    {
        $handleError = function (string $message) use ($path, $flags) {
            if (STREAM_URL_STAT_QUIET !== ($flags & STREAM_URL_STAT_QUIET)) {
                trigger_error(sprintf('Failed to get stats "%s": %s', $path, $message), E_USER_WARNING);
            }

            return false;
        };

        try {
            $result = Registry::resolveNode($path);

            if (!$result->exists) {
                return $handleError('No such file or directory');
            }

            $node = $result->node;

            if (STREAM_URL_STAT_LINK !== ($flags & STREAM_URL_STAT_LINK)) {
                while ($node instanceof Symlink) {
                    $node = $node->getTarget();
                }
            }

            return $this->createStats([
                'mode' => $node->getMode(),
                'uid' => $node->getOwner(),
                'gid' => $node->getGroup(),
                'atime' => $node->getAccessedTime(),
                'mtime' => $node->getModifiedTime(),
                'ctime' => $node->getChangedTime(),
                'size' => $node->getSize(),
            ]);
        } catch (FilesystemException $e) {
            $error = $e->getMessage();
        }

        return $handleError($error);
    }

    abstract protected function createStats(array $stats): array;
}
