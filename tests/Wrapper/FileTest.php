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
class FileTest extends AbstractWrapperTest
{
    public function testRenameSameDirectory()
    {
        $this->factory->createFile($this->root, ['var', 'test']);

        $var = $this->root->getChild('var');

        $this->assertTrue($var->hasChild('test'));
        $this->assertFalse($var->hasChild('new'));

        $this->assertTrue($this->wrapper->rename('vfs-test://var/test', 'vfs-test://var/new'));

        $this->assertFalse($var->hasChild('test'));
        $this->assertTrue($var->hasChild('new'));
    }

    public function testRenameAcrossDirectories()
    {
        $this->factory->createFile($this->root, ['var', 'source', 'foo']);
        $this->factory->createDirectory($this->root, ['var', 'target']);

        $var = $this->root->getChild('var');

        $this->assertTrue($this->wrapper->rename('vfs-test://var/source/foo', 'vfs-test://var/target/foo'));

        $this->assertFalse($var->getChild('source')->hasChild('foo'));
        $this->assertTrue($var->getChild('target')->hasChild('foo'));
    }

    public function testRenameExistingFile()
    {
        $this->factory->createFile($this->root, ['var', 'source', 'foo']);
        $this->factory->createFile($this->root, ['var', 'target', 'bar']);

        $var = $this->root->getChild('var');

        $this->assertTrue($this->wrapper->rename('vfs-test://var/source/foo', 'vfs-test://var/target/bar'));

        $this->assertFalse($var->getChild('source')->hasChild('foo'));
        $this->assertTrue($var->getChild('target')->hasChild('bar'));
        $this->assertCount(1, $var->getChild('target')->getChildren());
    }

    /**
     * @dataProvider provideRenameErrors
     *
     * @param string $source
     * @param string $target
     * @param string $message
     */
    public function testRenameErrors(string $source, string $target, string $message)
    {
        $this->factory->createFile($this->root, ['var', 'file']);
        $this->factory->createFile($this->root, ['var', 'source', 'foo']);
        $this->factory->createFile($this->root, ['var', 'target', 'bar']);

        $this->factory->createDirectory($this->root, ['var', 'source', 'locked'], 0, 0, 0700);
        $this->factory->createDirectory($this->root, ['var', 'target', 'locked'], 0, 0, 0700);

        $this->factory->createFile($this->root, ['var', 'source', 'locked', 'foo']);
        $this->factory->createFile($this->root, ['var', 'target', 'locked', 'bar']);

        $this->assertFalse($this->wrapper->rename($source, $target));
        $this->assertErrorMessage($message);
    }

    public function provideRenameErrors(): array
    {
        return [
            'missing file' => ['vfs-test://var/source/missing', 'vfs-test://var/target/new', '{File not found}'],
            'missing target' => ['vfs-test://var/source/foo', 'vfs-test://var/missing/new', '{File not found}'],
            'dir over file' => ['vfs-test://var/source', 'vfs-test://var/target/bar', '{Not a directory}'],
            'file over dir' => ['vfs-test://var/source/foo', 'vfs-test://var/target', '{Is a directory}'],
            'dir not empty' => ['vfs-test://var/source', 'vfs-test://var/target', '{Directory not empty}'],
            'Illegal name' => ['vfs-test://var/source/foo', 'vfs-test://var/target/#', '{Not a valid filename}'],
            'read-only source' => ['vfs-test://var/source/locked/foo', 'vfs-test://var/target/new', '{Permission denied}'],
            'read-only target' => ['vfs-test://var/source/foo', 'vfs-test://var/target/locked/new', '{Permission denied}'],
            'Illegal path' => ['vfs-test://var/file/foo', 'vfs-test://var/target/bar', '{Not a directory}'],
        ];
    }

    public function testUnlink()
    {
        $this->factory->createFile($this->root, ['var', 'foo']);

        $var = $this->root->getChild('var');

        $this->assertTrue($var->hasChild('foo'));
        $this->assertTrue($this->wrapper->unlink('vfs-test://var/foo'));
        $this->assertFalse($var->hasChild('foo'));
    }

    /**
     * @dataProvider provideUnlinkErrors
     *
     * @param string $path
     * @param string $message
     */
    public function testUnlinkErrors(string $path, string $message)
    {
        $this->factory->createFile($this->root, ['var', 'file']);
        $this->factory->createDirectory($this->root, ['var', 'foo']);
        $this->factory->createDirectory($this->root, ['var', 'locked'], 0, 0, 0700);
        $this->factory->createFile($this->root, ['var', 'locked', 'foo']);

        $this->assertFalse($this->wrapper->unlink($path));
        $this->assertErrorMessage($message);
    }

    public function provideUnlinkErrors(): array
    {
        return [
            'missing file' => ['vfs-test://var/missing', '{No such file or directory}'],
            'dir' => ['vfs-test://var/foo', '{Is a directory}'],
            'read-only' => ['vfs-test://var/locked/foo', '{Permission denied}'],
            'Illegal path' => ['vfs-test://var/file/foo', '{Not a directory}'],
        ];
    }

    public function testMetaDataInvalidOption()
    {
        $this->factory->createFile($this->root, ['var', 'file']);

        $this->assertFalse($this->wrapper->stream_metadata('vfs-test://var/file', 0, 0));
        $this->assertErrorMessage('{Unknown option}');
    }

    public function testTouch()
    {
        $file = $this->factory->createFile($this->root, ['var', 'file']);

        $var = $this->root->getChild('var');

        $file
            ->setAccessedTime(0)
            ->setModifiedTime(0)
            ->setChangedTime(123)
        ;

        $this->assertSame(0, $file->getAccessedTime());
        $this->assertSame(0, $file->getModifiedTime());
        $this->assertSame(123, $file->getChangedTime());

        $this->assertTrue($this->wrapper->stream_metadata('vfs-test://var/file', STREAM_META_TOUCH, null));

        $this->assertNotSame(0, $file->getAccessedTime());
        $this->assertNotSame(0, $file->getModifiedTime());
        $this->assertSame(123, $file->getChangedTime());

        $this->assertFalse($var->hasChild('test'));
        $this->assertTrue($this->wrapper->stream_metadata('vfs-test://var/test', STREAM_META_TOUCH, null));
        $this->assertTrue($var->hasChild('test'));
        $this->assertInstanceOf(File::class, $var->getChild('test'));

        $this->assertFalse($this->wrapper->stream_metadata('vfs-test://var/missing/test', STREAM_META_TOUCH, null));
        $this->assertErrorMessage('{Directory not found}');
    }

    public function testChown()
    {
        $file = $this->factory->createFile($this->root, ['var', 'file']);

        $var = $this->root->getChild('var');

        $this->emulator->createUser('other-user');
        $this->emulator->switchUser('root');

        $this->assertSame('test-user', $this->emulator->getUserName($file->getOwner()));
        $this->assertTrue($this->wrapper->stream_metadata('vfs-test://var/file', STREAM_META_OWNER_NAME, 'other-user'));
        $this->assertSame('other-user', $this->emulator->getUserName($file->getOwner()));

        $this->assertTrue($this->wrapper->stream_metadata('vfs-test://var/file', STREAM_META_OWNER, $this->emulator->getGroupId('root')));
        $this->assertSame('root', $this->emulator->getUserName($file->getOwner()));

        $this->emulator->switchUser('other-user');

        $this->assertFalse($this->wrapper->stream_metadata('vfs-test://var/file', STREAM_META_OWNER_NAME, 'test-user'));
        $this->assertErrorMessage('{Permission denied}');
    }

    public function testChgrp()
    {
        $file = $this->factory->createFile($this->root, ['var', 'file']);
        $file->setMode(0770);

        $this->emulator->createUser('other-user', ['shared-group']);

        $this->assertSame('test-user', $this->emulator->getUserName($file->getOwner()));
        $this->assertSame('test-group', $this->emulator->getGroupName($file->getGroup()));

        $this->emulator->switchUser('other-user');

        $this->assertSame('other-user', $this->emulator->getCurrentUserName());
        $this->assertFalse($file->isReadable($this->emulator->getCurrentUserId(), $this->emulator->getCurrentGroupId()));
        $this->assertFalse($this->wrapper->stream_metadata('vfs-test://var/file', STREAM_META_GROUP, $this->emulator->getGroupId('shared-group')), 'other-user is not the file owner');
        $this->assertSame('test-group', $this->emulator->getGroupName($file->getGroup()));

        $this->emulator->addUserToGroup('test-user', 'shared-group');

        $this->emulator->switchUser('test-user');

        $this->assertSame('test-user', $this->emulator->getCurrentUserName());
        $this->assertTrue($file->isReadable($this->emulator->getCurrentUserId(), $this->emulator->getCurrentGroupId()));
        $this->assertFalse($this->wrapper->stream_metadata('vfs-test://var/file', STREAM_META_GROUP_NAME, 'invalid-group'), 'test-user does not belong to invalid-group');
        $this->assertTrue($this->wrapper->stream_metadata('vfs-test://var/file', STREAM_META_GROUP_NAME, 'shared-group'), 'test-user is file owner');
        $this->assertSame('shared-group', $this->emulator->getGroupName($file->getGroup()));

        $this->emulator->switchUser('other-user', 'shared-group');

        $this->assertSame('other-user', $this->emulator->getCurrentUserName());
        $this->assertTrue($file->isReadable($this->emulator->getCurrentUserId(), $this->emulator->getCurrentGroupId()));
        $this->assertFalse($this->wrapper->stream_metadata('vfs-test://var/file', STREAM_META_GROUP_NAME, 'other-user'), 'other-user still cannot change group');
        $this->assertSame('shared-group', $this->emulator->getGroupName($file->getGroup()));

        $this->emulator->switchUser('root');

        $this->assertSame('root', $this->emulator->getCurrentUserName());
        $this->assertTrue($this->wrapper->stream_metadata('vfs-test://var/file', STREAM_META_GROUP_NAME, 'root'), 'root can change any files group');
        $this->assertSame('root', $this->emulator->getGroupName($file->getGroup()));

        $this->emulator->switchUser('other-user');

        $this->assertSame('other-user', $this->emulator->getCurrentUserName());
        $this->assertFalse($file->isReadable($this->emulator->getCurrentUserId(), $this->emulator->getCurrentGroupId()));
        $this->assertFalse($this->wrapper->stream_metadata('vfs-test://var/file', STREAM_META_GROUP_NAME, 'other-user'), 'other-user is not a member of root group');
        $this->assertSame('root', $this->emulator->getGroupName($file->getGroup()));

        $this->emulator->switchUser('test-user');

        $this->assertSame('test-user', $this->emulator->getCurrentUserName());
        $this->assertTrue($this->wrapper->stream_metadata('vfs-test://var/file', STREAM_META_GROUP_NAME, 'test-group'), 'File owners can change even root group');
        $this->assertSame('test-group', $this->emulator->getGroupName($file->getGroup()));
    }

    public function testChmod()
    {
        $file = $this->factory->createFile($this->root, ['var', 'file']);
        $link = $this->factory->createSymlink($this->root, ['var', 'link'], $file);

        $this->assertSame(0100755, $file->getMode());
        $this->assertTrue($this->wrapper->stream_metadata('vfs-test://var/link', STREAM_META_ACCESS, 0700));
        $this->assertSame(0100700, $file->getMode());

        $this->emulator->createUser('other-user');
        $this->emulator->switchUser('other-user');

        $this->assertFalse($this->wrapper->stream_metadata('vfs-test://var/file', STREAM_META_ACCESS, 0755));
        $this->assertSame(0100700, $file->getMode());

        $this->assertFalse($this->wrapper->stream_metadata('vfs-test://var/missing', STREAM_META_ACCESS, 0755));
        $this->assertErrorMessage('{File not found}');
    }

    public function testStat()
    {
        $file = $this->factory->createFile($this->root, ['var', 'file'], 'foo', 0, 0, 0770);
        $link = $this->factory->createSymlink($this->root, ['var', 'link'], $file);

        $this->assertNotEmpty($stat = $this->wrapper->url_stat('vfs-test://var/link', 0));

        $this->assertSame($file->getMode(), $stat['mode']);
        $this->assertSame($file->getOwner(), $stat['uid']);
        $this->assertSame($file->getGroup(), $stat['gid']);
        $this->assertSame($file->getAccessedTime(), $stat['atime']);
        $this->assertSame($file->getModifiedTime(), $stat['mtime']);
        $this->assertSame($file->getChangedTime(), $stat['ctime']);
        $this->assertSame($file->getSize(), $stat['size']);

        $this->assertNotEmpty($stat = $this->wrapper->url_stat('vfs-test://var/link', STREAM_URL_STAT_LINK));

        $this->assertSame($link->getMode(), $stat['mode']);
        $this->assertSame($link->getOwner(), $stat['uid']);
        $this->assertSame($link->getGroup(), $stat['gid']);
        $this->assertSame($link->getAccessedTime(), $stat['atime']);
        $this->assertSame($link->getModifiedTime(), $stat['mtime']);
        $this->assertSame($link->getChangedTime(), $stat['ctime']);
        $this->assertSame($link->getSize(), $stat['size']);

        $this->assertNull($this->lastError);
        $this->assertEmpty($stat = $this->wrapper->url_stat('vfs-test://var/missing', STREAM_URL_STAT_QUIET | STREAM_URL_STAT_LINK));
        $this->assertNull($this->lastError);

        $this->assertEmpty($stat = $this->wrapper->url_stat('vfs-test://var/missing', 0));
        $this->assertErrorMessage('{No such file or directory}');

        $this->assertEmpty($stat = $this->wrapper->url_stat('vfs-test://var/file/invalid', 0));
        $this->assertErrorMessage('{Not a directory}');
    }
}
