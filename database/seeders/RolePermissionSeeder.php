<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Proposta inicial de permissões por perfil.
     * Deve ser validada com a coordenação do NAE.
     */
    public function run(): void
    {
        $atendimentos = ['attendances.view', 'attendances.create', 'attendances.edit', 'attendances.delete', 'attendances.finish'];
        $encaminhamentos = ['referrals.view', 'referrals.create', 'referrals.edit', 'referrals.delete'];

        $mapa = [
            // Administração técnica: usuários e perfis. Não acessa dados sensíveis.
            'administrator' => [
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'acl.assign_roles', 'acl.manage_permissions',
                'profiles.view', 'profiles.create', 'profiles.edit', 'profiles.delete',
                'profiles.export_pdf', 'profiles.view_public',
                'students.view', 'reports.view', 'reports.export_pdf',
            ],
            // Acompanhamento institucional: consulta e relatórios.
            'coordinator' => [
                'students.view', 'attendances.view', 'referrals.view',
                'reports.view', 'reports.export_pdf',
            ],
            // Porta de entrada do NAE: triagem e cadastro do estudante.
            'psychopedagogue' => array_merge(
                ['students.view', 'students.create', 'students.edit', 'students.delete', 'students.sensitive', 'reports.view'],
                $atendimentos,
                $encaminhamentos,
            ),
            // Recebem o estudante por encaminhamento interno.
            'psychologist' => array_merge(['students.view', 'students.sensitive'], $atendimentos, $encaminhamentos),
            'pedagogue' => array_merge(['students.view', 'students.sensitive'], $atendimentos, $encaminhamentos),
            // Apoio administrativo: agenda.
            'assistant' => ['students.view', 'attendances.view'],
        ];

        foreach ($mapa as $perfil => $permissoes) {
            $roleId = DB::table('roles')->where('slug', $perfil)->value('id');

            foreach (DB::table('permissions')->whereIn('slug', $permissoes)->pluck('id') as $permissionId) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }
}