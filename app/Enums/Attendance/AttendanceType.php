<?php

namespace App\Enums\Attendance;

/**
 * Tipos de atendimento (SRS §10.4, RN-006).
 */
enum AttendanceType: string
{
    case Psychopedagogical = 'psychopedagogical';
    case Psychological = 'psychological';
    case Listening = 'listening';

    public function label(): string
    {
        return match ($this) {
            self::Psychopedagogical => 'Psicopedagógico',
            self::Psychological => 'Psicológico',
            self::Listening => 'Escuta e orientação',
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
