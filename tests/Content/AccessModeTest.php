<?php

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

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class AccessModeTest extends TestCase
{
    /**
     * @dataProvider provideParse
     *
     * @param string $mode
     * @param int    $expect
     */
    public function testParse(string $mode, int $expect)
    {
        $this->assertSame($expect, AccessMode::parse($mode));
    }

    public function provideParse(): array
    {
        return [
            ['', 0],

            ['t', 0],
            ['b', 0],
            ['e', 0],
            ['+', 0],
            ['z', 0],

            ['r', AccessMode::MODE_READ | AccessMode::MODE_OPEN | AccessMode::MODE_SEEK_START],
            ['w', AccessMode::MODE_WRITE | AccessMode::MODE_OPENCREATE | AccessMode::MODE_TRUNCATE],
            ['a', AccessMode::MODE_WRITE | AccessMode::MODE_OPENCREATE | AccessMode::MODE_SEEK_END | AccessMode::MODE_APPEND],
            ['x', AccessMode::MODE_WRITE | AccessMode::MODE_CREATE | AccessMode::MODE_SEEK_START],
            ['c', AccessMode::MODE_WRITE | AccessMode::MODE_OPENCREATE | AccessMode::MODE_SEEK_START],

            ['r+', AccessMode::MODE_READWRITE | AccessMode::MODE_OPEN | AccessMode::MODE_SEEK_START],
            ['w+', AccessMode::MODE_READWRITE | AccessMode::MODE_OPENCREATE | AccessMode::MODE_TRUNCATE],
            ['a+', AccessMode::MODE_READWRITE | AccessMode::MODE_OPENCREATE | AccessMode::MODE_SEEK_END | AccessMode::MODE_APPEND],
            ['x+', AccessMode::MODE_READWRITE | AccessMode::MODE_CREATE | AccessMode::MODE_SEEK_START],
            ['c+', AccessMode::MODE_READWRITE | AccessMode::MODE_OPENCREATE | AccessMode::MODE_SEEK_START],

            ['r+tbe', AccessMode::MODE_READWRITE | AccessMode::MODE_OPEN | AccessMode::MODE_SEEK_START],
            ['wt+be', AccessMode::MODE_READWRITE | AccessMode::MODE_OPENCREATE | AccessMode::MODE_TRUNCATE],
            ['atb+e', AccessMode::MODE_READWRITE | AccessMode::MODE_OPENCREATE | AccessMode::MODE_SEEK_END | AccessMode::MODE_APPEND],
            ['xtbe+', AccessMode::MODE_READWRITE | AccessMode::MODE_CREATE | AccessMode::MODE_SEEK_START],
            ['tbec+', AccessMode::MODE_READWRITE | AccessMode::MODE_OPENCREATE | AccessMode::MODE_SEEK_START],
        ];
    }
}
