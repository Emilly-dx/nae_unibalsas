<?php

namespace App\Http\Requests\Referrals;

use App\Enums\Referral\ReferralKind;
use App\Http\Requests\Concerns\HasPortugueseMessages;
use App\Models\Attendance;
use App\Models\Student;
use App\Support\Professionals;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Novo encaminhamento (RF-034 a RF-038).
 */
class StoreReferralRequest extends FormRequest
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
        $internal = $this->input('kind') === ReferralKind::Internal->value;
        $userId = $this->user()?->id;

        return [
            'student_id' => ['required', 'integer', Rule::exists(Student::class, 'id')->whereNull('deleted_at')],
            'attendance_id' => [
                'nullable', 'integer',
                Rule::exists(Attendance::class, 'id')->where('student_id', $this->integer('student_id')),
            ],
            'kind' => ['required', Rule::enum(ReferralKind::class)],
            'destination_user_id' => $internal
                ? ['required', 'integer', Rule::in(array_values(array_diff(Professionals::ids(), [$userId])))]
                : ['prohibited'],
            'destination_name' => $internal ? ['prohibited'] : ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:5000'],
            'referred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'return_deadline' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:referred_on'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'estudante',
            'attendance_id' => 'atendimento de origem',
            'kind' => 'tipo',
            'destination_user_id' => 'profissional de destino',
            'destination_name' => 'serviço de destino',
            'reason' => 'motivo',
            'referred_on' => 'data do encaminhamento',
            'return_deadline' => 'prazo para retorno',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->portugueseMessages(),
            'destination_user_id.in' => 'Selecione outro profissional do NAE.',
            'referred_on.before_or_equal' => 'A data do encaminhamento não pode estar no futuro.',
            'return_deadline.after_or_equal' => 'O prazo para retorno deve ser igual ou posterior à data do encaminhamento.',
            'date_format' => 'Informe um valor válido para :attribute.',
            'prohibited' => 'O campo :attribute não se aplica a este tipo de encaminhamento.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'destination_user_id' => $this->input('destination_user_id') ?: null,
            'destination_name' => is_string($this->input('destination_name')) && trim($this->input('destination_name')) !== ''
                ? trim($this->input('destination_name'))
                : null,
            'attendance_id' => $this->input('attendance_id') ?: null,
        ]);
    }
}
