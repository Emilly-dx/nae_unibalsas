<?php

namespace App\Http\Requests\Reports;

use App\Enums\Attendance\AttendanceType;
use App\Enums\Modality;
use App\Http\Requests\Concerns\HasPortugueseMessages;
use App\Support\Reports\ReportFilters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
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
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', 'after_or_equal:de'],
            'tipo' => ['nullable', Rule::enum(AttendanceType::class)],
            'profissional' => ['nullable', 'integer'],
            'curso' => ['nullable', 'string', 'max:255'],
            'modalidade' => ['nullable', Rule::enum(Modality::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->portugueseMessages(),
            'ate.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
        ];
    }

    public function filters(): ReportFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return ReportFilters::fromArray($data);
    }
}
