<?php

namespace App\Http\Requests\Students;

use App\Http\Requests\Concerns\HasPortugueseMessages;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentDisabilityRequest extends FormRequest
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
            // Formato CID-10: letra + 2 dígitos, com subcategoria opcional (ex.: F84, F84.0, H54.1).
            'cid_code' => ['required', 'string', 'max:10', 'regex:/^[A-Z][0-9]{2}(\.[0-9A-Z]{1,2})?$/'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cid_code' => 'CID',
            'description' => 'descrição',
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('cid_code');

        $this->merge([
            'cid_code' => is_string($code) ? strtoupper(trim($code)) : $code,
        ]);
    }
}
