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
class AccessMode
{
    /**
     * Opens file for reading.
     */
    const MODE_READ = 1;

    /**
     * Opens file for writing.
     */
    const MODE_WRITE = 2;

    /**
     * Opens file for read and write.
     *
     * Same as ( MODE_READ | MODE_WRITE )
     */
    const MODE_READWRITE = self::MODE_READ | self::MODE_WRITE;

    /**
     * Allow existing files to be opened.
     */
    const MODE_OPEN = 4;

    /**
     * Allow missing files to be created.
     */
    const MODE_CREATE = 8;

    /**
     * Creates a missing file before opening.
     *
     * Same as ( MODE_OPEN | MODE_CREATE )
     */
    const MODE_OPENCREATE = self::MODE_OPEN | self::MODE_CREATE;

    /**
     * Sets the file pointer to the end of existing data.
     */
    const MODE_SEEK_START = 16;

    /**
     * Sets the file pointer to the start of existing data.
     */
    const MODE_SEEK_END = 32;

    /**
     * Truncates an existing file.
     *
     * Same as ( MODE_SEEK_START | MODE_SEEK_END )
     */
    const MODE_TRUNCATE = self::MODE_SEEK_START | self::MODE_SEEK_END;

    /**
     * Appends all writes.
     */
    const MODE_APPEND = 64;

    /*
     * 'r'	Open for reading only; place the file pointer at the beginning of the file.
     * 'r+'	Open for reading and writing; place the file pointer at the beginning of the file.
     * 'w'	Open for writing only; place the file pointer at the beginning of the file and
     *      truncate the file to zero length. If the file does not exist, attempt to create it.
     * 'w+'	Open for reading and writing; place the file pointer at the beginning of the file
     *      and truncate the file to zero length. If the file does not exist, attempt to create it.
     * 'a'	Open for writing only; place the file pointer at the end of the file. If the file
     *      does not exist, attempt to create it. In this mode, fseek() has no effect, writes are
     *      always appended.
     * 'a+'	Open for reading and writing; place the file pointer at the end of the file. If the
     *      file does not exist, attempt to create it. In this mode, fseek() only affects the reading
     *      position, writes are always appended.
     * 'x'	Create and open for writing only; place the file pointer at the beginning of the file.
     *      If the file already exists, the fopen() call will fail by returning FALSE and generating
     *      an error of level E_WARNING. If the file does not exist, attempt to create it. This is
     *      equivalent to specifying O_EXCL|O_CREAT flags for the underlying open(2) system call.
     * 'x+'	Create and open for reading and writing; otherwise it has the same behavior as 'x'.
     * 'c'	Open the file for writing only. If the file does not exist, it is created. If it
     *      exists, it is neither truncated (as opposed to 'w'), nor the call to this function fails
     *      (as is the case with 'x'). The file pointer is positioned on the beginning of the file.
     *      This may be useful if it's desired to get an advisory lock (see flock()) before
     *      attempting to modify the file, as using 'w' could truncate the file before the lock was
     *      obtained (if truncation is desired, ftruncate() can be used after the lock is requested).
     * 'c+'	Open the file for reading and writing; otherwise it has the same behavior as 'c'.
     */

    /**
     * Parses the mode string passed to `fopen()`.
     *
     * @param string $mode
     *
     * @return int
     */
    public static function parse(string $mode): int
    {
        $flags = false !== strstr($mode, '+') ? self::MODE_READWRITE : 0;
        $openmode = str_replace(['t', 'b', 'e', '+'], '', $mode);

        switch ($openmode) {
            case 'r':
                $flags |= self::MODE_OPEN;
                $flags |= self::MODE_READ;
                $flags |= self::MODE_SEEK_START;
                break;
            case 'w':
                $flags |= self::MODE_OPENCREATE;
                $flags |= self::MODE_WRITE;
                $flags |= self::MODE_TRUNCATE;
                break;
            case 'a':
                $flags |= self::MODE_OPENCREATE;
                $flags |= self::MODE_WRITE;
                $flags |= self::MODE_SEEK_END;
                $flags |= self::MODE_APPEND;
                break;
            case 'x':
                $flags |= self::MODE_CREATE;
                $flags |= self::MODE_WRITE;
                $flags |= self::MODE_SEEK_START;
                break;
            case 'c':
                $flags |= self::MODE_OPENCREATE;
                $flags |= self::MODE_WRITE;
                $flags |= self::MODE_SEEK_START;
                break;
            default:
                $flags = 0;
        }

        return $flags;
    }
}
