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

namespace Twifty\VirtualFileSystem\Inject;

use Twifty\VirtualFileSystem\Node\Directory;
use Twifty\VirtualFileSystem\Node\Symlink;
use Twifty\VirtualFileSystem\System\Registry;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class RealPath extends AbstractInjectable
{
    /**
     * Simulates the php realpath function.
     *
     * @param string $originalPath
     *
     * @return bool
     */
    public function __invoke(...$args)
    {
        $originalPath = func_get_arg(0);
        $path = $this->resolvePath($originalPath);
        if (!$path || !$node = Registry::resolveRoot($path)) {
            return \realpath($originalPath);
        }

        foreach (Registry::getHierarchy($path) as $name) {
            if (!$node instanceof Directory || !$node->hasChild($name)) {
                return false;
            }

            $node = $node->getChild($name);

            while ($node instanceof Symlink) {
                $node = $node->getTarget();
            }
        }

        return $node->getPath();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'realpath';
    }
}
