<?php

namespace App\Http\Requests\Triage;

use App\Enums\Attendance\AttendanceModality;
use App\Enums\Attendance\AttendanceType;
use App\Enums\Triage\RequestPriority;
use App\Http\Requests\Concerns\HasPortugueseMessages;
use App\Support\Professionals;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Decisão da triagem: agendar a 1ª conversa (RF-019 a RF-021).
 */
class TriageScheduleRequest extends FormRequest
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
            'priority' => ['required', Rule::enum(RequestPriority::class)],
            'assigned_to' => ['required', 'integer', Rule::in(Professionals::ids())],
            'type' => ['required', Rule::enum(AttendanceType::class)],
            'modality' => ['required', Rule::enum(AttendanceModality::class)],
            'triage_notes' => ['nullable', 'string', 'max:5000'],
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
            'priority' => 'prioridade',
            'assigned_to' => 'profissional designado',
            'type' => 'tipo de atendimento',
            'modality' => 'modalidade',
            'triage_notes' => 'parecer da triagem',
            'date' => 'data da 1ª conversa',
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
            'assigned_to.in' => 'Selecione um profissional do NAE.',
            'date.after_or_equal' => 'A data da 1ª conversa não pode estar no passado.',
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
