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

namespace Twifty\VirtualFileSystem\Content;

use Twifty\VirtualFileSystem\Exception\ContentException;

/**
 * Implementation of ContentInterface using a string buffer.
 *
 * Outer classes will need to implement the empty methods.
 *
 * @author Owen Parry <waldermort@gmail.com>
 */
trait SeekableContentTrait
{
    /**
     * @var bool
     */
    private $readable;

    /**
     * @var bool
     */
    private $writable;

    /**
     * @var bool
     */
    private $append;

    /**
     * @var int
     */
    private $offset;

    /**
     * {@inheritdoc}
     */
    public function open(int $flags)
    {
        $this->readable = (bool) ($flags & AccessMode::MODE_READ);
        $this->writable = (bool) ($flags & AccessMode::MODE_WRITE);
        $this->append = (bool) ($flags & AccessMode::MODE_APPEND);
        $this->offset = 0;

        $length = $this->getDataLength();
        $time = time();

        $tmp = $flags & AccessMode::MODE_TRUNCATE;

        if (AccessMode::MODE_TRUNCATE === ($flags & AccessMode::MODE_TRUNCATE)) {
            $this->writeData('', 0, $length);
            $this->updateModifiedTime($time);
        } elseif ($flags & AccessMode::MODE_SEEK_END) {
            $this->offset = $length;
        }

        $this->updateAccessedTime($time);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function read(int $bytes): string
    {
        if (!$this->readable) {
            throw new ContentException('Operation not permitted');
        }

        $data = $this->readData($this->offset, $bytes);
        $this->offset += strlen($data);

        $this->updateAccessedTime(time());

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $data): int
    {
        if (!$this->writable) {
            throw new ContentException('Operation not permitted');
        }

        if (0 === $bytes = strlen($data)) {
            return 0;
        }

        $length = $this->getDataLength();

        if ($this->append) {
            $this->writeData($data, $length);
        } elseif ($this->offset > $length) {
            $padLength = $this->offset - $length;

            $this->writeData(str_repeat("\0", $padLength), $length);
            $this->writeData($data, $this->offset);

            $this->offset += $bytes;
        } else {
            $this->writeData($data, $this->offset, $bytes);

            $this->offset += $bytes;
        }

        $time = time();

        $this->updateModifiedTime($time);
        $this->updateAccessedTime($time);

        return $bytes;
    }

    /**
     * {@inheritdoc}
     */
    public function truncate(int $size): bool
    {
        if ($this->append || !$this->writable) {
            return false;
        }

        $length = $this->getDataLength();

        if ($size > $length) {
            $this->writeData(str_repeat("\0", $size - $length), $length);
        } elseif ($size < $length) {
            $this->writeData('', $size, $length - $size);
        }

        $time = time();

        $this->updateModifiedTime($time);
        $this->updateAccessedTime($time);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function seek(int $offset, int $whence): bool
    {
        // Files opened with 'a' flag are non seekable, but those opened with 'a+'
        // can seek for reads but not for writes.

        switch ($whence) {
            case SEEK_CUR:
                $offset += $this->offset;
                break;
            case SEEK_END:
                $offset += $this->getDataLength();
                break;
            case SEEK_SET:
                break;
            default:
                return false;
        }

        if ($offset < 0 || (!$this->writable && $offset > $this->getDataLength())) {
            return false;
        }

        $this->offset = $offset;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        return $this->offset >= $this->getDataLength();
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): int
    {
        return $this->offset;
    }

    /**
     * Returns the size in bytes of the content.
     *
     * @return int
     */
    abstract protected function getDataLength(): int;

    /**
     * Reads $length number of bytes from $offset.
     *
     * @param int $offset
     * @param int $length
     *
     * @return string
     */
    abstract protected function readData(int $offset, int $length): string;

    /**
     * Performs the actual writing of data.
     *
     * @param string $data
     * @param int    $offset
     * @param int    $replace
     */
    abstract protected function writeData(string $data, int $offset, int $replace = 0): void;

    /**
     * Notifies outer class of content modifications.
     *
     * @param int $time
     */
    protected function updateModifiedTime(int $time)
    {
    }

    /**
     * Notifies outer class of content access.
     *
     * @param int $time
     */
    protected function updateAccessedTime(int $time)
    {
    }
}
