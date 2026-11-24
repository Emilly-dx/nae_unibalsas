<?php

namespace App\Http\Controllers\Agenda;

use App\Enums\Attendance\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Support\AttendancePresenter;
use App\Support\Professionals;
use App\Support\WeekAgenda;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Agenda do NAE (SRS §13, RF-041 a RF-045): semana, próximos atendimentos
 * e horários de cada profissional.
 */
class AgendaController extends Controller
{
    private const int UPCOMING_DAYS = 14;

    private const array WEEKDAYS = [
        'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado', 'Domingo',
    ];

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $professionals = Professionals::options();
        $isProfessional = $professionals->contains(fn (array $option): bool => $option['value'] === $user->id);

        // Filtro por profissional: "meu" (padrão para profissionais), um id, ou todos.
        $filter = $request->has('profissional')
            ? $request->string('profissional')->toString()
            : ($isProfessional ? 'meu' : '');
        $professionalId = match (true) {
            $filter === 'meu' => $user->id,
            ctype_digit($filter) && $professionals->contains(fn (array $option): bool => $option['value'] === (int) $filter) => (int) $filter,
            default => null,
        };
        $filter = $professionalId === null ? '' : ($professionalId === $user->id && $isProfessional ? 'meu' : (string) $professionalId);

        $now = CarbonImmutable::now();

        $upcoming = Attendance::query()
            ->where('status', AttendanceStatus::Scheduled)
            ->whereBetween('scheduled_at', [$now->startOfDay(), $now->addDays(self::UPCOMING_DAYS)->endOfDay()])
            ->when($professionalId !== null, fn (Builder $query) => $query->where('professional_id', $professionalId))
            ->with(['student', 'professional'])
            ->orderBy('scheduled_at')
            ->get();

        return Inertia::render('agenda/Index', [
            'week' => WeekAgenda::build($request->string('semana')->toString(), $professionalId),
            'upcoming' => $upcoming->map(fn (Attendance $attendance): array => [
                ...AttendancePresenter::summary($attendance),
                'weekday' => self::WEEKDAYS[$attendance->scheduled_at->dayOfWeekIso - 1],
                'canReschedule' => $user->can('reschedule', $attendance),
            ])->values(),
            'upcomingDays' => self::UPCOMING_DAYS,
            'filters' => ['profissional' => $filter, 'semana' => $request->string('semana')->toString()],
            'professionals' => $professionals,
            'isProfessional' => $isProfessional,
            'canSchedule' => $user->can('attendances.schedule'),
        ]);
    }
}
