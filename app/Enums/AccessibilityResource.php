<?php

namespace App\Enums;

/**
 * Recursos de acessibilidade necessários (wireframe "Apoio e acessibilidade").
 * Lista a validar com o NAE/CIA.
 */
enum AccessibilityResource: string
{
    case AdaptedAssessment = 'adapted_assessment';
    case ExtraTime = 'extra_time';
    case AccessibleMaterial = 'accessible_material';
    case LibrasInterpreter = 'libras_interpreter';
    case ClassroomSupport = 'classroom_support';
    case ReferToCia = 'refer_to_cia';

    public function label(): string
    {
        return match ($this) {
            self::AdaptedAssessment => 'Avaliação adaptada',
            self::ExtraTime => 'Tempo adicional em provas',
            self::AccessibleMaterial => 'Material em formato acessível',
            self::LibrasInterpreter => 'Intérprete de Libras',
            self::ClassroomSupport => 'Apoio em sala de aula',
            self::ReferToCia => 'Encaminhar ao CIA',
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
