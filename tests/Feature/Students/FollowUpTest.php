<?php

use App\Enums\FollowUp\FollowUpStatus;
use App\Enums\Triage\RequestStatus;
use App\Models\Attendance;
use App\Models\Referral;
use App\Models\Student;
use App\Models\SupportRequest;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([ModuleSeeder::class, PermissionSeeder::class]);
});

describe('situação do acompanhamento (RF-033, RN-009)', function () {
    test('sem nada registrado, não há acompanhamento', function () {
        expect(Student::factory()->create()->followUpStatus())->toBe(FollowUpStatus::None);
    });

    test('solicitação na fila ou agendamento futuro: aguardando 1º atendimento', function () {
        $student = Student::factory()->create();
        SupportRequest::factory()->for($student)->create(['status' => RequestStatus::PendingTriage]);

        expect($student->followUpStatus())->toBe(FollowUpStatus::Waiting);
    });

    test('com atendimento registrado, está em acompanhamento', function () {
        $attendance = Attendance::factory()->registered()->create();

        expect($attendance->student->followUpStatus())->toBe(FollowUpStatus::Active);
    });
});

describe('encerramento (SRS §11.2)', function () {
    test('o profissional encerra com motivo; um novo atendimento reabre', function () {
        $professional = userWithPermissions('students.view', 'attendances.view', 'attendances.create', 'attendances.finish');
        $attendance = Attendance::factory()->registered()->create(['professional_id' => $professional->id]);
        $student = $attendance->student;

        $this->actingAs($professional)
            ->post(route('students.follow-up.close', $student), [])
            ->assertSessionHasErrors('reason');

        $this->actingAs($professional)
            ->post(route('students.follow-up.close', $student), ['reason' => 'goals_met'])
            ->assertRedirect(route('students.show', $student));

        $student->refresh();
        expect($student->followUpStatus())->toBe(FollowUpStatus::Closed)
            ->and($student->follow_up_closed_by)->toBe($professional->id);

        $this->travel(1)->minutes();

        $this->actingAs($professional)->post(route('attendances.register.store'), [
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'type' => 'listening',
            'modality' => 'presential',
            'demand' => 'Retorno do estudante.',
        ])->assertSessionHasNoErrors();

        expect($student->fresh()?->followUpStatus())->toBe(FollowUpStatus::Active);
    });

    test('não encerra quem não está em acompanhamento nem quem não tem permissão', function () {
        $student = Student::factory()->create();

        $this->actingAs(userWithPermissions('attendances.finish'))
            ->post(route('students.follow-up.close', $student), ['reason' => 'goals_met'])
            ->assertStatus(409);

        $active = Attendance::factory()->registered()->create()->student;

        $this->actingAs(userWithPermissions('attendances.view'))
            ->post(route('students.follow-up.close', $active), ['reason' => 'goals_met'])
            ->assertForbidden();
    });
});

describe('linha do tempo (RF-030 a RF-032)', function () {
    test('mostra atendimentos, encaminhamentos e o resumo do acompanhamento', function () {
        $professional = userWithPermissions('students.view', 'attendances.view', 'referrals.view', 'attendances.finish');
        $attendance = Attendance::factory()->registered()->create(['professional_id' => $professional->id, 'demand' => 'Minha demanda']);
        $other = Attendance::factory()->registered()->create(['student_id' => $attendance->student_id, 'demand' => 'Demanda de colega']);
        Referral::factory()->for($attendance->student)->create();

        $this->actingAs($professional)->get(route('students.show', $attendance->student_id))
            ->assertInertia(fn (Assert $page) => $page
                ->where('followUp.status', 'active')
                ->where('followUp.attendances', 2)
                ->where('followUp.open_referrals', 1)
                ->where('followUp.canClose', true)
                ->has('history.referrals', 1)
                ->missing('history.referrals.0.reason')
                ->has('history.attendances', 2)
            );

        // Evolução do caso: o resumo só aparece nos registros do próprio profissional.
        $page = $this->actingAs($professional)->get(route('students.show', $attendance->student_id))->viewData('page');
        $excerpts = collect($page['props']['history']['attendances'])->pluck('excerpt', 'id');

        expect($excerpts[$attendance->id])->toBe('Minha demanda')
            ->and($excerpts[$other->id])->toBeNull();
    });

    test('sem attendances.view não vê o resumo do acompanhamento', function () {
        $student = Student::factory()->create();

        $this->actingAs(userWithPermissions('students.view'))->get(route('students.show', $student))
            ->assertInertia(fn (Assert $page) => $page->where('followUp', null)->where('history.referrals', null));
    });
});
