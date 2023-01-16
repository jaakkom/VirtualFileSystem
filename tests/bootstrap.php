<?php

/*
 * This file is part of Twifty Virtual Filesystem.
 *
 * (c) Owen Parry <waldermort@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$loader = null;
$search = __DIR__;

while ('/' !== $search) {
    $path = $search.'/vendor/autoload.php';
    if (file_exists($path)) {
        echo 'Using bootstrap '.$path."\n";
        $loader = require $path;
        break;
    }
    $search = dirname($search);
}

if (null === $loader) {
    die('Failed to find the autoload file');
}

return $loader;
