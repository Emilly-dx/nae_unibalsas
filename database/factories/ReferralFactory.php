<?php

namespace Database\Factories;

use App\Enums\Referral\ReferralKind;
use App\Enums\Referral\ReferralStatus;
use App\Models\Referral;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referral>
 */
class ReferralFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'created_by' => User::factory(),
            'kind' => ReferralKind::External,
            'destination_user_id' => null,
            'destination_name' => 'Clínica UNIBALSAS',
            'reason' => 'Motivo de teste',
            'referred_on' => now()->subDays(2)->toDateString(),
            'return_deadline' => null,
            'status' => ReferralStatus::Sent,
        ];
    }

    public function internal(User $destination): static
    {
        return $this->state(fn (): array => [
            'kind' => ReferralKind::Internal,
            'destination_user_id' => $destination->id,
            'destination_name' => null,
        ]);
    }
}
