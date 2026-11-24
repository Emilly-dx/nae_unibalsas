<?php

use App\Enums\Attendance\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([ModuleSeeder::class, PermissionSeeder::class]);
});

function agendaProfessional(): User
{
    return userWithPermissions('attendances.view', 'attendances.create', 'attendances.schedule');
}

function rescheduleData(int $professionalId, array $overrides = []): array
{
    return [
        'professional_id' => $professionalId,
        'type' => 'psychological',
        'modality' => 'online',
        'date' => now()->addDays(4)->toDateString(),
        'time' => '15:00',
        'planned_minutes' => 50,
        ...$overrides,
    ];
}

describe('agenda (RF-041, RF-045)', function () {
    test('profissional vê a própria agenda por padrão e pode ver a de colegas', function () {
        $me = agendaProfessional();
        $colleague = agendaProfessional();
        $monday = now()->startOfWeek(CarbonInterface::MONDAY);
        Attendance::factory()->create(['professional_id' => $me->id, 'scheduled_at' => $monday->copy()->addDays(8)->setTime(9, 0)]);
        Attendance::factory()->create(['professional_id' => $colleague->id, 'scheduled_at' => $monday->copy()->addDays(8)->setTime(11, 0)]);
        $nextWeek = $monday->copy()->addWeek()->toDateString();

        $this->actingAs($me)->get(route('agenda.index', ['semana' => $nextWeek]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('agenda/Index')
                ->where('filters.profissional', 'meu')
                ->has('week.events', 1)
                ->where('week.events.0.professional_id', $me->id)
            );

        $this->actingAs($me)->get(route('agenda.index', ['semana' => $nextWeek, 'profissional' => $colleague->id]))
            ->assertInertia(fn (Assert $page) => $page->has('week.events', 1)->where('week.events.0.professional_id', $colleague->id));

        $this->actingAs($me)->get(route('agenda.index', ['semana' => $nextWeek, 'profissional' => '']))
            ->assertInertia(fn (Assert $page) => $page->has('week.events', 2));
    });

    test('quem não é profissional vê a agenda de todos', function () {
        Attendance::factory()->count(2)->create(['scheduled_at' => now()->addDay()->setTime(10, 0)]);

        $this->actingAs(userWithPermissions('attendances.view', 'attendances.schedule'))->get(route('agenda.index'))
            ->assertInertia(fn (Assert $page) => $page->where('filters.profissional', '')->where('isProfessional', false)->has('upcoming', 2));
    });

    test('próximos atendimentos: só agendados, nos próximos 14 dias, em ordem', function () {
        $later = Attendance::factory()->create(['scheduled_at' => now()->addDays(5)->setTime(10, 0)]);
        $sooner = Attendance::factory()->create(['scheduled_at' => now()->addDays(1)->setTime(10, 0)]);
        Attendance::factory()->create(['scheduled_at' => now()->addDays(30)]);
        Attendance::factory()->create(['scheduled_at' => now()->addDays(2), 'status' => AttendanceStatus::Cancelled]);

        $this->actingAs(userWithPermissions('attendances.view'))->get(route('agenda.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('upcoming', 2)
                ->where('upcoming.0.id', $sooner->id)
                ->where('upcoming.1.id', $later->id)
            );
    });

    test('sábado aparece na semana só quando há atendimento nele', function () {
        $monday = now()->startOfWeek(CarbonInterface::MONDAY)->addWeek();

        $this->actingAs(userWithPermissions('attendances.view'))->get(route('agenda.index', ['semana' => $monday->toDateString()]))
            ->assertInertia(fn (Assert $page) => $page->has('week.days', 5));

        Attendance::factory()->create(['scheduled_at' => $monday->copy()->addDays(5)->setTime(9, 0)]);

        $this->actingAs(userWithPermissions('attendances.view'))->get(route('agenda.index', ['semana' => $monday->toDateString()]))
            ->assertInertia(fn (Assert $page) => $page->has('week.days', 6)->where('week.events.0.day', 5));
    });

    test('sem attendances.view não acessa a agenda', function () {
        $this->actingAs(userWithPermissions('students.view'))->get(route('agenda.index'))->assertForbidden();
    });
});

describe('remarcação (RF-043)', function () {
    test('o profissional responsável remarca: data, horário, tipo e profissional', function () {
        $me = agendaProfessional();
        $colleague = agendaProfessional();
        $attendance = Attendance::factory()->create(['professional_id' => $me->id]);

        $this->actingAs($me)->get(route('attendances.edit', $attendance))
            ->assertInertia(fn (Assert $page) => $page->component('attendances/Reschedule'));

        $this->actingAs($me)
            ->put(route('attendances.update', $attendance), rescheduleData($colleague->id))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('attendances.show', $attendance));

        $attendance->refresh();

        expect($attendance->professional_id)->toBe($colleague->id)
            ->and($attendance->scheduled_at->format('H:i'))->toBe('15:00')
            ->and($attendance->type->value)->toBe('psychological')
            ->and($attendance->student_id)->not->toBeNull();
    });

    test('remarcar para o mesmo horário dele não conta como conflito; outro horário ocupado sim', function () {
        $me = agendaProfessional();
        $attendance = Attendance::factory()->create(['professional_id' => $me->id, 'scheduled_at' => now()->addDays(4)->setTime(15, 0)]);

        $this->actingAs($me)
            ->put(route('attendances.update', $attendance), rescheduleData($me->id, ['planned_minutes' => 60]))
            ->assertSessionHasNoErrors();

        Attendance::factory()->create(['professional_id' => $me->id, 'scheduled_at' => now()->addDays(5)->setTime(10, 0)]);

        $this->actingAs($me)
            ->put(route('attendances.update', $attendance), rescheduleData($me->id, ['date' => now()->addDays(5)->toDateString(), 'time' => '10:30']))
            ->assertSessionHasErrors('time');
    });

    test('não remarca para o passado nem atendimento já registrado', function () {
        $me = agendaProfessional();
        $attendance = Attendance::factory()->create(['professional_id' => $me->id]);

        $this->actingAs($me)
            ->put(route('attendances.update', $attendance), rescheduleData($me->id, ['date' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('date');

        $registered = Attendance::factory()->registered()->create(['professional_id' => $me->id]);

        $this->actingAs($me)->put(route('attendances.update', $registered), rescheduleData($me->id))->assertForbidden();
    });

    test('quem não é o responsável nem agenda não remarca', function () {
        $attendance = Attendance::factory()->create(['professional_id' => agendaProfessional()->id]);

        $this->actingAs(userWithPermissions('attendances.view', 'attendances.create'))
            ->put(route('attendances.update', $attendance), rescheduleData($attendance->professional_id))
            ->assertForbidden();
    });

    test('a remarcação fica registrada na auditoria', function () {
        $me = agendaProfessional();
        $attendance = Attendance::factory()->create(['professional_id' => $me->id]);

        $this->actingAs($me)->put(route('attendances.update', $attendance), rescheduleData($me->id));

        $log = Activity::query()
            ->where('log_name', 'attendances')->where('event', 'updated')->latest('id')->first();

        expect($log)->not->toBeNull()->and($log?->causer_id)->toBe($me->id);
    });
});
