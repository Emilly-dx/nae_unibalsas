<?php

namespace App\Enums\Attendance;

/**
 * Status do atendimento (SRS §10.5). A diferença entre ATENDIDO e CONCLUÍDO ainda será validada com o NAE.
 */
enum AttendanceStatus: string
{
    case Scheduled = 'scheduled';
    case Attended = 'attended';
    case Completed = 'completed';
    case Referred = 'referred';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendado',
            self::Attended => 'Atendido',
            self::Completed => 'Concluído',
            self::Referred => 'Encaminhado',
            self::Cancelled => 'Cancelado',
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
