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

namespace Twifty\VirtualFileSystem\Emulator;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Twifty\VirtualFileSystem\System\Registry;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class RegistryTest extends TestCase
{
    protected function tearDown()
    {
        $refl = (new ReflectionClass(Registry::class))->getProperty('registry');
        $refl->setAccessible(true);
        $refl->setValue(null, []);
    }

    public function testGenerateProtocol()
    {
        $this->assertRegExp('{^vfs[\w]+?$}', Registry::generateProtocol());
    }

    public function testRegister()
    {
        $protocol = Registry::generateProtocol();
        $root = $this->createMock(\Twifty\VirtualFileSystem\Node\Root::class);

        $root->expects($this->any())
            ->method('getProtocol')
            ->willReturn($protocol);

        try {
            Registry::resolveNode($protocol);
            $this->fail('The protocol should not exist in the resitry');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Twifty\VirtualFileSystem\Exception\RegistryException::class, $e);
            $this->assertRegExp('{The .*? protocol is not registered}', $e->getMessage());
        }

        Registry::register($root);

        $this->assertSame($root, Registry::resolveRoot($protocol));

        try {
            Registry::register($root);
            $this->fail('The same root cannot be registered multiple times');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Twifty\VirtualFileSystem\Exception\RegistryException::class, $e);
            $this->assertRegExp('{The .*? protocol has already been registered}', $e->getMessage());
        }

        Registry::unregister($protocol);
    }

    public function testUnregister()
    {
        try {
            $this->assertNull(Registry::unregister('unregistered'));
        } catch (\Exception $e) {
            $this->fail('unregister should not throw');
        }
    }

    /**
     * @dataProvider providePaths
     *
     * @param string $path
     * @param string $protocol
     * @param array  $hierarchy
     */
    public function testPaths(string $path, string $protocol, array $hierarchy)
    {
        $this->assertSame($protocol, Registry::getProtocol($path));
        $this->assertSame($hierarchy, Registry::getHierarchy($path));
    }

    public function providePaths(): array
    {
        return [
            'normal' => ['vfs://foo/bar', 'vfs', ['foo', 'bar']],
            'empty hierarchy' => ['vfs://', 'vfs', []],
            'leading slash' => ['vfs:///foo/bar', 'vfs', ['foo', 'bar']],
            'embedded slash' => ['vfs://foo//bar', 'vfs', ['foo', 'bar']],
            'trailing slash' => ['vfs://foo/bar/', 'vfs', ['foo', 'bar']],
            'funky' => ['vfs://foo/./.././bar', 'vfs', ['bar']],
        ];
    }
}
