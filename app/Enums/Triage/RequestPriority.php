<?php

namespace App\Enums\Triage;

/**
 * Prioridade definida na triagem.
 */
enum RequestPriority: string
{
    case Normal = 'normal';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::High => 'Prioritária',
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
