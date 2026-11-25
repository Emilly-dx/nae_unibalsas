<?php

namespace App\Http\Requests\Attendances;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Remarcação de um agendamento (RF-043): as mesmas regras do agendamento,
 * sem trocar o estudante.
 */
class RescheduleAttendanceRequest extends ScheduleAttendanceRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['student_id']);

        return $rules;
    }
}
