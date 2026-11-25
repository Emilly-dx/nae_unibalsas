<?php

namespace App\Enums\Attendance;

/**
 * Modalidade do atendimento.
 */
enum AttendanceModality: string
{
    case Presential = 'presential';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::Presential => 'Presencial',
            self::Online => 'Online',
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
