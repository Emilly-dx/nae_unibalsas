<?php

namespace App\Policies;

use App\Enums\Referral\ReferralStatus;
use App\Models\Referral;
use App\Models\User;

/**
 * Autorização por registro dos encaminhamentos. Referral é um
 * SensitiveRecord: o administrador não tem bypass aqui (RN-012).
 */
class ReferralPolicy
{
    /**
     * Ver motivo e retorno: quem encaminhou ou o profissional de destino.
     */
    public function viewContent(User $user, Referral $referral): bool
    {
        return $referral->involves($user);
    }

    /**
     * Atualizar situação e registrar o retorno (RF-039, RF-040).
     */
    public function update(User $user, Referral $referral): bool
    {
        return $user->can('referrals.edit')
            && $referral->involves($user)
            && $referral->status->isOpen();
    }

    /**
     * Cancelar: somente quem encaminhou, enquanto estiver em aberto.
     */
    public function cancel(User $user, Referral $referral): bool
    {
        return $user->can('referrals.edit')
            && $referral->created_by === $user->id
            && $referral->status->isOpen();
    }

    public function reopen(User $user, Referral $referral): bool
    {
        return $user->can('referrals.edit')
            && $referral->created_by === $user->id
            && $referral->status === ReferralStatus::Cancelled;
    }
}
