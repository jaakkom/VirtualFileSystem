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
use Twifty\VirtualFileSystem\System\Emulator;

/**
 * @author Owen Parry <waldermort@gmail.com>
 */
class EmulatorTest extends TestCase
{
    public function testConstruction()
    {
        $system = new Emulator('twifty');

        $this->assertSame('twifty', $system->getCurrentUserName());
        $this->assertSame('twifty', $system->getCurrentGroupName());

        $this->assertTrue($system->userExists('twifty'));
        $this->assertTrue($system->groupExists('twifty'));

        $this->assertSame(1000, $system->getCurrentUserId());
        $this->assertSame(1000, $system->getCurrentGroupId());

        $this->assertSame('twifty', $system->getUserName(1000));
        $this->assertSame('twifty', $system->getGroupName(1000));

        $this->assertSame(['twifty'], $system->getUserGroups('1000', true));
        $this->assertSame([1000], $system->getUserGroups('twifty', false));

        $this->assertSame(['twifty'], $system->getGroupUsers('twifty', true));
        $this->assertSame([1000], $system->getGroupUsers('1000', false));

        $this->assertFalse($system->isSuperUser());

        $this->assertTrue($system->userExists('root'));
        $this->assertTrue($system->groupExists('root'));

        $this->assertSame(0, $system->getUserId('root'));
        $this->assertSame(0, $system->getGroupId('root'));

        $this->assertSame('root', $system->getUserName(0));
        $this->assertSame('root', $system->getGroupName(0));

        $this->assertSame(['root'], $system->getUserGroups('root', true));
        $this->assertSame([0], $system->getUserGroups('0', false));

        $this->assertSame(['root'], $system->getGroupUsers('0', true));
        $this->assertSame([0], $system->getGroupUsers('root', false));
    }

    public function testRootIsReserved()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"root" is a reserved user');

        new Emulator('root');
    }

    public function testCreateUser()
    {
        $system = new Emulator('twifty');

        $system->createUser('new');

        $this->assertTrue($system->userExists('new'));
        $this->assertTrue($system->groupExists('new'));
        $this->assertSame(1001, $system->getUserId('new'));

        $system->createGroup('new-group', ['user1', 'user2']);

        $this->assertTrue($system->groupExists('new-group'));
        $this->assertTrue($system->userExists('user1'));
        $this->assertTrue($system->userExists('user2'));

        $this->assertCount(2, $users = $system->getGroupUsers('new-group', true));
        $this->assertContains('user1', $users);
        $this->assertContains('user2', $users);
    }

    public function testCreateGroup()
    {
        $system = new Emulator('twifty');

        $system->createGroup('new-group');

        $this->assertFalse($system->userExists('new-group'));
        $this->assertTrue($system->groupExists('new-group'));

        $system->addUserToGroup('twifty', ['group1', 'new-group', 'group2', 'group1']);

        $this->assertCount(4, $groups = $system->getUserGroups('twifty', true));
        $this->assertContains('twifty', $groups);
        $this->assertContains('new-group', $groups);
        $this->assertContains('group1', $groups);
        $this->assertContains('group2', $groups);
    }

    /**
     * @dataProvider provideGetName
     *
     * @param mixed  $arg
     * @param string $message
     * @param string $class
     */
    public function testGetName($arg, string $message, string $class = null)
    {
        $this->expectException($class ?? \Twifty\VirtualFileSystem\Exception\EmulatorException::class);
        $this->expectExceptionMessage($message);

        (new Emulator('twifty'))->addUserToGroup($arg, 'group');
    }

    public function provideGetName(): array
    {
        return [
            'unknown name' => ['foo', 'Unknown user name: (foo)'],
            'unknown id' => [1001, 'Unknown user ID: (1001)'],
            'invalid value' => [true, 'Expected one of [string, int], got (boolean)', \InvalidArgumentException::class],
        ];
    }
}
