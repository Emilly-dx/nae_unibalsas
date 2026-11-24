<?php

namespace App\Support;

use App\Models\Attendance;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monta a agenda semanal (segunda a sábado) usada nas telas de Triagem e
 * de Agenda (SRS §13, RF-041, RF-045).
 */
class WeekAgenda
{
    /**
     * @return array<string, mixed>
     */
    public static function build(?string $week, ?int $professionalId = null): array
    {
        $reference = rescue(fn () => CarbonImmutable::createFromFormat('Y-m-d', (string) $week), null, false);
        $today = CarbonImmutable::today();
        $monday = ($reference instanceof CarbonImmutable ? $reference : $today)
            ->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $currentMonday = $today->startOfWeek(CarbonInterface::MONDAY);

        $events = Attendance::query()->notCancelled()->between($monday, $monday->addDays(7))
            ->when($professionalId !== null, fn (Builder $query) => $query->where('professional_id', $professionalId))
            ->with(['student', 'professional'])->orderBy('scheduled_at')->get();

        // Sábado só aparece quando há atendimento nele.
        $dayCount = $events->contains(fn (Attendance $attendance): bool => $attendance->scheduled_at->dayOfWeekIso === 6) ? 6 : 5;
        $names = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

        return [
            'label' => $monday->format('d/m').' – '.$monday->addDays($dayCount - 1)->format('d/m/Y'),
            'previous' => $monday->subWeek()->toDateString(),
            'next' => $monday->addWeek()->toDateString(),
            'current' => $currentMonday->toDateString(),
            'isCurrent' => $monday->isSameDay($currentMonday),
            'days' => collect(range(0, $dayCount - 1))->map(fn (int $offset): array => [
                'label' => $names[$offset].' '.$monday->addDays($offset)->format('d'),
                'isToday' => $monday->addDays($offset)->isToday(),
            ])->all(),
            'events' => $events
                ->filter(fn (Attendance $attendance): bool => $attendance->scheduled_at->dayOfWeekIso <= $dayCount)
                ->map(fn (Attendance $attendance): array => [
                    ...AttendancePresenter::summary($attendance),
                    'day' => $attendance->scheduled_at->dayOfWeekIso - 1,
                    'start' => $attendance->scheduled_at->hour + $attendance->scheduled_at->minute / 60,
                    'hours' => max($attendance->planned_minutes, 30) / 60,
                ])->values()->all(),
        ];
    }
}
