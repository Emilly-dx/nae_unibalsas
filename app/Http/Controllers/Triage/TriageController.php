<?php

namespace App\Http\Controllers\Triage;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SupportRequest;
use App\Models\User;
use App\Support\AttendancePresenter;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tela "Triagem e atendimentos": fila de triagem, agenda da semana e
 * atendimentos registrados (SRS §10, RF-019 a RF-029).
 */
class TriageController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $canTriage = $user->can('triage.view');
        $canAttendances = $user->can('attendances.view');

        abort_unless($canTriage || $canAttendances, 403);

        $tab = $request->string('aba')->toString();
        $tab = in_array($tab, ['triagem', 'agenda', 'atendimentos'], true) ? $tab : ($canTriage ? 'triagem' : 'agenda');

        return Inertia::render('triage/Index', [
            'tab' => $tab,
            'queue' => $canTriage
                ? SupportRequest::query()->queue()->with('student')->get()
                    ->map(fn (SupportRequest $item): array => [
                        'id' => $item->id,
                        'student' => $item->student->social_name ?: $item->student->name,
                        'reasons' => $item->reasonLabels(),
                        'origin' => $item->origin->label(),
                        'priority' => $item->priority->value,
                        'date' => $item->created_at?->format('d/m'),
                    ])
                : [],
            'week' => $canAttendances ? $this->week($request) : null,
            'registered' => $canAttendances
                ? Attendance::query()->registered()->with(['student', 'professional'])
                    ->latest('scheduled_at')->paginate(15, ['*'], 'pagina')->withQueryString()
                    ->through(fn (Attendance $attendance): array => AttendancePresenter::summary($attendance))
                : null,
            'can' => [
                'triage' => $canTriage,
                'createRequest' => $user->can('triage.create'),
                'schedule' => $user->can('attendances.schedule'),
                'register' => $user->can('attendances.create'),
                'attendances' => $canAttendances,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function week(Request $request): array
    {
        $reference = rescue(
            fn () => CarbonImmutable::createFromFormat('Y-m-d', $request->string('semana')->toString()),
            null,
            false,
        );
        $monday = ($reference instanceof CarbonImmutable ? $reference : CarbonImmutable::today())->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $saturday = $monday->addDays(5);

        $events = Attendance::query()->notCancelled()->between($monday, $saturday)
            ->with(['student', 'professional'])->orderBy('scheduled_at')->get();

        return [
            'label' => $monday->format('d/m').' – '.$saturday->subDay()->format('d/m/Y'),
            'previous' => $monday->subWeek()->toDateString(),
            'next' => $monday->addWeek()->toDateString(),
            'current' => CarbonImmutable::today()->startOfWeek(CarbonInterface::MONDAY)->toDateString(),
            'isCurrent' => $monday->isSameDay(CarbonImmutable::today()->startOfWeek(CarbonInterface::MONDAY)),
            'days' => collect(range(0, 4))->map(function (int $offset) use ($monday): array {
                $day = $monday->addDays($offset);

                return [
                    'label' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'][$offset].' '.$day->format('d'),
                    'isToday' => $day->isToday(),
                ];
            })->all(),
            'events' => $events->map(fn (Attendance $attendance): array => [
                ...AttendancePresenter::summary($attendance),
                'day' => $attendance->scheduled_at->dayOfWeekIso - 1,
                'start' => $attendance->scheduled_at->hour + $attendance->scheduled_at->minute / 60,
                'hours' => max($attendance->planned_minutes, 30) / 60,
            ])->values()->all(),
        ];
    }
}
