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

namespace Twifty\VirtualFileSystem\Test\Wrapper;

use Twifty\VirtualFileSystem\Node\File;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class ContentTest extends AbstractWrapperTest
{
    public function testOpen()
    {
        $file = $this->factory->createFile($this->root, ['var', 'file'], 'test string');
        $link = $this->factory->createSymlink($this->root, ['var', 'link'], $file);
        $realPath = '';

        $this->assertTrue($this->wrapper->stream_open('vfs-test://var/link', 'r+', STREAM_REPORT_ERRORS | STREAM_USE_PATH, $realPath));
        $this->assertSame($file->getPath(), $realPath);

        $this->assertTrue($this->wrapper->stream_lock(LOCK_EX));
        $this->assertSame('test', $this->wrapper->stream_read(4));
        $this->assertSame(4, $this->wrapper->stream_tell());
        $this->assertFalse($this->wrapper->stream_eof());

        $this->assertTrue($this->wrapper->stream_truncate(0));
        $this->assertTrue($this->wrapper->stream_seek(0, SEEK_END));
        $this->assertTrue($this->wrapper->stream_eof());
        $this->assertSame(3, $this->wrapper->stream_write('foo'));
        $this->assertTrue($this->wrapper->stream_flush());

        $this->assertFalse($this->wrapper->stream_cast(0), 'Not implemented');
        $this->assertFalse($this->wrapper->stream_set_option(0, 0, 0), 'Not implemented');

        $this->assertNotEmpty($stat = $this->wrapper->stream_stat());

        $this->assertSame($file->getMode(), $stat['mode']);
        $this->assertSame($file->getOwner(), $stat['uid']);
        $this->assertSame($file->getGroup(), $stat['gid']);
        $this->assertSame($file->getAccessedTime(), $stat['atime']);
        $this->assertSame($file->getModifiedTime(), $stat['mtime']);
        $this->assertSame($file->getChangedTime(), $stat['ctime']);
        $this->assertSame($file->getSize(), $stat['size']);

        $this->wrapper->stream_close();

        $this->assertNull($this->lastError, 'Sanity check');
    }

    public function testCreate()
    {
        $var = $this->factory->createDirectory($this->root, ['var']);
        $realPath = '';

        $this->assertTrue($this->wrapper->stream_open('vfs-test://var/file', 'w+', STREAM_REPORT_ERRORS | STREAM_USE_PATH, $realPath));

        $this->assertSame('vfs-test://var/file', $realPath);
        $this->assertTrue($var->hasChild('file'));
        $this->assertInstanceOf(File::class, $var->getChild('file'));
    }

    /**
     * @dataProvider provideOpenErrors
     *
     * @param string $path
     * @param string $mode
     * @param string $message
     */
    public function testOpenErrors(string $path, string $mode, string $message)
    {
        $dummy = '';
        $this->factory->createFile($this->root, ['var', 'file'], 'test string');
        $this->factory->createDirectory($this->root, ['var', 'locked'], 0, 0, 0700);
        $this->factory->createFile($this->root, ['var', 'locked', 'file'], 'test string', 0, 0, 0700);
        $this->factory->createFile($this->root, ['var', 'locked', 'open'], 'test string');

        $this->assertFalse($this->wrapper->stream_open($path, $mode, STREAM_REPORT_ERRORS, $dummy));
        $this->assertErrorMessage($message);
    }

    public function provideOpenErrors(): array
    {
        return [
            'illegal mode' => ['vfs-test://var/link', '+', '{Illegal mode}'],
            'missing file' => ['vfs-test://var/missing', 'r', '{File not found}'],
            'missing dir' => ['vfs-test://var/missing/foo', 'w', '{Directory not found}'],
            'dir' => ['vfs-test://var/locked', 'r', '{Is a directory}'],
            'read only file' => ['vfs-test://var/locked/file', 'w', '{Permission denied}'],
            'read only dir' => ['vfs-test://var/locked/foo', 'w', '{Permission denied}'],
            'write only dir' => ['vfs-test://var/locked/file', 'r', '{Permission denied}'],
            'illegal path' => ['vfs-test://var/file/foo', 'r', '{Not a directory}'],
        ];
    }
}
