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

use Twifty\VirtualFileSystem\Content\LockHolderInterface;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
interface StreamWrapperInterface extends LockHolderInterface
{
    /**
     * Close directory handle.
     *
     * @return bool
     */
    public function dir_closedir(): bool;

    /**
     * Open directory handle.
     *
     * This method is called in response to opendir().
     *
     * @param string $path    specifies the URL that was passed to opendir()
     * @param int    $options whether or not to enforce safe_mode (0x04)
     *
     * @return bool
     */
    public function dir_opendir(string $path, int $options): bool;

    /**
     * Read entry from directory handle.
     *
     * @return string|false should return string representing the next filename, or FALSE if there is no next file
     */
    public function dir_readdir();

    /**
     * Rewind directory handle.
     *
     * This method is called in response to rewinddir().
     *
     * @return bool
     */
    public function dir_rewinddir(): bool;

    /**
     * Create a directory.
     *
     * @param string $path    directory which should be created
     * @param int    $mode    the value passed to mkdir()
     * @param int    $options a bitwise mask of values, such as STREAM_MKDIR_RECURSIVE
     *
     * @return bool
     */
    public function mkdir(string $path, int $mode, int $options): bool;

    /**
     * Renames a file or directory.
     *
     * This method is called in response to rename().
     *
     * @param string $path_from
     * @param string $path_to
     *
     * @return bool
     */
    public function rename(string $path_from, string $path_to): bool;

    /**
     * Removes a directory.
     *
     * @param string $path
     * @param int    $options
     *
     * @return bool
     */
    public function rmdir(string $path, int $options): bool;

    /**
     * Retrieve the underlaying resource.
     *
     * @param int $cast_as
     *
     * @return resource|false
     */
    public function stream_cast(int $cast_as);

    /**
     * Close a resource.
     */
    public function stream_close();

    /**
     * Tests for end-of-file on a file pointer.
     *
     * @return bool
     */
    public function stream_eof(): bool;

    /**
     * Flushes the output.
     *
     * @return bool
     */
    public function stream_flush(): bool;

    /**
     * Advisory file locking.
     *
     * @param mode $operation
     *
     * @return bool
     */
    public function stream_lock($operation): bool;

    /**
     * Change stream metadata.
     *
     * @param string $path
     * @param int    $option
     * @param mixed  $value
     *
     * @return bool
     */
    public function stream_metadata(string $path, int $option, $value): bool;

    /**
     * Opens file or URL.
     *
     * @param string $path
     * @param string $mode
     * @param int    $options
     * @param string|null &$opened_path
     *
     * @return bool
     */
    public function stream_open(string $path, string $mode, int $options, string &$opened_path = null): bool;

    /**
     * Read from stream.
     *
     * @param int $count
     *
     * @return string
     */
    public function stream_read(int $count): string;

    /**
     * Seeks to specific location in a stream.
     *
     * @param int $offset
     * @param int $whence = SEEK_SET
     *
     * @return bool
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool;

    /**
     * Change stream options.
     *
     * $option - One of:
     *      STREAM_OPTION_BLOCKING (The method was called in response to stream_set_blocking())
     *      STREAM_OPTION_READ_TIMEOUT (The method was called in response to stream_set_timeout())
     *      STREAM_OPTION_WRITE_BUFFER (The method was called in response to stream_set_write_buffer())
     *
     * $arg1 - If option is:
     *      STREAM_OPTION_BLOCKING: requested blocking mode (1 meaning block 0 not blocking).
     *      STREAM_OPTION_READ_TIMEOUT: the timeout in seconds.
     *      STREAM_OPTION_WRITE_BUFFER: buffer mode (STREAM_BUFFER_NONE or STREAM_BUFFER_FULL).
     *
     * $arg2 - If option is:
     *      STREAM_OPTION_BLOCKING: This option is not set.
     *      STREAM_OPTION_READ_TIMEOUT: the timeout in microseconds.
     *      STREAM_OPTION_WRITE_BUFFER: the requested buffer size.
     *
     * @param int $option
     * @param int $arg1
     * @param int $arg2
     *
     * @return bool
     */
    public function stream_set_option(int $option, int $arg1, int $arg2): bool;

    /**
     * Retrieve information about a file resource.
     *
     * @return array
     */
    public function stream_stat(): array;

    /**
     * Retrieve the current position of a stream.
     *
     * @return int
     */
    public function stream_tell(): int;

    /**
     * Truncate stream.
     *
     * @param int $new_size
     *
     * @return bool
     */
    public function stream_truncate(int $new_size): bool;

    /**
     * Write to stream.
     *
     * @param string $data
     *
     * @return int
     */
    public function stream_write(string $data): int;

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function unlink(string $path): bool;

    /**
     * Retrieve information about a file.
     *
     * @param string $path
     * @param int    $flags
     *
     * @return array|false
     */
    public function url_stat(string $path, int $flags);
}
