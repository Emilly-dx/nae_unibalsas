<?php

namespace App\Enums\FollowUp;

/**
 * Motivo do encerramento do acompanhamento. Lista a validar com o NAE.
 */
enum FollowUpClosureReason: string
{
    case GoalsMet = 'goals_met';
    case StudentRequest = 'student_request';
    case ExternalReferral = 'external_referral';
    case LeftInstitution = 'left_institution';
    case NoReturn = 'no_return';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::GoalsMet => 'Objetivos alcançados',
            self::StudentRequest => 'A pedido do estudante',
            self::ExternalReferral => 'Encaminhado a serviço externo',
            self::LeftInstitution => 'Desligamento ou conclusão do curso',
            self::NoReturn => 'Estudante não retornou',
            self::Other => 'Outro motivo',
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
