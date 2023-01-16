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
class FunctionInjector
{
    /**
     * @var InjectableInterface[]
     */
    private static $actual = [];

    /**
     * Creates a function within the given namespace.
     *
     * @param string              $namespace
     * @param InjectableInterface $func
     *
     * @return bool
     */
    public static function inject(string $namespace, InjectableInterface $func): bool
    {
        $qualifiedName = trim($namespace, '\\').'\\'.$func->getName();

        if (function_exists($qualifiedName)) {
            return false;
        }

        self::$actual[$qualifiedName] = $func;

        $code = self::create($namespace, $func->getName());

        eval($code);

        return true;
    }

    /**
     * Calls a previously injected functions actual code.
     *
     * @param string $funcName
     * @param mixed  ...$args
     *
     * @return mixed
     */
    public static function invoke(string $funcName, ...$args)
    {
        return call_user_func_array(self::$actual[$funcName], $args);
    }

    /**
     * Creates the php code required to forward the function call.
     *
     * @param string $funcNamespace
     * @param string $funcName
     *
     * @return string
     */
    private static function create(string $funcNamespace, string $funcName): string
    {
        $selfClass = __CLASS__;

        return <<<END
namespace $funcNamespace;

function $funcName() {
    return \\$selfClass::invoke('$funcNamespace\\$funcName', ...func_get_args());
}
END;
    }
}
