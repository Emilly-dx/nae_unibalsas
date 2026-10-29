<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentDocument>
 */
class StudentDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'uploaded_by' => User::factory(),
            'title' => 'Laudo '.fake()->word(),
            'path' => 'students/0/documents/'.fake()->uuid().'.pdf',
            'original_name' => 'laudo.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
