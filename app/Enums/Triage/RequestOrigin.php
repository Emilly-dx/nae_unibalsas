<?php

namespace App\Enums\Triage;

/**
 * Origem da solicitação de apoio (RN-001).
 */
enum RequestOrigin: string
{
    case CourseCoordination = 'course_coordination';
    case Teacher = 'teacher';
    case Spontaneous = 'spontaneous';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CourseCoordination => 'Coordenação do curso',
            self::Teacher => 'Docente da turma',
            self::Spontaneous => 'Procura espontânea',
            self::Other => 'Outro',
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
