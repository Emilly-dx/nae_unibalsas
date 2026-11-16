<?php

namespace App\Http\Controllers\Attendances;

use App\Actions\Attendances\ScheduleAttendance;
use App\Enums\Attendance\AttendanceModality;
use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Attendance\AttendanceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendances\RegisterAttendanceRequest;
use App\Http\Requests\Attendances\ScheduleAttendanceRequest;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use App\Support\AttendancePresenter;
use App\Support\Professionals;
use App\Support\StudentOptions;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Agendamentos e registro dos atendimentos (RF-022 a RF-029, RN-007, RN-008).
 */
class AttendanceController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('attendances/Schedule', [
            ...$this->formOptions(),
            'professionals' => Professionals::options(),
            'selectedStudent' => $request->integer('estudante') ?: null,
        ]);
    }

    public function store(ScheduleAttendanceRequest $request, ScheduleAttendance $schedule): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $schedule->handle(
            Student::query()->findOrFail((int) $data['student_id']),
            (int) $data['professional_id'],
            $user,
            $request->slot(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Atendimento agendado.']);

        return to_route('triage.index', ['aba' => 'agenda', 'semana' => $data['date']]);
    }

    public function show(Request $request, Attendance $attendance): Response
    {
        /** @var User $user */
        $user = $request->user();
        $attendance->load(['student', 'professional', 'supportRequest']);
        $canViewContent = Gate::allows('viewContent', $attendance);

        return Inertia::render('attendances/Show', [
            'attendance' => [
                ...AttendancePresenter::summary($attendance),
                'started_at' => $attendance->started_at?->format('H:i'),
                'ended_at' => $attendance->ended_at?->format('H:i'),
                'registered_at' => $attendance->registered_at?->format('d/m/Y H:i'),
                'cancellation_reason' => $attendance->cancellation_reason,
                'support_request_id' => $attendance->support_request_id,
            ],
            // Conteúdo sigiloso: apenas para o profissional responsável (RN-012).
            'content' => $canViewContent && $attendance->registered_at !== null ? [
                'demand' => $attendance->demand,
                'conduct' => $attendance->conduct,
                'agreements' => $attendance->agreements,
                'next_return_on' => $attendance->next_return_on?->format('d/m/Y'),
            ] : null,
            'can' => [
                'register' => Gate::allows('register', $attendance),
                'cancel' => Gate::allows('cancel', $attendance),
                'finish' => Gate::allows('finish', $attendance),
                'viewContent' => $canViewContent,
                'viewStudent' => $user->can('students.view'),
                'viewRequest' => $user->can('triage.view'),
            ],
        ]);
    }

    /**
     * Formulário de registro: de um agendamento existente ou de um
     * atendimento sem agendamento prévio (ex.: escuta espontânea).
     */
    public function registerForm(Request $request, ?Attendance $attendance = null): Response
    {
        if ($attendance !== null) {
            Gate::authorize('register', $attendance);
            $attendance->load(['student', 'professional']);
        }

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('attendances/Register', [
            ...$this->formOptions(),
            'attendance' => $attendance === null ? null : [
                ...AttendancePresenter::summary($attendance),
                'date_value' => $attendance->scheduled_at->toDateString(),
                'start_time' => $attendance->started_at?->format('H:i') ?? $attendance->scheduled_at->format('H:i'),
                'end_time' => $attendance->ended_at?->format('H:i')
                    ?? $attendance->scheduled_at->addMinutes($attendance->planned_minutes)->format('H:i'),
                'modality' => $attendance->modality->value,
                'demand' => $attendance->demand,
                'conduct' => $attendance->conduct,
                'agreements' => $attendance->agreements,
                'next_return_on' => $attendance->next_return_on?->toDateString(),
            ],
            'studentSummaries' => $this->studentSummaries($attendance?->student_id),
            'selectedStudent' => $attendance->student_id ?? ($request->integer('estudante') ?: null),
            'professional' => $user->name,
        ]);
    }

    public function storeRegistration(RegisterAttendanceRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $attendance = new Attendance([
            'scheduled_at' => CarbonImmutable::parse($data['date'].' '.($data['start_time'] ?? '00:00')),
            'planned_minutes' => 50,
        ]);
        $attendance->student()->associate(Student::query()->findOrFail((int) $data['student_id']));
        $attendance->professional_id = $user->id;
        $attendance->scheduled_by = $user->id;
        $this->fillRecord($attendance, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Atendimento registrado.']);

        return to_route('attendances.show', $attendance);
    }

    public function updateRegistration(RegisterAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        Gate::authorize('register', $attendance);

        $this->fillRecord($attendance, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Atendimento registrado.']);

        return to_route('attendances.show', $attendance);
    }

    public function cancel(Request $request, Attendance $attendance): RedirectResponse
    {
        Gate::authorize('cancel', $attendance);

        $validated = $request->validate(
            ['cancellation_reason' => ['nullable', 'string', 'max:255']],
            [],
            ['cancellation_reason' => 'motivo do cancelamento'],
        );

        $attendance->status = AttendanceStatus::Cancelled;
        $attendance->cancelled_at = now()->toImmutable();
        $attendance->cancellation_reason = $validated['cancellation_reason'] ?? null;
        $attendance->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agendamento cancelado.']);

        return to_route('attendances.show', $attendance);
    }

    public function finish(Attendance $attendance): RedirectResponse
    {
        Gate::authorize('finish', $attendance);

        $attendance->status = AttendanceStatus::Completed;
        $attendance->completed_at = now()->toImmutable();
        $attendance->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Atendimento concluído.']);

        return to_route('attendances.show', $attendance);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fillRecord(Attendance $attendance, array $data): void
    {
        $date = (string) $data['date'];
        $start = isset($data['start_time']) ? CarbonImmutable::parse("{$date} {$data['start_time']}") : null;

        $attendance->type = AttendanceType::from((string) $data['type']);
        $attendance->modality = AttendanceModality::from((string) $data['modality']);
        $attendance->started_at = $start;
        $attendance->ended_at = isset($data['end_time']) ? CarbonImmutable::parse("{$date} {$data['end_time']}") : null;
        $attendance->demand = (string) $data['demand'];
        $attendance->conduct = $data['conduct'] ?? null;
        $attendance->agreements = $data['agreements'] ?? null;
        $attendance->next_return_on = isset($data['next_return_on']) ? CarbonImmutable::parse((string) $data['next_return_on']) : null;
        $attendance->status = AttendanceStatus::Attended;
        $attendance->registered_at ??= now()->toImmutable();

        if ($start !== null && $attendance->scheduled_at->toDateString() !== $date) {
            $attendance->scheduled_at = $start;
        }

        $attendance->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'students' => StudentOptions::all(),
            'types' => AttendanceType::options(),
            'modalities' => AttendanceModality::options(),
        ];
    }

    /**
     * Resumo de acompanhamento por estudante (painel lateral do registro).
     *
     * @return array<int, array{count: int, last: string|null}>
     */
    private function studentSummaries(?int $onlyStudent): array
    {
        $semesterStart = now()->month >= 7 ? now()->setDate(now()->year, 7, 1) : now()->setDate(now()->year, 1, 1);

        return Attendance::query()->registered()
            ->when($onlyStudent !== null, fn ($query) => $query->where('student_id', $onlyStudent))
            ->where('scheduled_at', '>=', $semesterStart->startOfDay())
            ->get(['student_id', 'scheduled_at'])
            ->groupBy('student_id')
            ->map(fn ($items): array => [
                'count' => $items->count(),
                'last' => $items->max('scheduled_at')?->format('d/m/Y'),
            ])
            ->all();
    }
}
