<?php

namespace App\Support\Reports;

use App\Enums\Attendance\AttendanceModality;
use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Attendance\AttendanceType;
use App\Enums\Referral\ReferralKind;
use App\Enums\Referral\ReferralStatus;
use App\Enums\Triage\RequestOrigin;
use App\Enums\Triage\RequestReason;
use App\Models\Attendance;
use App\Models\Referral;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Indicadores gerenciais do NAE (SRS §14, RF-046 a RF-050).
 *
 * Só contagens: nenhum nome de estudante e nenhum conteúdo sigiloso
 * (demanda, condução, motivo do encaminhamento, descrição da solicitação)
 * é lido aqui. As consultas selecionam apenas colunas de metadados.
 *
 * @phpstan-type AttendanceRow object{student_id: int, professional_id: int, type: string, status: string, modality: string, is_first_conversation: bool, scheduled_at: string, course: string}
 * @phpstan-type RequestRow object{origin: string, reasons: string|null}
 */
final class IndicatorReport
{
    private const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    public function __construct(private readonly ReportFilters $filters) {}

    /**
     * @return array{
     *     period: string,
     *     summary: list<array{key: string, label: string, value: int, hint: string}>,
     *     byType: list<array{key: string, label: string, value: int}>,
     *     monthly: array{labels: list<string>, series: list<array{key: string, label: string, values: list<int>}>},
     *     byProfessional: list<array{name: string, total: int, psychopedagogical: int, psychological: int, listening: int, cancelled: int}>,
     *     byCourse: list<array{course: string, students: int, attendances: int}>,
     *     byModality: list<array{label: string, value: int}>,
     *     referrals: array{internal: int, external: int, byStatus: list<array{label: string, value: int}>},
     *     requests: array{total: int, byReason: list<array{label: string, value: int}>, byOrigin: list<array{label: string, value: int}>}
     * }
     */
    public function build(): array
    {
        $attendances = $this->attendanceRows();
        $done = $attendances->filter(fn (object $row): bool => $this->isRegistered($row->status));
        $referrals = $this->referralRows();
        $requests = $this->requestRows();

        $closedFollowUps = $this->studentScope(Student::query())
            ->whereBetween('follow_up_closed_at', [$this->filters->from, $this->filters->to])
            ->count();

        $summary = [
            ['key' => 'students', 'label' => 'Estudantes atendidos', 'value' => $done->pluck('student_id')->unique()->count(), 'hint' => 'Estudantes com ao menos um atendimento registrado no período'],
            ['key' => 'attendances', 'label' => 'Atendimentos realizados', 'value' => $done->count(), 'hint' => 'Atendimentos com registro feito pelo profissional'],
            ['key' => 'new_follow_ups', 'label' => 'Novos acompanhamentos', 'value' => $done->where('is_first_conversation', true)->count(), 'hint' => 'Primeiras conversas realizadas no período'],
            ['key' => 'closed_follow_ups', 'label' => 'Acompanhamentos encerrados', 'value' => $closedFollowUps, 'hint' => 'Encerramentos registrados no período'],
            ['key' => 'scheduled', 'label' => 'Atendimentos agendados', 'value' => $attendances->where('status', AttendanceStatus::Scheduled->value)->count(), 'hint' => 'Marcados no período e ainda não registrados'],
            ['key' => 'cancelled', 'label' => 'Atendimentos cancelados', 'value' => $attendances->where('status', AttendanceStatus::Cancelled->value)->count(), 'hint' => 'Agendamentos cancelados no período'],
            ['key' => 'internal_referrals', 'label' => 'Encaminhamentos internos', 'value' => $referrals->where('kind', ReferralKind::Internal->value)->count(), 'hint' => 'Entre profissionais do NAE'],
            ['key' => 'external_referrals', 'label' => 'Encaminhamentos externos', 'value' => $referrals->where('kind', ReferralKind::External->value)->count(), 'hint' => 'Para serviços fora do NAE'],
            ['key' => 'requests', 'label' => 'Solicitações de apoio recebidas', 'value' => $requests->count(), 'hint' => 'Pedidos que chegaram ao NAE no período'],
        ];

        return [
            'period' => $this->filters->periodLabel(),
            'summary' => $summary,
            'byType' => array_map(fn (AttendanceType $type): array => [
                'key' => $type->value,
                'label' => $type->label(),
                'value' => $done->where('type', $type->value)->count(),
            ], AttendanceType::cases()),
            'monthly' => $this->monthly($done),
            'byProfessional' => $this->byProfessional($attendances),
            'byCourse' => $this->byCourse($done),
            'byModality' => array_map(fn (AttendanceModality $modality): array => [
                'label' => $modality->label(),
                'value' => $done->where('modality', $modality->value)->count(),
            ], AttendanceModality::cases()),
            'referrals' => [
                'internal' => $referrals->where('kind', ReferralKind::Internal->value)->count(),
                'external' => $referrals->where('kind', ReferralKind::External->value)->count(),
                'byStatus' => array_map(fn (ReferralStatus $status): array => [
                    'label' => $status->label(),
                    'value' => $referrals->where('status', $status->value)->count(),
                ], ReferralStatus::cases()),
            ],
            'requests' => [
                'total' => $requests->count(),
                'byReason' => $this->countReasons($requests),
                'byOrigin' => array_map(fn (RequestOrigin $origin): array => [
                    'label' => $origin->label(),
                    'value' => $requests->where('origin', $origin->value)->count(),
                ], RequestOrigin::cases()),
            ],
        ];
    }

    /**
     * Atendimentos do período (data marcada), só com metadados.
     *
     * @return Collection<int, AttendanceRow>
     */
    private function attendanceRows(): Collection
    {
        $query = Attendance::query()
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->whereBetween('attendances.scheduled_at', [$this->filters->from, $this->filters->to])
            ->when($this->filters->type, fn (Builder $query, AttendanceType $type) => $query->where('attendances.type', $type->value))
            ->when($this->filters->professionalId, fn (Builder $query, int $id) => $query->where('attendances.professional_id', $id))
            ->when($this->filters->course, fn (Builder $query, string $course) => $query->where('students.course', $course))
            ->when($this->filters->modality, fn (Builder $query, $modality) => $query->where('students.modality', $modality->value));

        /** @var Collection<int, AttendanceRow> $rows */
        $rows = $query->toBase()->get([
            'attendances.student_id', 'attendances.professional_id', 'attendances.type', 'attendances.status',
            'attendances.modality', 'attendances.is_first_conversation', 'attendances.scheduled_at', 'students.course',
        ])->map(function (object $row): object {
            $row->is_first_conversation = (bool) $row->is_first_conversation;

            return $row;
        });

        return $rows;
    }

    /**
     * @return Collection<int, object{kind: string, status: string}>
     */
    private function referralRows(): Collection
    {
        $query = $this->studentScope(Referral::query(), 'student')
            ->whereBetween('referred_on', [$this->filters->from->toDateString(), $this->filters->to->toDateString()])
            ->when($this->filters->professionalId, fn (Builder $query, int $id) => $query->where('created_by', $id));

        /** @var Collection<int, object{kind: string, status: string}> $rows */
        $rows = $query->toBase()->get(['kind', 'status']);

        return $rows;
    }

    /**
     * @return Collection<int, RequestRow>
     */
    private function requestRows(): Collection
    {
        $query = $this->studentScope(SupportRequest::query(), 'student')
            ->whereBetween('created_at', [$this->filters->from, $this->filters->to]);

        /** @var Collection<int, RequestRow> $rows */
        $rows = $query->toBase()->get(['origin', 'reasons']);

        return $rows;
    }

    /**
     * Aplica os filtros de curso e modalidade do estudante.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function studentScope(Builder $query, ?string $relation = null): Builder
    {
        if (! $this->filters->filtersStudents()) {
            return $query;
        }

        $apply = fn (Builder $query) => $query
            ->when($this->filters->course, fn (Builder $query, string $course) => $query->where('course', $course))
            ->when($this->filters->modality, fn (Builder $query, $modality) => $query->where('modality', $modality->value));

        return $relation === null ? $apply($query) : $query->whereHas($relation, $apply);
    }

    private function isRegistered(string $status): bool
    {
        return in_array($status, [AttendanceStatus::Attended->value, AttendanceStatus::Completed->value, AttendanceStatus::Referred->value], true);
    }

    /**
     * Evolução mensal por tipo de atendimento.
     *
     * @param  Collection<int, AttendanceRow>  $done
     * @return array{labels: list<string>, series: list<array{key: string, label: string, values: list<int>}>}
     */
    private function monthly(Collection $done): array
    {
        $months = [];
        $cursor = $this->filters->from->startOfMonth();
        $multiYear = $this->filters->from->year !== $this->filters->to->year;

        while ($cursor->lessThanOrEqualTo($this->filters->to) && count($months) < 36) {
            $months[$cursor->format('Y-m')] = self::MONTHS[$cursor->month - 1].($multiYear ? '/'.$cursor->format('y') : '');
            $cursor = $cursor->addMonth();
        }

        $series = array_map(function (AttendanceType $type) use ($done, $months): array {
            $counts = $done->where('type', $type->value)
                ->countBy(fn (object $row): string => CarbonImmutable::parse($row->scheduled_at)->format('Y-m'));

            return [
                'key' => $type->value,
                'label' => $type->label(),
                'values' => array_map(fn (string $month): int => (int) ($counts[$month] ?? 0), array_keys($months)),
            ];
        }, AttendanceType::cases());

        return ['labels' => array_values($months), 'series' => $series];
    }

    /**
     * @param  Collection<int, AttendanceRow>  $attendances
     * @return list<array{name: string, total: int, psychopedagogical: int, psychological: int, listening: int, cancelled: int}>
     */
    private function byProfessional(Collection $attendances): array
    {
        $names = User::query()->whereIn('id', $attendances->pluck('professional_id')->unique())->pluck('name', 'id');

        return array_values($attendances->groupBy('professional_id')
            ->map(function (Collection $rows, int|string $id) use ($names): array {
                $done = $rows->filter(fn (object $row): bool => $this->isRegistered($row->status));

                return [
                    'name' => (string) ($names[$id] ?? 'Usuário removido'),
                    'total' => $done->count(),
                    'psychopedagogical' => $done->where('type', AttendanceType::Psychopedagogical->value)->count(),
                    'psychological' => $done->where('type', AttendanceType::Psychological->value)->count(),
                    'listening' => $done->where('type', AttendanceType::Listening->value)->count(),
                    'cancelled' => $rows->where('status', AttendanceStatus::Cancelled->value)->count(),
                ];
            })
            ->sortByDesc('total')
            ->all());
    }

    /**
     * @param  Collection<int, AttendanceRow>  $done
     * @return list<array{course: string, students: int, attendances: int}>
     */
    private function byCourse(Collection $done): array
    {
        return array_values($done->groupBy('course')
            ->map(fn (Collection $rows, int|string $course): array => [
                'course' => (string) $course,
                'students' => $rows->pluck('student_id')->unique()->count(),
                'attendances' => $rows->count(),
            ])
            ->sortByDesc('attendances')
            ->all());
    }

    /**
     * Distribuição das demandas: motivos marcados nas solicitações (uma solicitação pode ter vários).
     *
     * @param  Collection<int, RequestRow>  $requests
     * @return list<array{label: string, value: int}>
     */
    private function countReasons(Collection $requests): array
    {
        $counts = $requests
            ->flatMap(fn (object $row): array => is_string($row->reasons) ? (array) json_decode($row->reasons, true) : [])
            ->countBy();

        return array_values(collect(RequestReason::cases())
            ->map(fn (RequestReason $reason): array => ['label' => $reason->label(), 'value' => (int) ($counts[$reason->value] ?? 0)])
            ->sortByDesc('value')
            ->all());
    }
}
