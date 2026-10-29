<?php

namespace App\Http\Requests\Students;

use App\Enums\Sex;
use App\Http\Requests\Concerns\HasPortugueseMessages;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Regras comuns de cadastro/edição de estudante (SRS §9.2).
 * A autorização é feita na rota (middleware permission:students.*).
 */
abstract class StudentRequest extends FormRequest
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
        $student = $this->route('student');

        return [
            'name' => ['required', 'string', 'max:255'],
            'registration' => [
                'required', 'string', 'max:30',
                // Inclui inativos: matrícula de estudante inativo deve ser reativada, não duplicada.
                Rule::unique(Student::class, 'registration')
                    ->ignore($student instanceof Student ? $student->id : null),
            ],
            'course' => ['required', 'string', 'max:255'],
            'period' => ['required', 'integer', 'between:1,12'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'sex' => ['nullable', Rule::enum(Sex::class)],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
            'guardian_relationship' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'registration' => 'matrícula',
            'course' => 'curso',
            'period' => 'período',
            'email' => 'e-mail',
            'phone' => 'telefone',
            'birth_date' => 'data de nascimento',
            'sex' => 'sexo',
            'guardian_name' => 'nome do responsável',
            'guardian_phone' => 'telefone do responsável',
            'guardian_relationship' => 'parentesco do responsável',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'registration' => is_string($this->input('registration'))
                ? trim($this->input('registration'))
                : $this->input('registration'),
        ]);
    }
}
