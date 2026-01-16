<?php

namespace App\Core;

class Permissions
{
    // Role Management
    public const CAN_EDIT_PROFILE = 1 << 0;
    public const CAN_MANAGE_APPLICATIONS = 1 << 1;
    public const CAN_INVITE_MEMBERS = 1 << 2;
    public const CAN_KICK_MEMBERS = 1 << 3;
    public const CAN_MANAGE_ROLES = 1 << 4;
    public const CAN_SEE_PRIVATE_BOARD = 1 << 5;
    public const CAN_MANAGE_FORUM = 1 << 6;
    public const CAN_MANAGE_BANK = 1 << 7;
    public const CAN_MANAGE_STRUCTURES = 1 << 8;
    public const CAN_MANAGE_DIPLOMACY = 1 << 9;
    public const CAN_DECLARE_WAR = 1 << 10;

    /**
     * @return array<string, int>
     */
    public static function all(): array
    {
        return [
            'CAN_EDIT_PROFILE' => self::CAN_EDIT_PROFILE,
            'CAN_MANAGE_APPLICATIONS' => self::CAN_MANAGE_APPLICATIONS,
            'CAN_INVITE_MEMBERS' => self::CAN_INVITE_MEMBERS,
            'CAN_KICK_MEMBERS' => self::CAN_KICK_MEMBERS,
            'CAN_MANAGE_ROLES' => self::CAN_MANAGE_ROLES,
            'CAN_SEE_PRIVATE_BOARD' => self::CAN_SEE_PRIVATE_BOARD,
            'CAN_MANAGE_FORUM' => self::CAN_MANAGE_FORUM,
            'CAN_MANAGE_BANK' => self::CAN_MANAGE_BANK,
            'CAN_MANAGE_STRUCTURES' => self::CAN_MANAGE_STRUCTURES,
            'CAN_MANAGE_DIPLOMACY' => self::CAN_MANAGE_DIPLOMACY,
            'CAN_DECLARE_WAR' => self::CAN_DECLARE_WAR,
        ];
    }
}
