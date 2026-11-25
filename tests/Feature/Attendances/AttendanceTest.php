<?php

use App\Enums\Attendance\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([ModuleSeeder::class, PermissionSeeder::class]);
});

function professionalUser(): User
{
    return userWithPermissions('attendances.view', 'attendances.create', 'attendances.finish', 'attendances.schedule', 'students.view');
}

function registerData(array $overrides = []): array
{
    return [
        'date' => now()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:45',
        'type' => 'psychopedagogical',
        'modality' => 'presential',
        'demand' => 'Dificuldade com a organização dos estudos.',
        'conduct' => 'Escuta e construção de rotina semanal.',
        'agreements' => 'Retornar com a rotina preenchida.',
        'next_return_on' => now()->addWeek()->toDateString(),
        ...$overrides,
    ];
}

describe('agendamento (RF-022, RN-007)', function () {
    test('quem tem attendances.schedule agenda para um profissional', function () {
        $professional = professionalUser();
        $student = Student::factory()->create();

        $this->actingAs(userWithPermissions('attendances.schedule'))
            ->post(route('attendances.store'), [
                'student_id' => $student->id,
                'professional_id' => $professional->id,
                'type' => 'psychological',
                'modality' => 'online',
                'date' => now()->addDays(3)->toDateString(),
                'time' => '14:00',
                'planned_minutes' => 50,
            ])
            ->assertSessionHasNoErrors();

        $attendance = Attendance::query()->sole();

        expect($attendance->status)->toBe(AttendanceStatus::Scheduled)
            ->and($attendance->professional_id)->toBe($professional->id);
    });

    test('não agenda no passado', function () {
        $this->actingAs(userWithPermissions('attendances.schedule'))
            ->post(route('attendances.store'), [
                'student_id' => Student::factory()->create()->id,
                'professional_id' => professionalUser()->id,
                'type' => 'psychological',
                'modality' => 'online',
                'date' => now()->subDay()->toDateString(),
                'time' => '14:00',
                'planned_minutes' => 50,
            ])
            ->assertSessionHasErrors('date');
    });

    test('a agenda da semana lista os agendamentos não cancelados', function () {
        $monday = now()->startOfWeek(CarbonInterface::MONDAY);
        Attendance::factory()->create(['scheduled_at' => $monday->copy()->addDays(1)->setTime(10, 0)]);
        Attendance::factory()->create(['scheduled_at' => $monday->copy()->addDays(2)->setTime(10, 0), 'status' => AttendanceStatus::Cancelled]);

        $this->actingAs(userWithPermissions('attendances.view'))
            ->get(route('triage.index', ['aba' => 'agenda', 'semana' => $monday->toDateString()]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('week.events', 1)
                ->where('week.events.0.day', 1)
            );
    });
});

describe('registro do atendimento (RF-024 a RF-028, RN-008)', function () {
    test('o profissional registra o atendimento agendado', function () {
        $professional = professionalUser();
        $attendance = Attendance::factory()->create([
            'professional_id' => $professional->id,
            'scheduled_at' => now()->setTime(9, 0),
        ]);

        $this->actingAs($professional)
            ->put(route('attendances.register.update', $attendance), registerData())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('attendances.show', $attendance));

        $attendance->refresh();

        expect($attendance->status)->toBe(AttendanceStatus::Attended)
            ->and($attendance->durationMinutes())->toBe(45)
            ->and($attendance->demand)->toBe('Dificuldade com a organização dos estudos.')
            ->and($attendance->registered_at)->not->toBeNull();
    });

    test('registra atendimento sem agendamento prévio', function () {
        $professional = professionalUser();
        $student = Student::factory()->create();

        $this->actingAs($professional)
            ->post(route('attendances.register.store'), registerData(['student_id' => $student->id, 'type' => 'listening']))
            ->assertSessionHasNoErrors();

        $attendance = Attendance::query()->sole();

        expect($attendance->professional_id)->toBe($professional->id)
            ->and($attendance->status)->toBe(AttendanceStatus::Attended);
    });

    test('o conteúdo do registro é criptografado no banco', function () {
        $this->actingAs(professionalUser())
            ->post(route('attendances.register.store'), registerData(['student_id' => Student::factory()->create()->id]));

        expect(DB::table('attendances')->value('demand'))->not->toContain('organização');
    });

    test('valida demanda, horário e data futura', function () {
        $this->actingAs(professionalUser())
            ->post(route('attendances.register.store'), registerData([
                'student_id' => Student::factory()->create()->id,
                'demand' => '',
                'end_time' => '08:00',
                'date' => now()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors(['demand', 'end_time', 'date']);
    });

    test('outro profissional não registra o atendimento de um colega', function () {
        $attendance = Attendance::factory()->create(['professional_id' => professionalUser()->id]);

        $this->actingAs(professionalUser())
            ->put(route('attendances.register.update', $attendance), registerData())
            ->assertForbidden();
    });
});

describe('sigilo (RN-012)', function () {
    test('só o profissional responsável vê o conteúdo, nem o administrador', function () {
        $professional = professionalUser();
        $attendance = Attendance::factory()->registered()->create(['professional_id' => $professional->id]);

        $this->actingAs($professional)->get(route('attendances.show', $attendance))
            ->assertInertia(fn (Assert $page) => $page->where('content.demand', 'Demanda de teste'));

        $this->actingAs(professionalUser())->get(route('attendances.show', $attendance))
            ->assertInertia(fn (Assert $page) => $page->where('content', null));

        $this->actingAs(administrator())->get(route('attendances.show', $attendance))
            ->assertInertia(fn (Assert $page) => $page->where('content', null)->where('can.register', false));
    });

    test('o log de auditoria não guarda o conteúdo do atendimento', function () {
        $this->actingAs(professionalUser())
            ->post(route('attendances.register.store'), registerData(['student_id' => Student::factory()->create()->id]));

        $logs = Activity::query()->where('log_name', 'attendances')->get();

        expect($logs)->not->toBeEmpty()
            ->and(json_encode($logs->pluck('attribute_changes')))->not->toContain('organiza');
    });
});

describe('status (RF-029)', function () {
    test('cancelar um agendamento', function () {
        $attendance = Attendance::factory()->create();

        $this->actingAs(userWithPermissions('attendances.schedule'))
            ->patch(route('attendances.cancel', $attendance), ['cancellation_reason' => 'Estudante pediu remarcação'])
            ->assertRedirect();

        expect($attendance->fresh()?->status)->toBe(AttendanceStatus::Cancelled);
    });

    test('atendimento registrado não pode ser cancelado', function () {
        $attendance = Attendance::factory()->registered()->create();

        $this->actingAs(userWithPermissions('attendances.schedule'))
            ->patch(route('attendances.cancel', $attendance))
            ->assertForbidden();
    });

    test('o profissional conclui um atendimento registrado', function () {
        $professional = professionalUser();
        $attendance = Attendance::factory()->registered()->create(['professional_id' => $professional->id]);

        $this->actingAs($professional)->patch(route('attendances.finish', $attendance))->assertRedirect();

        expect($attendance->fresh()?->status)->toBe(AttendanceStatus::Completed);
    });
});

describe('histórico e painel', function () {
    test('a página do estudante mostra o histórico sem o conteúdo sigiloso', function () {
        $attendance = Attendance::factory()->registered()->create();

        $this->actingAs(userWithPermissions('students.view', 'attendances.view'))
            ->get(route('students.show', $attendance->student_id))
            ->assertInertia(fn (Assert $page) => $page
                ->has('history.attendances', 1)
                ->missing('history.attendances.0.demand')
            );
    });

    test('o painel conta os atendimentos de hoje', function () {
        Attendance::factory()->create(['scheduled_at' => now()->setTime(23, 0)]);
        Attendance::factory()->create(['scheduled_at' => now()->addDays(3)]);

        $this->actingAs(userWithPermissions('attendances.view'))
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('stats.today', 1)->has('todayAgenda', 1));
    });
});

test('as mensagens de validação do registro ficam em português', function () {
    $this->actingAs(professionalUser())
        ->post(route('attendances.register.store'), registerData(['student_id' => null]))
        ->assertSessionHasErrors(['student_id' => 'O campo estudante é obrigatório.']);
});
