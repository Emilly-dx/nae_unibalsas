<?php

namespace App\Http\Requests\Students;

use App\Enums\AccessibilityResource;
use App\Enums\Modality;
use App\Enums\Sex;
use App\Enums\Shift;
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
            'social_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'sex' => ['nullable', Rule::enum(Sex::class)],
            'registration' => [
                'required', 'string', 'max:30',
                // Inclui inativos: matrícula de estudante inativo deve ser reativada, não duplicada.
                Rule::unique(Student::class, 'registration')
                    ->ignore($student instanceof Student ? $student->id : null),
            ],
            'course' => ['required', 'string', 'max:255'],
            'period' => ['required', 'integer', 'between:1,12'],
            'shift' => ['nullable', Rule::enum(Shift::class)],
            'modality' => ['required', Rule::enum(Modality::class)],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
            'guardian_relationship' => ['nullable', 'string', 'max:50'],
            'needs_accessibility' => ['boolean'],
            'accessibility_resources' => ['nullable', 'array'],
            'accessibility_resources.*' => [Rule::enum(AccessibilityResource::class)],
            'has_external_follow_up' => ['boolean'],
            ...$this->extraRules(),
        ];
    }

    /**
     * Regras específicas de cadastro/edição.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    abstract protected function extraRules(): array;

    /**
     * Dados do estudante, sem os campos de controle (consentimento, laudo).
     *
     * @return array<string, mixed>
     */
    public function studentData(): array
    {
        $data = $this->safe()->except(['consent', 'document', 'document_title']);

        if (! ($data['needs_accessibility'] ?? false)) {
            $data['accessibility_resources'] = [];
        }

        return $data;
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
            'social_name' => 'nome social',
            'shift' => 'turno',
            'modality' => 'modalidade',
            'guardian_name' => 'contato de emergência',
            'guardian_phone' => 'telefone de emergência',
            'guardian_relationship' => 'parentesco do contato de emergência',
            'accessibility_resources' => 'recursos de acessibilidade',
            'accessibility_resources.*' => 'recurso de acessibilidade',
            'consent' => 'consentimento',
            'document' => 'laudo ou documento',
            'document_title' => 'título do documento',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'registration' => is_string($this->input('registration'))
                ? trim($this->input('registration'))
                : $this->input('registration'),
            'modality' => $this->input('modality') ?: 'presential',
            'needs_accessibility' => $this->boolean('needs_accessibility'),
            'has_external_follow_up' => $this->boolean('has_external_follow_up'),
        ]);
    }
}
