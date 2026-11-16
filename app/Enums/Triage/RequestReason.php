<?php

namespace App\Enums\Triage;

/**
 * Motivos do Formulário de Solicitação do Apoio do NAE (lista a validar com o NAE).
 */
enum RequestReason: string
{
    case LearningDifficulties = 'learning_difficulties';
    case StudyRoutine = 'study_routine';
    case Emotional = 'emotional';
    case CourseAdaptation = 'course_adaptation';
    case AdaptedAssessment = 'adapted_assessment';
    case DropoutRisk = 'dropout_risk';
    case Accessibility = 'accessibility';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LearningDifficulties => 'Dificuldades de aprendizagem',
            self::StudyRoutine => 'Organização da rotina de estudos',
            self::Emotional => 'Questões emocionais',
            self::CourseAdaptation => 'Adaptação ao curso',
            self::AdaptedAssessment => 'Avaliação adaptada',
            self::DropoutRisk => 'Faltas frequentes ou risco de evasão',
            self::Accessibility => 'Acessibilidade',
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
