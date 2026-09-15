<?php

namespace Database\Seeders;

use App\Models\ACL\Module;
use App\Models\ACL\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Initial permissions per module (SRS §7.5, §7.6).
     */
    public function run(): void
    {
        $permissionsByModule = [
            'profiles' => [
                'profiles.view' => 'Visualizar perfis',
                'profiles.create' => 'Criar perfis',
                'profiles.edit' => 'Editar perfis',
                'profiles.delete' => 'Excluir perfis',
                'profiles.export_pdf' => 'Exportar perfis em PDF',
                'profiles.view_public' => 'Visualizar perfis públicos',
            ],
            'users' => [
                'users.view' => 'Visualizar usuários',
                'users.create' => 'Criar usuários',
                'users.edit' => 'Editar usuários',
                'users.delete' => 'Excluir usuários',
            ],
            'acl' => [
                'acl.assign_roles' => 'Atribuir perfis de acesso',
                'acl.manage_permissions' => 'Gerenciar permissões',
            ],
            'students' => [
                'students.view' => 'Visualizar estudantes',
                'students.create' => 'Cadastrar estudantes',
                'students.edit' => 'Editar estudantes',
                'students.delete' => 'Excluir estudantes',
            ],
            'attendances' => [
                'attendances.view' => 'Visualizar atendimentos',
                'attendances.create' => 'Criar atendimentos',
                'attendances.edit' => 'Editar atendimentos',
                'attendances.delete' => 'Excluir atendimentos',
                'attendances.finish' => 'Concluir atendimentos',
            ],
            'referrals' => [
                'referrals.view' => 'Visualizar encaminhamentos',
                'referrals.create' => 'Criar encaminhamentos',
                'referrals.edit' => 'Editar encaminhamentos',
                'referrals.delete' => 'Excluir encaminhamentos',
            ],
            'reports' => [
                'reports.view' => 'Visualizar relatórios',
                'reports.export_pdf' => 'Exportar relatórios em PDF',
            ],
        ];

        foreach ($permissionsByModule as $moduleSlug => $permissions) {
            $module = Module::query()->where('slug', $moduleSlug)->firstOrFail();

            foreach ($permissions as $slug => $name) {
                Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'module_id' => $module->id],
                );
            }
        }
    }
}
