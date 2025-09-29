<?php

namespace App\Enums;

enum OrganizationStatus: string
{
    case Active = 'active';
    case Trial = 'trial';
    case Suspended = 'suspended';
    case Inactive = 'inactive';
    case SetupIncomplete = 'setup_incomplete';

    /**
     * Get the display label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::Active => 'Active',
            self::Trial => 'Trial',
            self::Suspended => 'Suspended',
            self::Inactive => 'Inactive',
            self::SetupIncomplete => 'Setup Incomplete',
        };
    }

    /**
     * Get the color class for the status
     */
    public function color(): string
    {
        return match($this) {
            self::Active => 'text-green-600 bg-green-100',
            self::Trial => 'text-blue-600 bg-blue-100',
            self::Suspended => 'text-red-600 bg-red-100',
            self::Inactive => 'text-gray-600 bg-gray-100',
            self::SetupIncomplete => 'text-yellow-600 bg-yellow-100',
        };
    }
}
