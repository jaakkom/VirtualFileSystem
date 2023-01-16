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

namespace Twifty\VirtualFileSystem\System;

use Twifty\VirtualFileSystem\Exception\EmulatorException;

/**
 * Emulates users and groups.
 *
 * @author Owen Parry <waldermort@gmail.com>
 */
class Emulator
{
    /**
     * @var string[]
     */
    private $users = [];

    /**
     * @var string[]
     */
    private $groups = [];

    /**
     * @var int[][]
     */
    private $userGroups = [];

    /**
     * @var int[][]
     */
    private $groupUsers = [];

    /**
     * @var bool
     */
    private $super;

    /**
     * @var int
     */
    private $currentUser;

    /**
     * @var int
     */
    private $currentGroup;

    /**
     * Constructor.
     *
     * @param string $user
     * @param string $group
     */
    public function __construct(string $user, string $group = null)
    {
        if ('root' === $user) {
            throw new \LogicException('"root" is a reserved user');
        }

        $this->createUser('root', ['root'], true);
        $this->createUser($user, $group ? [$group] : []);
        $this->switchUser($user, $group);
    }

    /**
     * Checks if current user is root.
     *
     * @return bool
     */
    public function isSuperUser(): bool
    {
        return $this->super;
    }

    /**
     * Changes the current user and group.
     *
     * @param string|int      $user
     * @param string|int|null $group
     */
    public function switchUser($user, $group = null)
    {
        $this->currentUser = $this->get('users', $user, false);
        $this->currentGroup = $group ? $this->get('groups', $group, false) : $this->currentUser;
        $this->super = 0 === $this->currentUser;
    }

    /**
     * Returns the name of the current user.
     *
     * @return string
     */
    public function getCurrentUserName(): string
    {
        return $this->get('users', $this->currentUser, true);
    }

    /**
     * Returns the ID of the current user.
     *
     * @return int
     */
    public function getCurrentUserId(): int
    {
        return $this->currentUser;
    }

    /**
     * Returns the name of the current group.
     *
     * @return string
     */
    public function getCurrentGroupName(): string
    {
        return $this->get('groups', $this->currentGroup, true);
    }

    /**
     * Returns the ID of the current group.
     *
     * @return int
     */
    public function getCurrentGroupId(): int
    {
        return $this->currentGroup;
    }

    /**
     * Checks if a user exists in the system.
     *
     * @param string|int $user
     *
     * @return bool
     */
    public function userExists($user): bool
    {
        return isset($this->users[$user]) || false !== array_search($user, $this->users, true);
    }

    /**
     * Checks if a group exists in the system.
     *
     * @param string|int $group
     *
     * @return bool
     */
    public function groupExists($group): bool
    {
        return isset($this->groups[$group]) || false !== array_search($group, $this->groups, true);
    }

    /**
     * Converts a user ID to name.
     *
     * @param int $uid
     *
     * @throws EmulatorException
     *
     * @return string
     */
    public function getUserName(int $uid): string
    {
        return $this->get('users', $uid, true);
    }

    /**
     * Converts a user name to ID.
     *
     * @param string|int $name
     *
     * @throws EmulatorException
     *
     * @return int
     */
    public function getUserId($name): int
    {
        return $this->get('users', $name, false);
    }

    /**
     * Converts a group ID to name.
     *
     * @param int $gid
     *
     * @throws EmulatorException
     *
     * @return string
     */
    public function getGroupName(int $gid): string
    {
        return $this->get('groups', $gid, true);
    }

    /**
     * Converts a group name to ID.
     *
     * @param string|int $name
     *
     * @return int
     */
    public function getGroupId($name): int
    {
        return $this->get('groups', $name, false);
    }

    /**
     * Returns all groups to which the user belongs.
     *
     * @param string|int $user
     * @param bool       $getNames
     *
     * @return array
     */
    public function getUserGroups($user, bool $getNames = false): array
    {
        $uid = $this->get('users', $user, false);
        $groups = array_keys($this->userGroups[$uid]);

        if ($getNames) {
            $groups = array_map(function ($gid) {
                return $this->groups[$gid];
            }, $groups);
        }

        return $groups;
    }

    /**
     * Returns all users which belong to a group.
     *
     * @param string|int $group
     * @param bool       $getNames
     *
     * @return array
     */
    public function getGroupUsers($group, bool $getNames = false): array
    {
        $gid = $this->get('groups', $group, false);
        $users = array_keys($this->groupUsers[$gid]);

        if ($getNames) {
            $users = array_map(function ($uid) {
                return $this->users[$uid];
            }, $users);
        }

        return $users;
    }

    /**
     * Creates a new user and adds it to the given groups.
     *
     * This method will also create a group for the user.
     *
     * @param string   $name
     * @param string[] $groups
     * @param bool     $isSystem
     *
     * @return int
     */
    public function createUser(string $name, array $groups = [], bool $isSystem = false): int
    {
        if (false === $uid = array_search($name, $this->users, true)) {
            $uid = $isSystem ? 0 : 1000;
            while (isset($this->users[$uid])) {
                ++$uid;
            }
            $this->users[$uid] = $name;
        }

        foreach (array_merge($groups, [$name]) as $group) {
            $gid = $this->createGroup($group, [], $isSystem);
            $this->userGroups[$uid][$gid] = true;
            $this->groupUsers[$gid][$uid] = true;
        }

        return $uid;
    }

    /**
     * Creates a new group and adds the given users.
     *
     * If any of the users do not exist, they will be created.
     *
     * @param string   $name
     * @param string[] $users
     * @param bool     $isSystem
     *
     * @return int
     */
    public function createGroup(string $name, array $users = [], bool $isSystem = false): int
    {
        if (false === $gid = array_search($name, $this->groups, true)) {
            $gid = $isSystem ? 0 : 1000;
            while (isset($this->groups[$gid])) {
                ++$gid;
            }
            $this->groups[$gid] = $name;
        }

        foreach ($users as $user) {
            $uid = $this->createUser($user, [], $isSystem);
            $this->groupUsers[$gid][$uid] = true;
            $this->userGroups[$uid][$gid] = true;
        }

        return $gid;
    }

    public function addUserToGroup($user, $group)
    {
        $name = $this->get('users', $user, true);

        $this->createUser($name, (array) $group);
    }

    /**
     * Validates and converts the value into a name.
     *
     * @param string     $which
     * @param string|int $value
     * @param bool       $asString
     *
     * @throws EmulatorException
     * @throws \InvalidArgumentException
     *
     * @return string|int
     */
    private function get(string $which, $value, bool $asString)
    {
        if (is_string($value) && ctype_digit($value)) {
            $value = (int) $value;
        }

        if (is_int($value)) {
            if (!isset($this->{$which}[$value])) {
                throw new EmulatorException(sprintf('Unknown %s ID: (%d)', rtrim($which, 's'), $value));
            }

            return $asString ? $this->{$which}[$value] : $value;
        } elseif (is_string($value)) {
            if (false === $id = array_search($value, $this->{$which}, true)) {
                throw new EmulatorException(sprintf('Unknown %s name: (%s)', rtrim($which, 's'), $value));
            }

            return $asString ? $value : $id;
        }

        throw new \InvalidArgumentException(sprintf('Expected one of [string, int], got (%s)', gettype($value)));
    }
}
