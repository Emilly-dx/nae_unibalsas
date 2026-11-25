<?php

namespace Database\Seeders;

use App\Enums\Attendance\AttendanceModality;
use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Attendance\AttendanceType;
use App\Enums\Referral\ReferralKind;
use App\Enums\Referral\ReferralStatus;
use App\Enums\Triage\RequestPriority;
use App\Enums\Triage\RequestStatus;
use App\Models\Attendance;
use App\Models\Referral;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Dados FICTÍCIOS para demonstração e testes manuais (contas de cada perfil,
 * estudantes, solicitações e atendimentos). Nunca rode em produção.
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * As contas usam a senha de NAE_DEMO_PASSWORD (padrão "password", só no
 * computador local). Num servidor de testes (APP_ENV=staging), defina uma
 * senha forte em NAE_DEMO_PASSWORD antes de rodar.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoDataSeeder não pode ser executado em produção.');
        }

        if (! app()->environment(['local', 'testing']) && $this->password() === 'password') {
            throw new RuntimeException('Fora do computador local, defina uma senha forte em NAE_DEMO_PASSWORD.');
        }

        $this->call([ModuleSeeder::class, PermissionSeeder::class, RoleSeeder::class, RolePermissionSeeder::class]);

        $psychopedagogue = $this->account('psychopedagogue', 'Paula Psicopedagoga', 'psicopedagoga@nae.test');
        $psychologist = $this->account('psychologist', 'Lúcia Psicóloga', 'psicologa@nae.test');
        $pedagogue = $this->account('pedagogue', 'Rita Pedagoga', 'pedagoga@nae.test');
        $this->account('coordinator', 'Carla Coordenadora', 'coordenacao@nae.test');
        $assistant = $this->account('assistant', 'Sônia Assistente', 'assistente@nae.test');

        $students = Student::factory()->count(18)->create();
        $monday = CarbonImmutable::today()->startOfWeek(CarbonInterface::MONDAY);
        $professionals = [$psychopedagogue, $psychologist, $pedagogue];

        // Fila de triagem.
        foreach ($students->take(4) as $index => $student) {
            $request = SupportRequest::factory()->for($student)->create([
                'created_by' => $assistant->id,
                'created_at' => now()->subDays(4 - $index),
            ]);

            if ($index === 1) {
                $request->priority = RequestPriority::High;
                $request->save();
            }
        }

        // Agenda desta semana e da próxima.
        foreach ($students->slice(4, 8)->values() as $index => $student) {
            $professional = $professionals[$index % 3];
            $day = $monday->addDays($index % 5)->addWeeks(intdiv($index, 5));
            $start = $day->setTime(8 + ($index * 2) % 9, $index % 2 === 0 ? 0 : 30);

            $attendance = new Attendance([
                'type' => [AttendanceType::Psychopedagogical, AttendanceType::Psychological, AttendanceType::Listening][$index % 3],
                'modality' => AttendanceModality::Presential,
                'scheduled_at' => $start,
                'planned_minutes' => 50,
                'is_first_conversation' => $index === 0,
            ]);
            $attendance->student()->associate($student);
            $attendance->professional_id = $professional->id;
            $attendance->scheduled_by = $assistant->id;

            if ($start->isPast()) {
                $attendance->status = AttendanceStatus::Attended;
                $attendance->started_at = $start;
                $attendance->ended_at = $start->addMinutes(45);
                $attendance->demand = 'Registro fictício de demonstração.';
                $attendance->registered_at = $start->addHour();
            } else {
                $attendance->status = AttendanceStatus::Scheduled;
            }

            $attendance->save();
        }

        // Histórico do semestre.
        foreach ($students->slice(12)->values() as $index => $student) {
            foreach (range(1, 3) as $week) {
                $date = CarbonImmutable::today()->subWeeks($week + $index)->setTime(10, 0);
                $attendance = new Attendance([
                    'type' => AttendanceType::Psychopedagogical,
                    'modality' => AttendanceModality::Presential,
                    'scheduled_at' => $date,
                    'planned_minutes' => 50,
                    'started_at' => $date,
                    'ended_at' => $date->addMinutes(50),
                    'demand' => 'Registro fictício de demonstração.',
                ]);
                $attendance->student()->associate($student);
                $attendance->professional_id = $psychopedagogue->id;
                $attendance->scheduled_by = $psychopedagogue->id;
                $attendance->status = $week === 3 ? AttendanceStatus::Completed : AttendanceStatus::Attended;
                $attendance->registered_at = $date->addHour();
                $attendance->save();
            }
        }

        // Encaminhamentos.
        $referrals = [
            [$students[12], ReferralKind::Internal, $psychologist, null, ReferralStatus::InProgress, 10],
            [$students[13], ReferralKind::External, null, 'Clínica UNIBALSAS', ReferralStatus::AwaitingReturn, 20],
            [$students[14], ReferralKind::External, null, 'Coordenação do curso', ReferralStatus::Completed, 30],
            [$students[15], ReferralKind::External, null, 'CIA — Comitê de Inclusão e Acessibilidade', ReferralStatus::Sent, 3],
        ];

        foreach ($referrals as [$student, $kind, $destination, $service, $status, $daysAgo]) {
            $referral = new Referral([
                'kind' => $kind,
                'destination_name' => $service,
                'reason' => 'Motivo fictício de demonstração.',
                'referred_on' => CarbonImmutable::today()->subDays($daysAgo),
                'return_deadline' => CarbonImmutable::today()->subDays($daysAgo)->addDays(15),
            ]);
            $referral->student()->associate($student);
            $referral->created_by = $psychopedagogue->id;
            $referral->destination_user_id = $destination?->id;
            $referral->status = $status;

            if ($status === ReferralStatus::Completed) {
                $referral->return_notes = 'Retorno fictício de demonstração.';
                $referral->returned_at = CarbonImmutable::today()->subDays(5);
            }

            $referral->save();
        }

        SupportRequest::factory()->for($students[12])->create([
            'status' => RequestStatus::Scheduled,
            'created_by' => $assistant->id,
        ]);
    }

    private function account(string $role, string $name, string $email): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill(['name' => $name, 'password' => $this->password(), 'email_verified_at' => now()])->save();
        $user->assignRole($role);

        return $user;
    }

    private function password(): string
    {
        $password = config('nae.demo_password');

        return is_string($password) && $password !== '' ? $password : 'password';
    }
}
