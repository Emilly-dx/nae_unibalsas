<?php

namespace App\Providers;

use App\Models\Contracts\SensitiveRecord;
use App\Models\StudentDisability;
use App\Models\StudentDocument;
use App\Models\User;
use App\Policies\SensitiveRecordPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure the application's ACL: administrators bypass every check,
     * everyone else is resolved against their permission slugs.
     *
     * Exceção: registros sensíveis (RN-012) são sempre decididos pela
     * policy, sem bypass do administrador (SRS §28.13, pendente com o NAE).
     */
    protected function configureAuthorization(): void
    {
        Gate::policy(StudentDisability::class, SensitiveRecordPolicy::class);
        Gate::policy(StudentDocument::class, SensitiveRecordPolicy::class);

        Gate::before(function (User $user, string $ability, array $arguments) {
            foreach ($arguments as $argument) {
                if ($argument instanceof SensitiveRecord) {
                    return null;
                }
            }

            return $user->hasRole('administrator') ? true : null;
        });

        Gate::after(function (User $user, string $ability) {
            return $user->hasPermissionTo($ability);
        });
    }
}
