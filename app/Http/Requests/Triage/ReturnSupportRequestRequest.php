<?php

namespace App\Http\Requests\Triage;

use App\Http\Requests\Concerns\HasPortugueseMessages;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Decisão da triagem: devolver a solicitação à coordenação.
 */
class ReturnSupportRequestRequest extends FormRequest
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
            'triage_notes' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['triage_notes' => 'motivo da devolução'];
    }
}
