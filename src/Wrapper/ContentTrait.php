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

use Twifty\VirtualFileSystem\Content\AccessMode;
use Twifty\VirtualFileSystem\Content\ContentInterface;
use Twifty\VirtualFileSystem\Exception\FilesystemException;
use Twifty\VirtualFileSystem\Node\Directory;
use Twifty\VirtualFileSystem\Node\Symlink;
use Twifty\VirtualFileSystem\System\Registry;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
trait ContentTrait
{
    /**
     * @var ContentInterface
     */
    private $fileContent;

    /**
     * {@inheritdoc}
     */
    public function stream_cast(int $cast_as)
    {
        // TODO - try returning a php memory stream
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function stream_open(string $path, string $mode, int $options, string &$opened_path = null): bool
    {
        $fileFlags = AccessMode::parse($mode);

        $handleError = function (string $message) use ($path, $options) {
            if ($options & STREAM_REPORT_ERRORS) {
                $error = sprintf('Failed to open stream "%s": %s', $path, $message);
                trigger_error($error, E_USER_WARNING);
            }

            return false;
        };

        if (0 === $fileFlags) {
            return $handleError(sprintf('Illegal mode: "%s"', $mode));
        }

        $isCreate = (bool) ($fileFlags & AccessMode::MODE_CREATE);
        $isWrite = (bool) ($fileFlags & AccessMode::MODE_WRITE);
        $isRead = (bool) ($fileFlags & AccessMode::MODE_READ);

        try {
            $result = Registry::resolveNode($path);

            if (!$result->exists) {
                if (!$isCreate) {
                    return $handleError('File not found');
                }

                if (1 !== count($result->remainder) || !$result->directory) {
                    return $handleError('Directory not found');
                }

                if (!$result->directory->isWritable($result->emulator->getCurrentUserId(), $result->emulator->getCurrentGroupId())) {
                    return $handleError('Permission denied');
                }

                $node = $result->factory->createFile($result->directory, $result->remainder);
            } else {
                $node = $result->node;
            }

            // Resolve symlink to actual file
            while ($node instanceof Symlink) {
                $node = $node->getTarget();
            }

            // Note some systems allow opening directories (read-only)
            if ($node instanceof Directory) {
                return $handleError('Is a directory');
            }

            if ($isWrite && !$node->isWritable($result->emulator->getCurrentUserId(), $result->emulator->getCurrentGroupId())) {
                return $handleError('Permission denied');
            }

            if ($isRead && !$node->isReadable($result->emulator->getCurrentUserId(), $result->emulator->getCurrentGroupId())) {
                return $handleError('Permission denied');
            }

            $this->fileContent = $node->open($fileFlags);

            if ($options & STREAM_USE_PATH) {
                $opened_path = $node->getPath();
            }
        } catch (FilesystemException $e) {
            return $handleError($e->getMessage());
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stream_close()
    {
        $this->fileContent->lock($this, LOCK_UN);
        $this->fileContent = null;
    }

    /**
     * {@inheritdoc}
     */
    public function stream_eof(): bool
    {
        return $this->fileContent->eof();
    }

    /**
     * {@inheritdoc}
     */
    public function stream_flush(): bool
    {
        return $this->fileContent->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function stream_lock($operation): bool
    {
        return $this->fileContent->lock($this, $operation);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_read(int $count): string
    {
        return $this->fileContent->read($count);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return $this->fileContent->seek($offset, $whence);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_set_option(int $option, int $arg1, int $arg2): bool
    {
        //        switch ($option) {
//            case STREAM_OPTION_BLOCKING:
//                // break omitted
//
//            case STREAM_OPTION_READ_TIMEOUT:
//                // break omitted
//
//            case STREAM_OPTION_WRITE_BUFFER:
//                // break omitted
//
//            default:
//                // nothing to do here
//        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function stream_tell(): int
    {
        return $this->fileContent->tell();
    }

    /**
     * {@inheritdoc}
     */
    public function stream_truncate(int $new_size): bool
    {
        return $this->fileContent->truncate($new_size);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_write(string $data): int
    {
        return $this->fileContent->write($data);
    }

    /**
     * {@inheritdoc}
     */
    public function stream_stat(): array
    {
        return $this->createStats($this->fileContent->stat());
    }

    abstract protected function createStats(array $stats): array;
}
