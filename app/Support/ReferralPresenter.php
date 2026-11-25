<?php

namespace App\Support;

use App\Models\Referral;

/**
 * Dados de um encaminhamento enviados ao frontend. Motivo e retorno
 * (sigilosos) nunca vão no resumo; são expostos à parte, só para os
 * envolvidos (RN-012).
 */
class ReferralPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function summary(Referral $referral): array
    {
        $student = $referral->student;

        return [
            'id' => $referral->id,
            'student' => [
                'id' => $student->id,
                'name' => $student->social_name ?: $student->name,
                'course' => $student->course,
                'period' => $student->period,
            ],
            'kind' => $referral->kind->value,
            'kind_label' => $referral->kind->label(),
            'origin' => $referral->author->name,
            'destination' => $referral->destinationLabel(),
            'status' => $referral->status->value,
            'status_label' => $referral->status->label(),
            'referred_on' => $referral->referred_on->format('d/m/Y'),
            'referred_on_iso' => $referral->referred_on->toDateString(),
            'return_deadline' => $referral->return_deadline?->format('d/m/Y'),
            'overdue' => $referral->isOverdue(),
            'attendance_id' => $referral->attendance_id,
        ];
    }
}
