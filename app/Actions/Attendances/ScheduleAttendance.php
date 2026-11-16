<?php

namespace App\Actions\Attendances;

use App\Enums\Attendance\AttendanceModality;
use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Attendance\AttendanceType;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Cria um agendamento (RN-007), impedindo conflito de horário do profissional.
 */
class ScheduleAttendance
{
    /**
     * @param  array{date: string, time: string, planned_minutes: int, type: string, modality: string}  $data
     */
    public function handle(
        Student $student,
        int $professionalId,
        User $scheduledBy,
        array $data,
        ?SupportRequest $request = null,
        bool $firstConversation = false,
    ): Attendance {
        $start = CarbonImmutable::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['time']}");

        if (! $start instanceof CarbonImmutable) {
            throw ValidationException::withMessages(['time' => 'Data ou horário inválidos.']);
        }

        if ($start->isPast()) {
            throw ValidationException::withMessages(['time' => 'O horário do agendamento já passou.']);
        }

        $minutes = $data['planned_minutes'];
        $this->ensureProfessionalIsFree($professionalId, $start, $minutes);

        $attendance = new Attendance([
            'type' => AttendanceType::from($data['type']),
            'modality' => AttendanceModality::from($data['modality']),
            'scheduled_at' => $start,
            'planned_minutes' => $minutes,
            'is_first_conversation' => $firstConversation,
        ]);
        $attendance->student()->associate($student);
        $attendance->supportRequest()->associate($request);
        $attendance->professional_id = $professionalId;
        $attendance->scheduled_by = $scheduledBy->id;
        $attendance->status = AttendanceStatus::Scheduled;
        $attendance->save();

        return $attendance;
    }

    private function ensureProfessionalIsFree(int $professionalId, CarbonImmutable $start, int $minutes): void
    {
        $end = $start->addMinutes($minutes);

        $conflict = Attendance::query()
            ->where('professional_id', $professionalId)
            ->where('status', AttendanceStatus::Scheduled)
            ->where('scheduled_at', '<', $end)
            ->where('scheduled_at', '>', $start->subHours(4))
            ->get()
            ->first(fn (Attendance $other): bool => $other->scheduled_at->addMinutes($other->planned_minutes)->greaterThan($start));

        if ($conflict !== null) {
            throw ValidationException::withMessages([
                'time' => sprintf(
                    'O profissional já tem um atendimento agendado às %s nesse dia.',
                    $conflict->scheduled_at->format('H:i'),
                ),
            ]);
        }
    }
}
