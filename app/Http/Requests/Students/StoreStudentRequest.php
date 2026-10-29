<?php

namespace App\Http\Requests\Students;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreStudentRequest extends StudentRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function extraRules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            // LGPD: o cadastro só é feito com o consentimento registrado.
            'consent' => ['accepted'],
            // Laudo opcional no cadastro: dado sensível, exige students.sensitive (RN-012).
            'document' => $user->can('students.sensitive')
                ? ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.StoreStudentDocumentRequest::MAX_KILOBYTES]
                : ['prohibited'],
            'document_title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
