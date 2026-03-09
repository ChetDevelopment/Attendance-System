<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'PRESENT';
    case ABSENT = 'ABSENT';
    case LATE = 'LATE';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function fromString(string $value): self
    {
        $upper = strtoupper($value);
        return match ($upper) {
            'PRESENT' => self::PRESENT,
            'ABSENT' => self::ABSENT,
            'LATE' => self::LATE,
            default => throw new \InvalidArgumentException("Invalid attendance status: {$value}"),
        };
    }
}
