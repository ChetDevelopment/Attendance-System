<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';

    public static function fromString(string $value): self
    {
        // Handle both lowercase (from DB) and capitalized (from frontend)
        $normalized = strtolower($value);

        return match ($normalized) {
            'present' => self::PRESENT,
            'absent' => self::ABSENT,
            'late' => self::LATE,
            default => throw new \InvalidArgumentException("Invalid attendance status: {$value}"),
        };
    }
}
