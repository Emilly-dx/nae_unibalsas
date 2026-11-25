<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\ACL\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contas da equipe do NAE (M9): cadastro, perfis de acesso e desativação.
 * Contas não são excluídas (RN-013): a desativação mantém a autoria dos
 * registros e a trilha de auditoria.
 */
class UserController extends Controller
{
    private const int PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $search = trim($request->string('busca')->toString());
        $role = $request->string('perfil')->toString();
        $status = $request->string('situacao', 'ativos')->toString();

        $users = User::query()
            ->with('roles')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($role !== '', fn (Builder $query) => $query->whereHas('roles', fn (Builder $query) => $query->where('slug', $role)))
            ->when($status === 'ativos', fn (Builder $query) => $query->whereNull('deactivated_at'))
            ->when($status === 'inativos', fn (Builder $query) => $query->whereNotNull('deactivated_at'))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $lastLogins = DB::table('activity_log')
            ->where('log_name', 'auth')->where('event', 'login')
            ->where('causer_type', (new User)->getMorphClass())
            ->whereIn('causer_id', $users->getCollection()->pluck('id'))
            ->groupBy('causer_id')
            ->pluck(DB::raw('max(created_at)'), 'causer_id');

        /** @var User $me */
        $me = $request->user();

        return Inertia::render('admin/users/Index', [
            'users' => $users->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->values(),
                'active' => $user->isActive(),
                'last_login' => isset($lastLogins[$user->id])
                    ? CarbonImmutable::parse((string) $lastLogins[$user->id])->format('d/m/Y H:i')
                    : null,
                'is_me' => $user->id === $me->id,
            ]),
            'filters' => ['busca' => $search, 'perfil' => $role, 'situacao' => $status],
            'roles' => $this->roleOptions(),
            'can' => [
                'create' => $me->can('users.create'),
                'edit' => $me->can('users.edit'),
                'deactivate' => $me->can('users.delete'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/users/Form', [
            'user' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $roleIds = $request->roleIds();
        $this->guardAdministratorRole($request, null, $roleIds);

        $user = DB::transaction(function () use ($request, $roleIds): User {
            $user = new User([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
            ]);
            // Contas criadas pela administração já valem como verificadas.
            $user->forceFill(['email_verified_at' => now()])->save();

            if ($request->user()?->can('acl.assign_roles')) {
                $this->syncRoles($request, $user, $roleIds);
            }

            return $user;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Conta de {$user->name} criada. Passe a senha provisória pessoalmente e peça para trocá-la no primeiro acesso."]);

        return to_route('admin.users.index');
    }

    public function edit(Request $request, User $user): Response
    {
        $user->load('roles');

        return Inertia::render('admin/users/Form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('id')->values(),
                'active' => $user->isActive(),
                'is_me' => $user->id === $request->user()?->id,
            ],
            ...$this->formOptions($request),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $canAssignRoles = $request->user()?->can('acl.assign_roles') ?? false;
        $roleIds = $request->roleIds();

        if ($canAssignRoles) {
            $this->guardAdministratorRole($request, $user, $roleIds);
        }

        DB::transaction(function () use ($request, $user, $roleIds, $canAssignRoles): void {
            $user->fill([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
            ])->save();

            if ($canAssignRoles) {
                $this->syncRoles($request, $user, $roleIds);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Conta atualizada.']);

        return to_route('admin.users.index');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()?->id) {
            throw ValidationException::withMessages(['user' => 'Você não pode desativar a sua própria conta.']);
        }

        $this->guardAdministratorTarget($request, $user);

        $user->forceFill(['deactivated_at' => now()])->save();

        // Encerra as sessões abertas da conta.
        DB::table('sessions')->where('user_id', $user->id)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Conta de {$user->name} desativada."]);

        return back();
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $this->guardAdministratorTarget($request, $user);

        $user->forceFill(['deactivated_at' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Conta de {$user->name} reativada."]);

        return back();
    }

    /**
     * Envia ao usuário o link para ele mesmo criar uma nova senha.
     */
    public function sendPasswordResetLink(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isActive(), 422, 'Reative a conta antes de enviar o link.');

        Password::broker()->sendResetLink(['email' => $user->email]);

        activity('users')->causedBy($request->user())->performedOn($user)
            ->event('password_reset_link')->log('Link de redefinição de senha enviado');

        Inertia::flash('toast', ['type' => 'success', 'message' => "Link de redefinição enviado para {$user->email}."]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        return [
            'roles' => Role::query()->orderBy('id')->get(['id', 'name', 'slug'])
                ->map(fn (Role $role): array => ['value' => $role->id, 'label' => $role->name, 'slug' => $role->slug])
                ->values(),
            'canAssignRoles' => $request->user()?->can('acl.assign_roles') ?? false,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return array_values(Role::query()->orderBy('id')->get(['slug', 'name'])
            ->map(fn (Role $role): array => ['value' => $role->slug, 'label' => $role->name])
            ->all());
    }

    /**
     * @param  list<int>  $roleIds
     */
    private function syncRoles(Request $request, User $user, array $roleIds): void
    {
        $before = $user->roles()->pluck('roles.id')->sort()->values()->all();
        $user->syncRoles(Role::query()->whereIn('id', $roleIds)->get()->all());
        $after = $user->roles()->pluck('roles.id')->sort()->values()->all();

        if ($before !== $after) {
            activity('users')->causedBy($request->user())->performedOn($user)
                ->event('roles_updated')
                ->withProperties(['roles' => $user->roles()->pluck('name')->implode(', ')])
                ->log('Perfis de acesso alterados');
        }
    }

    /**
     * Só um administrador concede ou retira o perfil de Administrador,
     * e ninguém retira o próprio (para não perder o acesso à administração).
     *
     * @param  list<int>  $roleIds
     */
    private function guardAdministratorRole(Request $request, ?User $user, array $roleIds): void
    {
        /** @var User $me */
        $me = $request->user();
        $adminId = Role::query()->where('slug', 'administrator')->value('id');
        $wantsAdmin = in_array($adminId, $roleIds, true);
        $isAdmin = $user?->hasRole('administrator') ?? false;

        if ($wantsAdmin !== $isAdmin && ! $me->hasRole('administrator')) {
            throw ValidationException::withMessages(['roles' => 'Só um administrador pode conceder ou retirar o perfil de Administrador.']);
        }

        if ($user?->id === $me->id && $isAdmin && ! $wantsAdmin) {
            throw ValidationException::withMessages(['roles' => 'Você não pode retirar o seu próprio perfil de Administrador.']);
        }
    }

    private function guardAdministratorTarget(Request $request, User $user): void
    {
        /** @var User $me */
        $me = $request->user();

        abort_if($user->hasRole('administrator') && ! $me->hasRole('administrator'), 403);
    }
}
