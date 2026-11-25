<?php

namespace App\Http\Requests\Referrals;

use App\Enums\Referral\ReferralStatus;
use App\Http\Requests\Concerns\HasPortugueseMessages;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Atualização da situação e registro do retorno (RF-039, RF-040).
 */
class UpdateReferralStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(ReferralStatus::class)->only([
                ReferralStatus::InProgress, ReferralStatus::AwaitingReturn, ReferralStatus::Completed,
            ])],
            'return_notes' => [
                Rule::requiredIf($this->input('status') === ReferralStatus::Completed->value),
                'nullable', 'string', 'max:5000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'situação',
            'return_notes' => 'retorno',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->portugueseMessages(),
            'return_notes.required' => 'Para concluir, registre o retorno do encaminhamento.',
        ];
    }
}
