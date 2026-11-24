<?php

namespace App\Http\Controllers;

use App\Enums\Triage\RequestStatus;
use App\Models\Attendance;
use App\Models\Referral;
use App\Models\SupportRequest;
use App\Models\User;
use App\Support\AttendancePresenter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Painel inicial: indicadores do dia e agenda de hoje (dados reais do M4).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $canAttendances = $user->can('attendances.view');
        $today = CarbonImmutable::today();
        $semesterStart = $today->month >= 7 ? $today->setDate($today->year, 7, 1) : $today->setDate($today->year, 1, 1);

        $todayAgenda = $canAttendances
            ? Attendance::query()->notCancelled()->between($today, $today->addDay())
                ->with(['student', 'professional'])->orderBy('scheduled_at')->get()
            : collect();

        return Inertia::render('Dashboard', [
            'stats' => [
                'today' => $canAttendances ? $todayAgenda->count() : null,
                'queue' => $user->can('triage.view')
                    ? SupportRequest::query()->where('status', RequestStatus::PendingTriage)->count()
                    : null,
                'referrals' => $user->can('referrals.view')
                    ? Referral::query()->open()->count()
                    : null,
                'following' => $canAttendances
                    ? Attendance::query()->registered()->where('scheduled_at', '>=', $semesterStart)
                        ->distinct()->count('student_id')
                    : null,
            ],
            'todayAgenda' => $todayAgenda->map(fn (Attendance $attendance): array => [
                ...AttendancePresenter::summary($attendance),
                'canRegister' => $user->can('register', $attendance),
            ]),
        ]);
    }
}
