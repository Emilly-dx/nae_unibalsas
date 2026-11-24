<?php

namespace App\Enums\Referral;

/**
 * Situação do encaminhamento (RF-039). Lista a validar com o NAE.
 */
enum ReferralStatus: string
{
    case Sent = 'sent';
    case InProgress = 'in_progress';
    case AwaitingReturn = 'awaiting_return';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Enviado',
            self::InProgress => 'Em andamento',
            self::AwaitingReturn => 'Aguardando retorno',
            self::Completed => 'Concluído',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Encaminhamentos ainda em aberto (sem retorno final).
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Sent, self::InProgress, self::AwaitingReturn];
    }

    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
