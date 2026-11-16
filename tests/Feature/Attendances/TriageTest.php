<?php

use App\Enums\Triage\RequestStatus;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\SupportRequest;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([ModuleSeeder::class, PermissionSeeder::class]);
});

function triageData(array $overrides = []): array
{
    return [
        'requester_name' => 'Coordenação de Direito',
        'requester_email' => 'direito@unibalsas.edu.br',
        'origin' => 'course_coordination',
        'reasons' => ['course_adaptation', 'emotional'],
        'description' => 'Estudante com dificuldade de adaptação ao curso.',
        'student_aware' => '1',
        ...$overrides,
    ];
}

function triageDecision(int $professionalId, array $overrides = []): array
{
    return [
        'priority' => 'high',
        'assigned_to' => $professionalId,
        'type' => 'psychopedagogical',
        'modality' => 'presential',
        'triage_notes' => 'Demanda de adaptação; iniciar com a psicopedagogia.',
        'date' => now()->addDays(2)->toDateString(),
        'time' => '10:00',
        'planned_minutes' => 50,
        ...$overrides,
    ];
}

describe('solicitações de apoio (RN-001)', function () {
    test('quem tem triage.create registra uma solicitação, que entra na fila', function () {
        $student = Student::factory()->create();

        $this->actingAs(userWithPermissions('triage.create'))
            ->post(route('triage.requests.store'), ['student_id' => $student->id, ...triageData()])
            ->assertRedirect(route('triage.index'));

        $request = SupportRequest::query()->sole();

        expect($request->status)->toBe(RequestStatus::PendingTriage)
            ->and($request->reasonLabels())->toBe(['Adaptação ao curso', 'Questões emocionais']);
    });

    test('a descrição da situação é criptografada no banco', function () {
        $this->actingAs(userWithPermissions('triage.create'))
            ->post(route('triage.requests.store'), ['student_id' => Student::factory()->create()->id, ...triageData()]);

        expect(DB::table('support_requests')->value('description'))->not->toContain('adaptação');
    });

    test('valida campos obrigatórios e não aceita estudante inativo', function () {
        $inactive = Student::factory()->inactive()->create();

        $this->actingAs(userWithPermissions('triage.create'))
            ->post(route('triage.requests.store'), ['student_id' => $inactive->id, 'origin' => 'course_coordination'])
            ->assertSessionHasErrors(['student_id', 'requester_name', 'description']);
    });

    test('sem triage.create não registra solicitação', function () {
        $this->actingAs(userWithPermissions('triage.view'))
            ->post(route('triage.requests.store'), ['student_id' => Student::factory()->create()->id, ...triageData()])
            ->assertForbidden();
    });
});

describe('fila de triagem', function () {
    test('mostra só as pendentes, prioritárias primeiro', function () {
        SupportRequest::factory()->create(['created_at' => now()->subDays(3)]);
        $high = SupportRequest::factory()->highPriority()->create(['created_at' => now()->subDay()]);
        SupportRequest::factory()->create(['status' => RequestStatus::Returned]);

        $this->actingAs(userWithPermissions('triage.view'))
            ->get(route('triage.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('triage/Index')
                ->has('queue', 2)
                ->where('queue.0.id', $high->id)
            );
    });

    test('sem triage.view e sem attendances.view a tela é negada', function () {
        $this->actingAs(userWithPermissions('students.view'))
            ->get(route('triage.index'))
            ->assertForbidden();
    });
});

describe('decisão da triagem (RF-019 a RF-021)', function () {
    test('agendar a 1ª conversa cria o agendamento e muda a situação', function () {
        $professional = userWithPermissions('attendances.create');
        $triager = userWithPermissions('triage.view', 'triage.perform');
        $request = SupportRequest::factory()->create();

        $this->actingAs($triager)
            ->post(route('triage.requests.schedule', $request), triageDecision($professional->id))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $attendance = Attendance::query()->sole();
        $request->refresh();

        expect($request->status)->toBe(RequestStatus::Scheduled)
            ->and($request->assigned_to)->toBe($professional->id)
            ->and($request->triaged_by)->toBe($triager->id)
            ->and($attendance->is_first_conversation)->toBeTrue()
            ->and($attendance->professional_id)->toBe($professional->id)
            ->and($attendance->support_request_id)->toBe($request->id);
    });

    test('só profissionais do NAE podem ser designados', function () {
        $notProfessional = userWithPermissions('students.view');

        $this->actingAs(userWithPermissions('triage.perform'))
            ->post(route('triage.requests.schedule', SupportRequest::factory()->create()), triageDecision($notProfessional->id))
            ->assertSessionHasErrors('assigned_to');
    });

    test('impede conflito de horário do profissional', function () {
        $professional = userWithPermissions('attendances.create');
        Attendance::factory()->create([
            'professional_id' => $professional->id,
            'scheduled_at' => now()->addDays(2)->setTime(9, 30),
            'planned_minutes' => 60,
        ]);

        $this->actingAs(userWithPermissions('triage.perform'))
            ->post(route('triage.requests.schedule', SupportRequest::factory()->create()), triageDecision($professional->id))
            ->assertSessionHasErrors('time');

        expect(Attendance::query()->count())->toBe(1);
    });

    test('devolver à coordenação exige motivo', function () {
        $request = SupportRequest::factory()->create();
        $user = userWithPermissions('triage.perform');

        $this->actingAs($user)->post(route('triage.requests.give-back', $request), [])
            ->assertSessionHasErrors('triage_notes');

        $this->actingAs($user)->post(route('triage.requests.give-back', $request), ['triage_notes' => 'Falta descrição'])
            ->assertRedirect(route('triage.index'));

        expect($request->fresh()?->status)->toBe(RequestStatus::Returned);
    });

    test('solicitação já triada não pode ser triada de novo', function () {
        $request = SupportRequest::factory()->create(['status' => RequestStatus::Returned]);

        $this->actingAs(userWithPermissions('triage.perform'))
            ->post(route('triage.requests.give-back', $request), ['triage_notes' => 'x'])
            ->assertStatus(409);
    });

    test('sem triage.perform não decide a triagem', function () {
        $this->actingAs(userWithPermissions('triage.view'))
            ->post(route('triage.requests.give-back', SupportRequest::factory()->create()), ['triage_notes' => 'x'])
            ->assertForbidden();
    });

    test('o parecer da triagem só aparece para quem o registrou (RN-012)', function () {
        $triager = userWithPermissions('triage.view', 'triage.perform');
        $request = SupportRequest::factory()->create();
        $this->actingAs($triager)->post(route('triage.requests.give-back', $request), ['triage_notes' => 'Parecer reservado']);

        $this->actingAs($triager)->get(route('triage.requests.show', $request))
            ->assertInertia(fn (Assert $page) => $page->where('request.triage_notes', 'Parecer reservado'));

        $this->actingAs(userWithPermissions('triage.view'))->get(route('triage.requests.show', $request))
            ->assertInertia(fn (Assert $page) => $page
                ->where('request.triage_notes', null)
                ->where('request.triage_notes_hidden', true)
            );
    });
});
