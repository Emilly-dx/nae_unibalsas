<?php

use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Attendance\AttendanceType;
use App\Models\Attendance;
use App\Models\Referral;
use App\Models\Student;
use App\Models\SupportRequest;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([ModuleSeeder::class, PermissionSeeder::class]);
});

const YEAR_2026 = ['de' => '2026-01-01', 'ate' => '2026-12-31'];

function doneAttendance(string $date, array $attributes = []): Attendance
{
    return Attendance::factory()->registered()->create([
        'scheduled_at' => $date.' 09:00:00',
        ...$attributes,
    ]);
}

/**
 * @return array<string, int>
 */
function summaryOf(iterable $summary): array
{
    return collect($summary)->pluck('value', 'key')->all();
}

describe('indicadores (RF-046, RF-047)', function () {
    test('conta atendimentos realizados, estudantes, agendados e cancelados do período', function () {
        $student = Student::factory()->create();
        doneAttendance('2026-03-10', ['student_id' => $student->id, 'type' => AttendanceType::Psychological, 'is_first_conversation' => true]);
        doneAttendance('2026-03-20', ['student_id' => $student->id, 'type' => AttendanceType::Psychological]);
        doneAttendance('2026-05-02', ['type' => AttendanceType::Listening, 'status' => AttendanceStatus::Completed]);
        Attendance::factory()->create(['scheduled_at' => '2026-06-01 10:00:00']);
        Attendance::factory()->create(['scheduled_at' => '2026-06-02 10:00:00', 'status' => AttendanceStatus::Cancelled]);
        doneAttendance('2025-11-10'); // fora do período

        $this->actingAs(userWithPermissions('reports.view'))
            ->get(route('reports.index', YEAR_2026))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/Index')
                ->where('report.period', '01/01/2026 a 31/12/2026')
                ->where('report.byType.1.value', 2)
                ->where('report.byType.2.value', 1)
                ->where('report.monthly.labels', ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'])
                ->where('report.monthly.series.1.values.2', 2)
                ->where('report.monthly.series.2.values.4', 1)
                ->where('can', ['exportPdf' => false, 'exportSpreadsheet' => false])
                ->where('report.summary', fn ($summary) => summaryOf($summary) === [
                    'students' => 2,
                    'attendances' => 3,
                    'new_follow_ups' => 1,
                    'closed_follow_ups' => 0,
                    'scheduled' => 1,
                    'cancelled' => 1,
                    'internal_referrals' => 0,
                    'external_referrals' => 0,
                    'requests' => 0,
                ])
            );
    });

    test('sem filtro de data usa o ano atual', function () {
        $this->actingAs(userWithPermissions('reports.view'))
            ->get(route('reports.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.de', now()->startOfYear()->toDateString())
                ->where('filters.ate', now()->endOfYear()->toDateString())
            );
    });

    test('encaminhamentos, demandas e encerramentos de acompanhamento', function () {
        $colleague = userWithPermissions('attendances.create');
        Referral::factory()->create(['referred_on' => '2026-04-01']);
        Referral::factory()->internal($colleague)->create(['referred_on' => '2026-04-02']);
        Referral::factory()->create(['referred_on' => '2025-04-02']);
        SupportRequest::factory()->create(['created_at' => '2026-02-01 10:00:00', 'reasons' => ['emotional', 'study_routine']]);
        SupportRequest::factory()->create(['created_at' => '2026-02-03 10:00:00', 'reasons' => ['emotional']]);
        Student::factory()->create(['follow_up_closed_at' => '2026-07-01 10:00:00']);

        $this->actingAs(userWithPermissions('reports.view'))
            ->get(route('reports.index', YEAR_2026))
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.referrals.internal', 1)
                ->where('report.referrals.external', 1)
                ->where('report.requests.total', 2)
                ->where('report.requests.byReason.0', ['label' => 'Questões emocionais', 'value' => 2])
                ->where('report.summary.3.value', 1)
            );
    });

    test('por profissional e por curso', function () {
        $paula = userWithPermissions('attendances.create');
        $paula->update(['name' => 'Paula']);
        $student = Student::factory()->create(['course' => 'Direito']);
        doneAttendance('2026-03-10', ['professional_id' => $paula->id, 'student_id' => $student->id, 'type' => AttendanceType::Psychopedagogical]);
        doneAttendance('2026-03-11', ['professional_id' => $paula->id, 'student_id' => $student->id, 'type' => AttendanceType::Listening]);
        Attendance::factory()->create(['professional_id' => $paula->id, 'scheduled_at' => '2026-03-12 09:00:00', 'status' => AttendanceStatus::Cancelled]);

        $this->actingAs(userWithPermissions('reports.view'))
            ->get(route('reports.index', YEAR_2026))
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.byProfessional.0', [
                    'name' => 'Paula', 'total' => 2, 'psychopedagogical' => 1, 'psychological' => 0, 'listening' => 1, 'cancelled' => 1,
                ])
                ->where('report.byCourse.0', ['course' => 'Direito', 'students' => 1, 'attendances' => 2])
            );
    });
});

describe('filtros (RF-048)', function () {
    test('filtra por tipo, profissional, curso e modalidade', function () {
        $me = userWithPermissions('attendances.create');
        $law = Student::factory()->create(['course' => 'Direito', 'modality' => 'presential']);
        $admin = Student::factory()->create(['course' => 'Administração', 'modality' => 'distance']);
        doneAttendance('2026-03-10', ['student_id' => $law->id, 'professional_id' => $me->id, 'type' => AttendanceType::Psychological]);
        doneAttendance('2026-03-11', ['student_id' => $law->id, 'type' => AttendanceType::Listening]);
        doneAttendance('2026-03-12', ['student_id' => $admin->id, 'type' => AttendanceType::Psychological]);
        $user = userWithPermissions('reports.view');

        $count = fn (array $filters) => $this->actingAs($user)->get(route('reports.index', [...YEAR_2026, ...$filters]));

        $count(['tipo' => 'psychological'])->assertInertia(fn (Assert $page) => $page->where('report.summary.1.value', 2));
        $count(['profissional' => $me->id])->assertInertia(fn (Assert $page) => $page->where('report.summary.1.value', 1));
        $count(['curso' => 'Direito'])->assertInertia(fn (Assert $page) => $page->where('report.summary.1.value', 2)->where('report.summary.0.value', 1));
        $count(['modalidade' => 'distance'])->assertInertia(fn (Assert $page) => $page->where('report.summary.1.value', 1));
        $count(['de' => '2026-03-11', 'ate' => '2026-03-11'])->assertInertia(fn (Assert $page) => $page->where('report.summary.1.value', 1));
    });

    test('valida as datas', function () {
        $this->actingAs(userWithPermissions('reports.view'))
            ->get(route('reports.index', ['de' => '2026-05-01', 'ate' => '2026-04-01']))
            ->assertSessionHasErrors(['ate' => 'A data final deve ser igual ou posterior à data inicial.']);
    });
});

describe('permissões e sigilo (RF-049, RF-050)', function () {
    test('sem reports.view não acessa relatórios nem exportações', function () {
        $user = userWithPermissions('students.view');

        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.pdf'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.spreadsheet'))->assertForbidden();
    });

    test('exportações exigem a permissão de cada formato', function () {
        $viewer = userWithPermissions('reports.view');
        $this->actingAs($viewer)->get(route('reports.pdf'))->assertForbidden();
        $this->actingAs($viewer)->get(route('reports.spreadsheet'))->assertForbidden();

        $exporter = userWithPermissions('reports.view', 'reports.export_pdf', 'reports.export_spreadsheet');
        $this->actingAs($exporter)->get(route('reports.index'))
            ->assertInertia(fn (Assert $page) => $page->where('can', ['exportPdf' => true, 'exportSpreadsheet' => true]));
        $this->actingAs($exporter)->get(route('reports.pdf', YEAR_2026))->assertOk()->assertSee('Relatório de indicadores');

        $response = $this->actingAs($exporter)->get(route('reports.spreadsheet', YEAR_2026));
        $response->assertOk()->assertDownload('relatorio-nae-2026-01-01-a-2026-12-31.csv');
        expect($response->streamedContent())->toContain('"Atendimentos realizados";0');
    });

    test('nenhum nome de estudante ou conteúdo sigiloso aparece nos relatórios', function () {
        $student = Student::factory()->create(['name' => 'Fulana Sigilosa', 'course' => 'Direito']);
        doneAttendance('2026-03-10', ['student_id' => $student->id, 'demand' => 'Conteúdo muito sigiloso']);
        Referral::factory()->create(['student_id' => $student->id, 'referred_on' => '2026-03-11', 'reason' => 'Motivo secreto']);
        $user = userWithPermissions('reports.view', 'reports.export_pdf', 'reports.export_spreadsheet');

        $page = $this->actingAs($user)->get(route('reports.index', YEAR_2026));
        $pdf = $this->actingAs($user)->get(route('reports.pdf', YEAR_2026));
        $csv = $this->actingAs($user)->get(route('reports.spreadsheet', YEAR_2026))->streamedContent();

        foreach (['Fulana Sigilosa', 'Conteúdo muito sigiloso', 'Motivo secreto'] as $secret) {
            $page->assertDontSee($secret, false);
            $pdf->assertDontSee($secret, false);
            expect($csv)->not->toContain($secret);
        }
    });

    test('exportações ficam registradas na auditoria', function () {
        $user = userWithPermissions('reports.view', 'reports.export_pdf');

        $this->actingAs($user)->get(route('reports.pdf', YEAR_2026));

        $log = Activity::query()->where('log_name', 'reports')->sole();
        expect($log->event)->toBe('exported')
            ->and($log->causer_id)->toBe($user->id)
            ->and($log->properties->get('format'))->toBe('pdf');
    });

    test('planilha protege contra fórmulas (CSV injection)', function () {
        $user = userWithPermissions('attendances.create');
        $user->update(['name' => '=HYPERLINK("x")']);
        doneAttendance('2026-03-10', ['professional_id' => $user->id]);

        $csv = $this->actingAs(userWithPermissions('reports.view', 'reports.export_spreadsheet'))
            ->get(route('reports.spreadsheet', YEAR_2026))->streamedContent();

        expect($csv)->toContain("'=HYPERLINK");
    });
});
