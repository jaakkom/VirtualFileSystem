<?php

namespace Twifty\Virtual;

use Twifty\TestGen\Generator\ClassInfo;
use Twifty\TestGen\Skeleton\ProjectSkeleton;

return new class('Twifty\\VirtualFileSystem\\Test', __DIR__.'/tests') extends ProjectSkeleton
{
    /**
     * {@inheritdoc}
     */
    public function getTemplatedVariables(): array
    {
        $header = <<<END
This file is part of Twifty Virtual Filesystem.

(c) Owen Parry <waldermort@gmail.com>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
END;
        
        return [
            'header_comment' => $header,
            'class_annotations' => ['', 'author' => 'Owen Parry <waldermort@gmail.com>'],
        ];
    }
};