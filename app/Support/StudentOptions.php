<?php

namespace App\Support;

use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Estudantes ativos para os campos de busca dos formulários.
 */
class StudentOptions
{
    /**
     * @return Collection<int, array{id: int, name: string, registration: string, course: string, period: int}>
     */
    public static function all(): Collection
    {
        return Student::query()->orderBy('name')
            ->get(['id', 'name', 'social_name', 'registration', 'course', 'period'])
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'name' => $student->social_name ?: $student->name,
                'registration' => $student->registration,
                'course' => $student->course,
                'period' => $student->period,
            ])
            ->values();
    }
}
