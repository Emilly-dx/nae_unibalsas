<?php

namespace App\Policies;

use App\Models\Contracts\SensitiveRecord;
use App\Models\User;

/**
 * Autorização em nível de registro para informações sensíveis
 * (RN-011/RN-012, SRS §15.3): só quem cadastrou visualiza/remove.
 *
 * O administrador NÃO tem bypass aqui (ver AppServiceProvider) até
 * a validação do item §28.13 com o NAE.
 */
class SensitiveRecordPolicy
{
    public function view(User $user, SensitiveRecord $record): bool
    {
        return $user->can('students.sensitive') && $record->ownerId() === $user->id;
    }

    public function delete(User $user, SensitiveRecord $record): bool
    {
        return $this->view($user, $record);
    }
}
