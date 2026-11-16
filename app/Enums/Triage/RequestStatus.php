<?php

namespace App\Enums\Triage;

/**
 * Situação da solicitação de apoio.
 */
enum RequestStatus: string
{
    case PendingTriage = 'pending_triage';
    case Scheduled = 'scheduled';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::PendingTriage => 'Aguardando triagem',
            self::Scheduled => '1ª conversa agendada',
            self::Returned => 'Devolvida à coordenação',
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
