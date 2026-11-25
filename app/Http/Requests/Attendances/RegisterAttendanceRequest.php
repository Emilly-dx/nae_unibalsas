<?php

namespace App\Http\Requests\Attendances;

use App\Enums\Attendance\AttendanceModality;
use App\Enums\Attendance\AttendanceType;
use App\Http\Requests\Concerns\HasPortugueseMessages;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registro do atendimento realizado pelo profissional (RF-024 a RF-028, RN-008).
 * Serve tanto para registrar um agendamento quanto um atendimento sem agendamento.
 */
class RegisterAttendanceRequest extends FormRequest
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
        $isNew = ! $this->route('attendance') instanceof Attendance;

        return [
            'student_id' => $isNew
                ? ['required', 'integer', Rule::exists(Student::class, 'id')->whereNull('deleted_at')]
                : ['prohibited'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_with:start_time', 'after:start_time'],
            'type' => ['required', Rule::enum(AttendanceType::class)],
            'modality' => ['required', Rule::enum(AttendanceModality::class)],
            'demand' => ['required', 'string', 'max:10000'],
            'conduct' => ['nullable', 'string', 'max:10000'],
            'agreements' => ['nullable', 'string', 'max:5000'],
            'next_return_on' => ['nullable', 'date_format:Y-m-d', 'after:date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'estudante',
            'date' => 'data',
            'start_time' => 'início',
            'end_time' => 'término',
            'type' => 'tipo de atendimento',
            'modality' => 'modalidade',
            'demand' => 'demanda apresentada',
            'conduct' => 'condução e observações',
            'agreements' => 'combinados e próximos passos',
            'next_return_on' => 'próximo retorno',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->portugueseMessages(),
            'date.before_or_equal' => 'O atendimento só pode ser registrado depois de acontecer.',
            'end_time.after' => 'O término deve ser depois do início.',
            'end_time.required_with' => 'Informe o término do atendimento.',
            'next_return_on.after' => 'O próximo retorno deve ser depois da data do atendimento.',
            'date_format' => 'Informe um valor válido para :attribute.',
        ];
    }
}
