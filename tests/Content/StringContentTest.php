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

namespace Twifty\VirtualFileSystem\Test\Content;

use PHPUnit\Framework\TestCase;
use Twifty\VirtualFileSystem\Content\AccessMode;
use Twifty\VirtualFileSystem\Content\ContentInterface;
use Twifty\VirtualFileSystem\Content\StringContentTrait;
use Twifty\VirtualFileSystem\Exception\ContentException;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class StringContentTest extends TestCase
{
    const INITIAL_DATA = 'The quick brown fox jumps over the lazy dog';

    /**
     * @var ContentInterface
     */
    protected $content;

    protected function setUp()
    {
        $this->content = new class(self::INITIAL_DATA) {
            use StringContentTrait;

            public function __construct(string $data)
            {
                $this->initializeData($data);
            }
        };
    }

    protected function tearDown()
    {
        $this->content = null;
    }

    public function testReadOnly()
    {
        $this->content->open(AccessMode::parse('r'));

        $this->assertSame(0, $this->content->tell());
        $this->assertSame('The quick brown fox jumps', $this->content->read(25));
        $this->assertSame(25, $this->content->tell());

        try {
            $this->content->write('test');
            $this->fail('The content should be read-only');
        } catch (\Exception $e) {
            $this->assertInstanceOf(ContentException::class, $e);
            $this->assertSame('Operation not permitted', $e->getMessage());
        }

        $this->assertTrue($this->content->seek(0, SEEK_END));
        $this->assertSame(43, $this->content->tell());
        $this->assertTrue($this->content->eof());

        $this->assertSame('', $this->content->read(10));
        $this->assertSame(43, $this->content->tell(), 'reading past end of file should not move pointer');
        $this->assertTrue($this->content->eof());

        $this->assertFalse($this->content->seek(10, SEEK_CUR), 'Only writable files can seek past the end');
        $this->assertFalse($this->content->seek(-10, SEEK_SET), 'Cannot seek to negative positions');
        $this->assertFalse($this->content->seek(0, -1), 'Invalid operation');

        // Sanity check
        $this->assertSame(43, $this->content->tell(), 'reading past end of file should not move pointer');
        $this->assertTrue($this->content->eof());

        $this->assertFalse($this->content->truncate(43), 'Read only files cannot be truncated');
    }

    public function testReadUpdate()
    {
        $this->content->open(AccessMode::parse('r+'));

        $this->assertSame(0, $this->content->tell());
        $this->assertSame(25, $this->content->write('An overweight cat stepped'));
        $this->assertSame(25, $this->content->tell());

        $this->assertTrue($this->content->seek(0, SEEK_END));
        $this->assertSame(43, $this->content->tell());
        $this->assertTrue($this->content->eof());

        $this->assertTrue($this->content->seek(0, SEEK_SET));
        $this->assertSame('An overweight cat stepped over the lazy dog', $this->content->read(43));

        $this->assertTrue($this->content->truncate(25));
        $this->assertSame(43, $this->content->tell(), 'truncate should not move the pointer');
    }

    public function testWriteOnly()
    {
        $this->content->open(AccessMode::parse('w'));

        $this->assertSame(0, $this->content->tell());
        $this->assertSame(11, $this->content->write('test string'));

        $this->assertTrue($this->content->seek(0, SEEK_SET));
        $this->assertSame(0, $this->content->tell());
        $this->assertSame(3, $this->content->write('foo'));

        $this->assertTrue($this->content->seek(0, SEEK_END));
        $this->assertSame(11, $this->content->tell(), 'content should have been overwritten in place');

        try {
            $this->assertTrue($this->content->seek(0, SEEK_SET));
            $this->content->read(10);
            $this->fail('The content should be write-only');
        } catch (\Exception $e) {
            $this->assertInstanceOf(ContentException::class, $e);
            $this->assertSame('Operation not permitted', $e->getMessage());
        }
    }

    public function testWriteUpdate()
    {
        $this->content->open(AccessMode::parse('w+'));

        $this->assertSame('', $this->content->read(43));

        $this->assertSame(0, $this->content->tell());
        $this->assertTrue($this->content->seek(25, SEEK_SET));
        $this->assertSame(25, $this->content->tell());

        $this->assertSame('', $this->content->read(10));
        $this->assertSame(25, $this->content->tell(), 'Reading past the end should not move the pointer');

        $this->assertSame(18, $this->content->write(' over the lazy dog'));
        $this->assertSame(43, $this->content->tell());
        $this->assertTrue($this->content->eof());

        $this->assertTrue($this->content->seek(0, SEEK_SET));
        $this->assertSame("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0 over the lazy dog", $this->content->read(43));

        $this->assertTrue($this->content->seek(0, SEEK_SET));
        $this->assertSame(25, $this->content->write('The quick brown fox jumps'));

        $this->assertTrue($this->content->seek(0, SEEK_SET));
        $this->assertSame('The quick brown fox jumps over the lazy dog', $this->content->read(43));
        $this->assertTrue($this->content->eof());

        $this->assertTrue($this->content->truncate(0));
        $this->assertTrue($this->content->seek(0, SEEK_SET));
        $this->assertSame('', $this->content->read(10));

        $this->assertTrue($this->content->truncate(10));
        $this->assertSame("\0\0\0\0\0\0\0\0\0\0", $this->content->read(10), 'Expanded files should be filled with null bytes');

        $this->assertTrue($this->content->flush(), 'Coverage only');
    }

    public function testAppend()
    {
        $this->content->open(AccessMode::parse('a'));

        $this->assertSame(43, $this->content->tell());

        $this->assertTrue($this->content->seek(0, SEEK_SET), 'Seeking in append mode should not cause errors');
        $this->assertSame(0, $this->content->tell());

        $this->assertSame(22, $this->content->write(' because it was daring'));
        $this->assertTrue($this->content->seek(0, SEEK_END));
        $this->assertSame(65, $this->content->tell(), 'writes should always be appended');

        try {
            $this->assertTrue($this->content->seek(0, SEEK_SET));
            $this->content->read(10);
            $this->fail('The content should be write-only');
        } catch (\Exception $e) {
            $this->assertInstanceOf(ContentException::class, $e);
            $this->assertSame('Operation not permitted', $e->getMessage());
        }
    }

    public function testAppendUpdate()
    {
        $this->content->open(AccessMode::parse('a+'));

        $this->assertSame(43, $this->content->tell());
        $this->assertSame('', $this->content->read(10));

        $this->assertTrue($this->content->seek(0, SEEK_SET), 'Seeking in append mode should not cause errors');
        $this->assertSame(0, $this->content->tell());

        $this->assertSame(0, $this->content->write(''), 'Purely for coverage');
        $this->assertSame(22, $this->content->write(' because it was daring'));
        $this->assertTrue($this->content->seek(0, SEEK_END));
        $this->assertSame(65, $this->content->tell(), 'writes should always be appended');

        $this->assertTrue($this->content->seek(0, SEEK_SET));
        $this->assertSame('The quick brown fox jumps over the lazy dog because it was daring', $this->content->read(65));

        $this->assertFalse($this->content->truncate(43), 'Append only files cannot be truncated');
    }
}
