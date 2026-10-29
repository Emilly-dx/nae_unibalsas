<?php

namespace App\Http\Requests\Students;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateStudentRequest extends StudentRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function extraRules(): array
    {
        return [
            // Permite registrar o consentimento de cadastros antigos que não o tinham.
            'consent' => ['nullable', 'boolean'],
        ];
    }
}
