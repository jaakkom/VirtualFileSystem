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

use Twifty\VirtualFileSystem\Node\Directory;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class DirectoryTest extends AbstractWrapperTest
{
    public function testOpenDir()
    {
        $this->factory->createDirectory($this->root, ['foo', 'bar']);

        $this->assertTrue($this->wrapper->dir_opendir('vfs-test://', 0));
        $this->assertTrue($this->wrapper->dir_opendir('vfs-test://foo', 0));
        $this->assertTrue($this->wrapper->dir_opendir('vfs-test://foo/bar', 0));

        $this->assertTrue($this->wrapper->dir_closedir());
        $this->assertFalse($this->wrapper->dir_closedir(), 'directory should have been closed');
    }

    /**
     * @dataProvider provideOpenDirErrors
     *
     * @param string $path
     * @param string $message
     */
    public function testOpenDirErrors(string $path, string $message)
    {
        $this->factory->createDirectory($this->root, ['foo', 'bar']);
        $this->factory->createDirectory($this->root, ['locked'], 0, 0, 0700);
        $this->factory->createFile($this->root, ['file']);

        $this->assertFalse($this->wrapper->dir_opendir($path, 0));
        $this->assertErrorMessage($message);
    }

    public function provideOpenDirErrors(): array
    {
        return [
            'missing file' => ['vfs-test://missing', '{File not found}'],
            'not a dir' => ['vfs-test://file', '{Not a directory}'],
            'locked dir' => ['vfs-test://locked', '{Permission denied}'],
            'file as dir' => ['vfs-test://file/dir', '{Not a directory}'],
        ];
    }

    public function testIterator()
    {
        $this->factory->createFile($this->root, ['file1']);
        $this->factory->createFile($this->root, ['file2']);
        $this->factory->createFile($this->root, ['file3']);

        $this->assertFalse($this->wrapper->dir_rewinddir(), 'The directory has not been opened');

        $this->assertTrue($this->wrapper->dir_opendir('vfs-test://', 0));

        $this->assertTrue($this->wrapper->dir_rewinddir());
        $this->assertSame('file1', $this->wrapper->dir_readdir());
        $this->assertSame('file2', $this->wrapper->dir_readdir());
        $this->assertSame('file3', $this->wrapper->dir_readdir());
        $this->assertFalse($this->wrapper->dir_readdir());
    }

    public function testMakeDir()
    {
        $this->factory->createDirectory($this->root, ['var']);

        $this->assertTrue($this->wrapper->mkdir('vfs-test://var/foo', 0750, 0));

        $var = $this->root->getChild('var');

        $this->assertTrue($var->hasChild('foo'));
        $this->assertInstanceOf(Directory::class, $dir = $var->getChild('foo'));

        $this->assertSame(0750 | 0040000, $dir->getMode());
        $this->assertSame('test-user', $this->emulator->getUserName($dir->getOwner()));
        $this->assertSame('test-group', $this->emulator->getGroupName($dir->getGroup()));
    }

    public function testMakeDirRecursive()
    {
        $this->factory->createDirectory($this->root, ['var']);

        $this->assertTrue($this->wrapper->mkdir('vfs-test://var/foo/bar/baz', 0750, STREAM_MKDIR_RECURSIVE));

        $var = $this->root->getChild('var');

        $this->assertTrue($var->hasChild('foo'));
        $this->assertInstanceOf(Directory::class, $foo = $var->getChild('foo'));

        $this->assertSame(0750 | 0040000, $foo->getMode());
        $this->assertSame('test-user', $this->emulator->getUserName($foo->getOwner()));
        $this->assertSame('test-group', $this->emulator->getGroupName($foo->getGroup()));

        $this->assertTrue($foo->hasChild('bar'));
        $this->assertInstanceOf(Directory::class, $bar = $foo->getChild('bar'));

        $this->assertSame(0750 | 0040000, $bar->getMode());
        $this->assertSame('test-user', $this->emulator->getUserName($bar->getOwner()));
        $this->assertSame('test-group', $this->emulator->getGroupName($bar->getGroup()));

        $this->assertTrue($bar->hasChild('baz'));
        $this->assertInstanceOf(Directory::class, $baz = $bar->getChild('baz'));

        $this->assertSame(0750 | 0040000, $baz->getMode());
        $this->assertSame('test-user', $this->emulator->getUserName($baz->getOwner()));
        $this->assertSame('test-group', $this->emulator->getGroupName($baz->getGroup()));
    }

    /**
     * @dataProvider provideMakeDirErrors
     *
     * @param string $path
     * @param string $message
     */
    public function testMakeDirErrors(string $path, string $message)
    {
        $this->factory->createDirectory($this->root, ['var', 'var', 'var']);
        $this->factory->createDirectory($this->root, ['locked'], 0, 0, 0700);
        $this->factory->createDirectory($this->root, ['var', 'locked'], 0, 0, 0700);
        $this->factory->createFile($this->root, ['file']);
        $this->factory->createFile($this->root, ['var', 'file']);

        $this->assertFalse($this->wrapper->mkdir($path, 0755, 0));
        $this->assertErrorMessage($message);
    }

    public function provideMakeDirErrors(): array
    {
        return [
            'root' => ['vfs-test://foo', '{Permission denied}'],
            'no recursive flag' => ['vfs-test://var/foo/bar', '{File not found}'],
            'existing 1' => ['vfs-test://var', '{File exists}'],
            'existing 2' => ['vfs-test://var/var', '{File exists}'],
            'existing 3' => ['vfs-test://var/var/var', '{File exists}'],
            'locked 1' => ['vfs-test://locked/foo', '{Permission denied}'],
            'locked 2' => ['vfs-test://var/locked/foo', '{Permission denied}'],
            'file 1' => ['vfs-test://file/foo', '{Not a directory}'],
            'file 2' => ['vfs-test://var/file/foo', '{Not a directory}'],
        ];
    }

    public function testRemoveDir()
    {
        $this->factory->createDirectory($this->root, ['var', 'foo']);
        $this->factory->createDirectory($this->root, ['var', 'locked'], 0, 0, 0700);

        $this->assertTrue($this->wrapper->rmdir('vfs-test://var/foo', 0));
        $this->assertFalse($this->root->getChild('var')->hasChild('foo'));

        $this->assertTrue($this->wrapper->rmdir('vfs-test://var/locked', 0), 'parent is writable');
        $this->assertFalse($this->root->getChild('var')->hasChild('locked'));
    }

    /**
     * @dataProvider provideRemoveDirErrors
     *
     * @param string $path
     * @param string $message
     */
    public function testRemoveDirErrors(string $path, string $message)
    {
        $this->factory->createDirectory($this->root, ['var', 'foo', 'bar', 'baz']);
        $this->factory->createDirectory($this->root, ['var', 'locked'], 0, 0, 0700);
        $this->factory->createDirectory($this->root, ['var', 'locked', 'foo']);
        $this->factory->createFile($this->root, ['var', 'file']);

        $this->assertFalse($this->wrapper->rmdir($path, 0));
        $this->assertErrorMessage($message);
    }

    public function provideRemoveDirErrors(): array
    {
        return [
            'root' => ['vfs-test://', '{Cannot remove root}'],
            'locked dir' => ['vfs-test://var/locked/foo', '{Permission denied}'],
            'non dir' => ['vfs-test://var/file', '{Not a directory}'],
            'missing 1' => ['vfs-test://var/missing', '{File not found}'],
            'missing 2' => ['vfs-test://missing/missing', '{File not found}'],
            'non empty' => ['vfs-test://var/foo', '{Directory not empty}'],
            'invalid path' => ['vfs-test://var/file/foo', '{Not a directory}'],
        ];
    }
}
