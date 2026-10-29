<?php

namespace App\Enums;

enum Shift: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';
    case FullTime = 'full_time';

    public function label(): string
    {
        return match ($this) {
            self::Morning => 'Matutino',
            self::Afternoon => 'Vespertino',
            self::Evening => 'Noturno',
            self::FullTime => 'Integral',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
