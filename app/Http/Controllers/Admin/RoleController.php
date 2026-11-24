<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ACL\Module;
use App\Models\ACL\Permission;
use App\Models\ACL\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Perfis de acesso e suas permissões (RF-005 a RF-009, SRS §15.4).
 *
 * O perfil Administrador não é editável: ele já tem acesso administrativo
 * completo (exceto aos dados sensíveis, que seguem a regra por registro).
 */
class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $roles = Role::query()->with('permissions:id')->withCount('users')->orderBy('id')->get();

        return Inertia::render('admin/Roles', [
            'roles' => $roles->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'users' => (int) $role->getAttribute('users_count'),
                'permissions' => $role->permissions->pluck('id')->values(),
                'locked' => $role->slug === 'administrator',
            ])->values(),
            'modules' => Module::query()->with(['permissions' => fn ($query) => $query->orderBy('id')])->orderBy('id')->get()
                ->map(fn (Module $module): array => [
                    'name' => $module->name,
                    'permissions' => $module->permissions
                        ->map(fn (Permission $permission): array => ['id' => $permission->id, 'name' => $permission->name, 'slug' => $permission->slug])
                        ->values(),
                ])->values(),
            'canManage' => $request->user()?->can('acl.manage_permissions') ?? false,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->slug === 'administrator', 403);

        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        /** @var list<int> $ids */
        $ids = array_values(array_map(intval(...), $validated['permissions']));
        $before = $role->permissions()->pluck('permissions.id')->all();

        $role->syncPermissions($ids);

        $added = Permission::query()->whereIn('id', array_diff($ids, $before))->pluck('slug');
        $removed = Permission::query()->whereIn('id', array_diff($before, $ids))->pluck('slug');

        if ($added->isNotEmpty() || $removed->isNotEmpty()) {
            activity('acl')->causedBy($request->user())
                ->event('permissions_updated')
                ->withProperties([
                    'role' => $role->slug,
                    'added' => $added->all(),
                    'removed' => $removed->all(),
                    'summary' => "{$role->name}: +".$added->count().' / −'.$removed->count().' permissões',
                ])
                ->log('Permissões do perfil alteradas');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Permissões do perfil {$role->name} salvas."]);

        return back();
    }
}
