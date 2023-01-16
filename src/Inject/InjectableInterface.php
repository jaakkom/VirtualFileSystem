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

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
interface InjectableInterface
{
    /**
     * Executes the function body.
     *
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function __invoke(...$args);

    /**
     * Returns the name of the function te be created.
     *
     * @return string
     */
    public function getName(): string;
}
