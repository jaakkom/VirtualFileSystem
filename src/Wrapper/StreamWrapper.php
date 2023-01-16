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

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class StreamWrapper implements StreamWrapperInterface
{
    use ContentTrait;
    use DirectoryTrait;
    use FileTrait;

    /**
     * Combines and returns a mixed indexed/associative stat array.
     *
     * @param array $stats
     *
     * @return array
     */
    protected function createStats(array $stats): array
    {
        $merged = array_merge([
            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => 123,
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => -1,
            'blocks' => -1,
        ], $stats);

        return array_merge(array_values($merged), $merged);
    }
}
