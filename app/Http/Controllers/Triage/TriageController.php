<?php

namespace App\Http\Controllers\Triage;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SupportRequest;
use App\Models\User;
use App\Support\AttendancePresenter;
use App\Support\WeekAgenda;
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
            'week' => $canAttendances ? WeekAgenda::build($request->string('semana')->toString()) : null,
            'registered' => $canAttendances
                ? Attendance::query()->registered()->with(['student', 'professional'])
                    ->latest('scheduled_at')->paginate(15, ['*'], 'pagina')->withQueryString()
                    ->through(fn (Attendance $attendance): array => AttendancePresenter::summary($attendance))
                : null,
            'can' => [
                'triage' => $canTriage,
                'perform' => $user->can('triage.perform'),
                'createRequest' => $user->can('triage.create'),
                'schedule' => $user->can('attendances.schedule'),
                'register' => $user->can('attendances.create'),
                'attendances' => $canAttendances,
            ],
        ]);
    }
}
