<?php

namespace App\Enums\FollowUp;

/**
 * Situação atual do acompanhamento do estudante (SRS §11, RF-033). Derivada dos atendimentos; não é um registro próprio (RN-009).
 */
enum FollowUpStatus: string
{
    case None = 'none';
    case Waiting = 'waiting';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Sem acompanhamento',
            self::Waiting => 'Aguardando 1º atendimento',
            self::Active => 'Em acompanhamento',
            self::Closed => 'Acompanhamento encerrado',
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
