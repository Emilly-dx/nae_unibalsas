<?php

namespace App\Support;

use App\Models\Attendance;

/**
 * Dados de um atendimento enviados ao frontend. Nunca inclui o conteúdo
 * sigiloso (demanda, condução, combinados): isso é exposto à parte, só
 * para o profissional responsável.
 */
class AttendancePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function summary(Attendance $attendance): array
    {
        $student = $attendance->student;

        return [
            'id' => $attendance->id,
            'student' => [
                'id' => $student->id,
                'name' => $student->social_name ?: $student->name,
                'course' => $student->course,
                'period' => $student->period,
            ],
            'professional' => $attendance->professional->name,
            'professional_id' => $attendance->professional_id,
            'type' => $attendance->type->value,
            'type_label' => $attendance->type->label(),
            'status' => $attendance->status->value,
            'status_label' => $attendance->status->label(),
            'modality_label' => $attendance->modality->label(),
            'is_first_conversation' => $attendance->is_first_conversation,
            'scheduled_at' => $attendance->scheduled_at->toIso8601String(),
            'date' => $attendance->scheduled_at->format('d/m/Y'),
            'time' => $attendance->scheduled_at->format('H:i'),
            'planned_minutes' => $attendance->planned_minutes,
            'duration_minutes' => $attendance->durationMinutes(),
        ];
    }
}
