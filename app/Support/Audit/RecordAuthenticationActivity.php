<?php

namespace App\Support\Audit;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Trilha de acesso (M9, RF-051 a RF-053): entradas, saídas, tentativas
 * com senha errada, bloqueios por excesso de tentativas e redefinições de senha.
 */
class RecordAuthenticationActivity
{
    public function handleLogin(Login $event): void
    {
        $this->record('login', 'Entrou no sistema', $event->user);
    }

    public function handleLogout(Logout $event): void
    {
        $this->record('logout', 'Saiu do sistema', $event->user);
    }

    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        $this->record('failed', 'Tentativa de acesso com senha incorreta', $event->user, [
            'email' => is_string($email) ? $email : null,
        ]);
    }

    public function handleLockout(Lockout $event): void
    {
        $email = $event->request->input('email');

        $this->record('lockout', 'Acesso bloqueado temporariamente por excesso de tentativas', null, [
            'email' => is_string($email) ? $email : null,
        ]);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->record('password_reset', 'Redefiniu a senha', $event->user);
    }

    /**
     * @param  array<string, string|null>  $properties
     */
    private function record(string $event, string $description, ?Authenticatable $user, array $properties = []): void
    {
        $activity = activity('auth')->event($event)->withProperties(array_filter([
            ...$properties,
            'ip' => request()->ip(),
        ]));

        if ($user instanceof User) {
            $activity->causedBy($user)->performedOn($user);
        }

        $activity->log($description);
    }
}
