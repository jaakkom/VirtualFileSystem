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

namespace Twifty\VirtualFileSystem\Emulator;

use PHPUnit\Framework\TestCase;
use Twifty\VirtualFileSystem\Exception\NodeException;
use Twifty\VirtualFileSystem\Node\Directory;
use Twifty\VirtualFileSystem\Node\File;
use Twifty\VirtualFileSystem\System\Factory;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class FactoryTest extends TestCase
{
    protected $dir;

    protected function setUp()
    {
        $this->dir = new Directory('test', 1000, 1000);
    }

    protected function tearDown()
    {
        $this->dir = null;
    }

    public function testCreateFile()
    {
        $factory = new Factory();

        $file1 = $factory->createFile($this->dir, ['foo', 'bar', 'baz']);
        $file2 = $factory->createFile($this->dir, ['foo', 'bin']);
        $file3 = $factory->createFile($this->dir, ['var']);

        $this->assertSame('::test/foo/bar/baz', $file1->getPath());
        $this->assertSame('::test/foo/bin', $file2->getPath());
        $this->assertSame('::test/var', $file3->getPath());

        $this->assertCount(2, $this->dir->getChildren());

        $this->assertTrue($this->dir->hasChild('foo'));
        $this->assertTrue($this->dir->hasChild('var'));

        $this->assertInstanceOf(Directory::class, $foo = $this->dir->getChild('foo'));
        $this->assertInstanceOf(File::class, $this->dir->getChild('var'));

        $this->assertCount(2, $foo->getChildren());
        $this->assertTrue($foo->hasChild('bar'));
        $this->assertTrue($foo->hasChild('bin'));

        $this->assertInstanceOf(Directory::class, $bar = $foo->getChild('bar'));
        $this->assertInstanceOf(File::class, $foo->getChild('bin'));

        $this->assertCount(1, $bar->getChildren());
        $this->assertTrue($bar->hasChild('baz'));
        $this->assertInstanceOf(File::class, $bar->getChild('baz'));

        try {
            $factory->createFile($this->dir, ['foo', 'bin', 'oops']);
            $this->fail('Cannot add a child to a file');
        } catch (\Exception $e) {
            $this->assertInstanceOf(NodeException::class, $e);
            $this->assertSame('Not a directory', $e->getMessage());
        }
    }

    public function testCreateDirectory()
    {
        $factory = new Factory();

        $dir1 = $factory->createDirectory($this->dir, ['foo']);

        $this->assertSame('::test/foo', $dir1->getPath());
        $this->assertCount(1, $this->dir->getChildren());
        $this->assertTrue($this->dir->hasChild('foo'));
        $this->assertSame($dir1, $this->dir->getChild('foo'));

        try {
            $factory->createDirectory($this->dir, ['foo', '.', 'oops']);
            $this->fail('Cannot add a dot names');
        } catch (\Exception $e) {
            $this->assertInstanceOf(NodeException::class, $e);
            $this->assertSame('Not a valid filename', $e->getMessage());
        }
    }

    public function testCreateSymlink()
    {
        $factory = new Factory();

        $link = $factory->createSymlink($this->dir, ['foo', 'bar'], $this->dir);

        $this->assertSame('::test/foo/bar', $link->getPath());
        $this->assertTrue($this->dir->hasChild('foo'));
        $this->assertTrue($this->dir->getChild('foo')->hasChild('bar'));
        $this->assertSame($link, $bar = $this->dir->getChild('foo')->getChild('bar'));

        $this->assertSame($this->dir, $bar->getTarget());

        try {
            $factory->createSymlink($this->dir, ['foo', '..'], $this->dir);
            $this->fail('Cannot add a dot names');
        } catch (\Exception $e) {
            $this->assertInstanceOf(NodeException::class, $e);
            $this->assertSame('Not a valid filename', $e->getMessage());
        }
    }
}
