<?php

namespace Database\Factories;

use App\Enums\Triage\RequestOrigin;
use App\Enums\Triage\RequestPriority;
use App\Enums\Triage\RequestReason;
use App\Enums\Triage\RequestStatus;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportRequest>
 */
class SupportRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'created_by' => User::factory(),
            'requester_name' => fake()->firstName().' '.fake()->lastName(),
            'requester_email' => fake()->safeEmail(),
            'origin' => fake()->randomElement(RequestOrigin::cases()),
            'reasons' => [fake()->randomElement(RequestReason::cases())->value],
            'description' => fake()->randomElement([
                'Estudante relata dificuldade para acompanhar as aulas e organizar a rotina de estudos.',
                'Faltas frequentes nas últimas semanas; a coordenação percebeu queda nas notas.',
                'Estudante pediu orientação sobre adaptação de avaliações.',
                'Relato de ansiedade em períodos de prova, procurou a coordenação espontaneamente.',
                'Docente da turma observou isolamento e pouca participação nas atividades.',
                'Estudante recém-chegado com dificuldade de adaptação à rotina acadêmica.',
            ]).' (texto fictício)',
            'student_aware' => true,
            'status' => RequestStatus::PendingTriage,
            'priority' => RequestPriority::Normal,
        ];
    }

    public function highPriority(): static
    {
        return $this->state(fn (): array => ['priority' => RequestPriority::High]);
    }
}
