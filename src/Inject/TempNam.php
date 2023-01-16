<?php

declare(strict_types=1);

namespace Twifty\VirtualFileSystem\Inject;

use Twifty\VirtualFileSystem\System\Registry;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class TempNam extends AbstractInjectable
{
    public function __invoke(...$args)
    {
        $dir = $args[0];
        $prefix = $args[1];
        
        $path = $this->resolvePath($dir);
        if ($path && $result = Registry::resolveNode($path)) {
            if ($result->exists && $result->directory) {
                do {
                    $tmpName = $prefix.uniqid();
                } while ($result->directory->hasChild($tmpName));

                $file = $result->factory->createFile($result->directory, [$tmpName], null, null, null, 0600);

                return $file->getPath();
            }
        }
        
        return \tempnam($dir, $prefix);
    }

    public function getName(): string
    {
        return 'tempnam';
    }
}
