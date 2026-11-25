<?php

use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Referral\ReferralKind;
use App\Enums\Referral\ReferralStatus;
use App\Models\Attendance;
use App\Models\Referral;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([ModuleSeeder::class, PermissionSeeder::class]);
});

function referringProfessional(): User
{
    return userWithPermissions(
        'students.view', 'attendances.view', 'attendances.create',
        'referrals.view', 'referrals.create', 'referrals.edit',
    );
}

function referralData(array $overrides = []): array
{
    return [
        'kind' => 'external',
        'destination_name' => 'Clínica UNIBALSAS',
        'reason' => 'Avaliação neuropsicológica.',
        'referred_on' => now()->toDateString(),
        'return_deadline' => now()->addDays(15)->toDateString(),
        ...$overrides,
    ];
}

describe('criação (RF-034 a RF-038)', function () {
    test('registra um encaminhamento externo', function () {
        $user = referringProfessional();
        $student = Student::factory()->create();

        $this->actingAs($user)
            ->post(route('referrals.store'), ['student_id' => $student->id, ...referralData()])
            ->assertSessionHasNoErrors();

        $referral = Referral::query()->sole();

        expect($referral->kind)->toBe(ReferralKind::External)
            ->and($referral->status)->toBe(ReferralStatus::Sent)
            ->and($referral->created_by)->toBe($user->id)
            ->and($referral->destinationLabel())->toBe('Clínica UNIBALSAS');
    });

    test('o interno exige outro profissional do NAE como destino (RN-003)', function () {
        $user = referringProfessional();
        $psychologist = referringProfessional();
        $notProfessional = userWithPermissions('students.view');
        $student = Student::factory()->create();

        $this->actingAs($user)
            ->post(route('referrals.store'), ['student_id' => $student->id, ...referralData(['kind' => 'internal', 'destination_name' => null, 'destination_user_id' => $notProfessional->id])])
            ->assertSessionHasErrors('destination_user_id');

        $this->actingAs($user)
            ->post(route('referrals.store'), ['student_id' => $student->id, ...referralData(['kind' => 'internal', 'destination_name' => null, 'destination_user_id' => $user->id])])
            ->assertSessionHasErrors('destination_user_id');

        $this->actingAs($user)
            ->post(route('referrals.store'), ['student_id' => $student->id, ...referralData(['kind' => 'internal', 'destination_name' => null, 'destination_user_id' => $psychologist->id])])
            ->assertSessionHasNoErrors();

        expect(Referral::query()->sole()->destination_user_id)->toBe($psychologist->id);
    });

    test('o externo exige o serviço de destino', function () {
        $this->actingAs(referringProfessional())
            ->post(route('referrals.store'), ['student_id' => Student::factory()->create()->id, ...referralData(['destination_name' => ''])])
            ->assertSessionHasErrors('destination_name');
    });

    test('valida motivo, datas e estudante inativo', function () {
        $this->actingAs(referringProfessional())
            ->post(route('referrals.store'), [
                'student_id' => Student::factory()->inactive()->create()->id,
                ...referralData([
                    'reason' => '',
                    'referred_on' => now()->addDay()->toDateString(),
                ]),
            ])
            ->assertSessionHasErrors(['student_id', 'reason', 'referred_on']);
    });

    test('o motivo é criptografado no banco', function () {
        $this->actingAs(referringProfessional())
            ->post(route('referrals.store'), ['student_id' => Student::factory()->create()->id, ...referralData()]);

        expect(DB::table('referrals')->value('reason'))->not->toContain('neuropsicol');
    });

    test('encaminhar a partir de um atendimento muda o status dele para Encaminhado', function () {
        $user = referringProfessional();
        $attendance = Attendance::factory()->registered()->create(['professional_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('referrals.store'), ['student_id' => $attendance->student_id, 'attendance_id' => $attendance->id, ...referralData()])
            ->assertSessionHasNoErrors();

        expect($attendance->fresh()?->status)->toBe(AttendanceStatus::Referred)
            ->and(Referral::query()->sole()->attendance_id)->toBe($attendance->id);
    });

    test('não aceita atendimento de outro estudante como origem', function () {
        $attendance = Attendance::factory()->registered()->create();

        $this->actingAs(referringProfessional())
            ->post(route('referrals.store'), ['student_id' => Student::factory()->create()->id, 'attendance_id' => $attendance->id, ...referralData()])
            ->assertSessionHasErrors('attendance_id');
    });

    test('sem referrals.create não encaminha', function () {
        $this->actingAs(userWithPermissions('referrals.view'))
            ->post(route('referrals.store'), ['student_id' => Student::factory()->create()->id, ...referralData()])
            ->assertForbidden();
    });

    test('registrar o atendimento com "gerar encaminhamento" leva ao formulário', function () {
        $user = referringProfessional();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->post(route('attendances.register.store'), [
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'type' => 'psychopedagogical',
            'modality' => 'presential',
            'demand' => 'Demanda',
            'refer_after' => '1',
        ]);

        $response->assertRedirect(route('referrals.create', ['atendimento' => Attendance::query()->sole()->id]));
    });
});

describe('situação e retorno (RF-039, RF-040)', function () {
    test('quem encaminhou atualiza a situação; concluir exige o retorno', function () {
        $user = referringProfessional();
        $referral = Referral::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->patch(route('referrals.status', $referral), ['status' => 'awaiting_return'])
            ->assertSessionHasNoErrors();
        expect($referral->fresh()?->status)->toBe(ReferralStatus::AwaitingReturn);

        $this->actingAs($user)
            ->patch(route('referrals.status', $referral), ['status' => 'completed'])
            ->assertSessionHasErrors('return_notes');

        $this->actingAs($user)
            ->patch(route('referrals.status', $referral), ['status' => 'completed', 'return_notes' => 'Avaliação realizada.'])
            ->assertSessionHasNoErrors();

        $referral->refresh();

        expect($referral->status)->toBe(ReferralStatus::Completed)
            ->and($referral->return_notes)->toBe('Avaliação realizada.')
            ->and($referral->returned_at)->not->toBeNull();
    });

    test('o profissional de destino do interno também registra o retorno', function () {
        $destination = referringProfessional();
        $referral = Referral::factory()->internal($destination)->create();

        $this->actingAs($destination)
            ->patch(route('referrals.status', $referral), ['status' => 'in_progress'])
            ->assertSessionHasNoErrors();

        expect($referral->fresh()?->status)->toBe(ReferralStatus::InProgress);
    });

    test('quem não está envolvido não altera nem cancela', function () {
        $referral = Referral::factory()->create();
        $other = referringProfessional();

        $this->actingAs($other)->patch(route('referrals.status', $referral), ['status' => 'in_progress'])->assertForbidden();
        $this->actingAs($other)->patch(route('referrals.cancel', $referral))->assertForbidden();
    });

    test('quem encaminhou cancela e pode reabrir', function () {
        $user = referringProfessional();
        $referral = Referral::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->patch(route('referrals.cancel', $referral))->assertRedirect();
        expect($referral->fresh()?->status)->toBe(ReferralStatus::Cancelled);

        $this->actingAs($user)->patch(route('referrals.status', $referral), ['status' => 'in_progress'])->assertForbidden();

        $this->actingAs($user)->patch(route('referrals.reopen', $referral))->assertRedirect();
        expect($referral->fresh()?->status)->toBe(ReferralStatus::Sent);
    });
});

describe('sigilo e listagem', function () {
    test('motivo e retorno só para os envolvidos, nem para o administrador (RN-012)', function () {
        $author = referringProfessional();
        $destination = referringProfessional();
        $referral = Referral::factory()->internal($destination)->create(['created_by' => $author->id, 'reason' => 'Motivo reservado']);

        foreach ([$author, $destination] as $user) {
            $this->actingAs($user)->get(route('referrals.show', $referral))
                ->assertInertia(fn (Assert $page) => $page->where('content.reason', 'Motivo reservado'));
        }

        $this->actingAs(referringProfessional())->get(route('referrals.show', $referral))
            ->assertInertia(fn (Assert $page) => $page->where('content', null));

        $this->actingAs(administrator())->get(route('referrals.show', $referral))
            ->assertInertia(fn (Assert $page) => $page->where('content', null)->where('can.update', false));
    });

    test('a listagem filtra por tipo, situação e "meus", sem expor o motivo', function () {
        $user = referringProfessional();
        Referral::factory()->create(['created_by' => $user->id]);
        Referral::factory()->internal($user)->create();
        Referral::factory()->create(['status' => ReferralStatus::Completed]);

        $this->actingAs($user)->get(route('referrals.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('referrals/Index')
                ->has('referrals.data', 3)
                ->missing('referrals.data.0.reason')
                ->where('counts.open', 2)
                ->where('counts.awaitingMe', 1)
            );

        $this->actingAs($user)->get(route('referrals.index', ['tipo' => 'internal']))
            ->assertInertia(fn (Assert $page) => $page->has('referrals.data', 1));

        $this->actingAs($user)->get(route('referrals.index', ['situacao' => 'abertos', 'meus' => 1]))
            ->assertInertia(fn (Assert $page) => $page->has('referrals.data', 2));
    });

    test('encaminhamento com prazo vencido aparece como atrasado', function () {
        $referral = Referral::factory()->create(['return_deadline' => now()->subDays(2)->toDateString()]);

        expect($referral->isOverdue())->toBeTrue();
    });
});
