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

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
interface ContentInterface
{
    /**
     * Opens the underlying resource.
     *
     * @param int $flags
     *
     * @return ContentInterface
     */
    public function open(int $flags): ContentInterface;

    /**
     * Reads $bytes number of bytes from the current file position.
     *
     * The returned string length may be less than requested
     *
     * @param int $bytes
     *
     * @return string
     */
    public function read(int $bytes): string;

    /**
     * Writes a string to the current file position.
     *
     * @param string $data
     *
     * @return int
     */
    public function write(string $data): int;

    /**
     * Returns the current file pointer position.
     *
     * @return int
     */
    public function tell(): int;

    /**
     * Resizes the file.
     *
     * The file pointer is not moved, even if it points to beyond the end of the
     * resized file. seek() MUST be called to move it.
     *
     * @param int $size
     *
     * @return bool
     */
    public function truncate(int $size): bool;

    /**
     * Moves the file pointer.
     *
     * @param int $offset
     * @param int $whence
     *
     * @return bool
     */
    public function seek(int $offset, int $whence): bool;

    /**
     * Checks if the file pointer is at the end of the file.
     *
     * @return bool
     */
    public function eof(): bool;

    /**
     * Writes any internal buffers to the underlying resource.
     *
     * @return bool
     */
    public function flush(): bool;

    /**
     * @param int $castAs
     *
     * @return resource|false
     */
    public function cast(int $castAs);

    /**
     * @param int $option
     * @param int $arg1
     * @param int $arg2
     *
     * @return bool
     */
    public function setOption(int $option, int $arg1, int $arg2): bool;

    /**
     * Returns a partial associative stat array.
     *
     * @return array
     */
    public function stat(): array;
}
