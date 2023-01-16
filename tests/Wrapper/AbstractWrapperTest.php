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

use PHPUnit\Framework\TestCase;
use Twifty\VirtualFileSystem\Node\Root;
use Twifty\VirtualFileSystem\System\Emulator;
use Twifty\VirtualFileSystem\System\Factory;
use Twifty\VirtualFileSystem\System\Registry;
use Twifty\VirtualFileSystem\Wrapper\StreamWrapper;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
abstract class AbstractWrapperTest extends TestCase
{
    /**
     * @var Emulator
     */
    protected $emulator;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var Root
     */
    protected $root;

    /**
     * @var StreamWrapper
     */
    protected $wrapper;

    /**
     * @var string
     */
    protected $lastError;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->emulator = new Emulator('test-user', 'test-group');
        $this->factory = new Factory($this->emulator, [$this, 'validateFilename']);
        $this->root = new Root('vfs-test', $this->factory);

        $refl = (new \ReflectionClass(Registry::class))->getProperty('registry');
        $refl->setAccessible(true);
        $refl->setValue(null, ['vfs-test' => $this->root]);

        $this->wrapper = new StreamWrapper();

        set_error_handler([$this, 'errorHandler'], E_USER_WARNING);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Registry::unregister('vfs-test');

        $this->factory = null;
        $this->root = null;
        $this->wrapper = null;
        $this->lastError = null;

        restore_error_handler();
    }

    /**
     * Catches triggered warnings without blocking the code.
     *
     * @param int    $errCode
     * @param string $errMessage
     *
     * @return bool
     */
    public function errorHandler(int $errCode, string $errMessage): bool
    {
        $this->lastError = $errMessage;

        return true;
    }

    public function validateFileName(string $filename): bool
    {
        return !in_array($filename, ['.', '..', '#'], true);
    }

    /**
     * Validates the caught error.
     *
     * @param string $message
     */
    public function assertErrorMessage(string $message)
    {
        $this->assertNotNull($this->lastError);
        $this->assertRegExp($message, $this->lastError);
    }
}
