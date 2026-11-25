<?php

namespace App\Http\Requests\Attendances;

use App\Enums\Attendance\AttendanceModality;
use App\Enums\Attendance\AttendanceType;
use App\Http\Requests\Concerns\HasPortugueseMessages;
use App\Models\Student;
use App\Support\Professionals;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Novo agendamento (RF-022, RN-007).
 */
class ScheduleAttendanceRequest extends FormRequest
{
    use HasPortugueseMessages {
        messages as portugueseMessages;
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists(Student::class, 'id')->whereNull('deleted_at')],
            'professional_id' => ['required', 'integer', Rule::in(Professionals::ids())],
            'type' => ['required', Rule::enum(AttendanceType::class)],
            'modality' => ['required', Rule::enum(AttendanceModality::class)],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'planned_minutes' => ['required', 'integer', 'between:15,240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'estudante',
            'professional_id' => 'profissional',
            'type' => 'tipo de atendimento',
            'modality' => 'modalidade',
            'date' => 'data',
            'time' => 'horário',
            'planned_minutes' => 'duração prevista',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->portugueseMessages(),
            'professional_id.in' => 'Selecione um profissional do NAE.',
            'date.after_or_equal' => 'A data do agendamento não pode estar no passado.',
            'date_format' => 'Informe um valor válido para :attribute.',
        ];
    }

    /**
     * Data, horário e características do agendamento, já validados.
     *
     * @return array{date: string, time: string, planned_minutes: int, type: string, modality: string}
     */
    public function slot(): array
    {
        return [
            'date' => $this->string('date')->toString(),
            'time' => $this->string('time')->toString(),
            'planned_minutes' => $this->integer('planned_minutes'),
            'type' => $this->string('type')->toString(),
            'modality' => $this->string('modality')->toString(),
        ];
    }
}
