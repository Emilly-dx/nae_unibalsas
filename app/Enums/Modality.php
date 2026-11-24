<?php

namespace App\Enums;

enum Modality: string
{
    case Presential = 'presential';
    case Hybrid = 'hybrid';
    case Distance = 'distance';

    public function label(): string
    {
        return match ($this) {
            self::Presential => 'Presencial',
            self::Hybrid => 'Semipresencial',
            self::Distance => 'EAD',
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
