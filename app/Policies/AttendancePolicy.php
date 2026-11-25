<?php

namespace App\Policies;

use App\Enums\Attendance\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;

/**
 * Autorização por registro dos atendimentos. Attendance é um SensitiveRecord,
 * portanto o administrador não tem bypass aqui (RN-012, SRS §28.13).
 */
class AttendancePolicy
{
    /**
     * Ver o conteúdo sigiloso do registro: somente o profissional responsável.
     */
    public function viewContent(User $user, Attendance $attendance): bool
    {
        return $attendance->professional_id === $user->id;
    }

    /**
     * Registrar (ou corrigir) o atendimento: o profissional responsável,
     * enquanto o atendimento não foi cancelado nem concluído.
     */
    public function register(User $user, Attendance $attendance): bool
    {
        return $attendance->professional_id === $user->id
            && $user->can('attendances.create')
            // Encaminhado continua editável: corrigir o registro depois de gerar o encaminhamento.
            && in_array($attendance->status, [AttendanceStatus::Scheduled, AttendanceStatus::Attended, AttendanceStatus::Referred], true);
    }

    /**
     * Cancelar um agendamento: o profissional responsável ou quem agenda.
     */
    public function cancel(User $user, Attendance $attendance): bool
    {
        return $attendance->status === AttendanceStatus::Scheduled
            && ($attendance->professional_id === $user->id || $user->can('attendances.schedule'));
    }

    /**
     * Remarcar um agendamento (RF-043): o profissional responsável ou quem agenda.
     */
    public function reschedule(User $user, Attendance $attendance): bool
    {
        return $this->cancel($user, $attendance);
    }

    /**
     * Concluir um atendimento registrado (RF-029).
     */
    public function finish(User $user, Attendance $attendance): bool
    {
        return $attendance->professional_id === $user->id
            && $user->can('attendances.finish')
            && $attendance->status === AttendanceStatus::Attended;
    }
}
