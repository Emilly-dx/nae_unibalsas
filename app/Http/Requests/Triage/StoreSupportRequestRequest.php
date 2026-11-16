<?php

namespace App\Http\Requests\Triage;

use App\Enums\Triage\RequestOrigin;
use App\Enums\Triage\RequestReason;
use App\Http\Requests\Concerns\HasPortugueseMessages;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulário de Solicitação do Apoio do NAE (RN-001).
 */
class StoreSupportRequestRequest extends FormRequest
{
    use HasPortugueseMessages;

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
            // Somente estudantes ativos recebem novas solicitações.
            'student_id' => ['required', 'integer', Rule::exists(Student::class, 'id')->whereNull('deleted_at')],
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_email' => ['nullable', 'email', 'max:255'],
            'origin' => ['required', Rule::enum(RequestOrigin::class)],
            'reasons' => ['nullable', 'array'],
            'reasons.*' => [Rule::enum(RequestReason::class)],
            'description' => ['required', 'string', 'max:5000'],
            'student_aware' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'estudante',
            'requester_name' => 'solicitante',
            'requester_email' => 'e-mail do solicitante',
            'origin' => 'origem',
            'reasons' => 'motivos',
            'reasons.*' => 'motivo',
            'description' => 'descrição da situação',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['student_aware' => $this->boolean('student_aware', true)]);
    }
}
