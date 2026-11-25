<?php

namespace App\Support\Audit;

use App\Models\Attendance;
use App\Models\Referral;
use App\Models\Student;
use App\Models\StudentDisability;
use App\Models\StudentDocument;
use App\Models\SupportRequest;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Traduz os registros da auditoria para a tela (M9, RF-051 a RF-054).
 *
 * Mostra QUEM fez O QUÊ, QUANDO e EM QUAL registro, e o NOME dos campos
 * alterados, mas nunca os valores: os dados pessoais e sigilosos não
 * são exibidos na auditoria.
 */
final class AuditPresenter
{
    public const array MODULES = [
        'auth' => 'Acesso ao sistema',
        'users' => 'Usuários',
        'acl' => 'Perfis de acesso',
        'students' => 'Estudantes',
        'students.sensitive' => 'Dados sensíveis',
        'triage' => 'Triagem',
        'attendances' => 'Atendimentos',
        'referrals' => 'Encaminhamentos',
        'reports' => 'Relatórios',
    ];

    public const array EVENTS = [
        'created' => 'Cadastrou',
        'updated' => 'Alterou',
        'deleted' => 'Inativou',
        'restored' => 'Reativou',
        'login' => 'Entrou',
        'logout' => 'Saiu',
        'failed' => 'Senha incorreta',
        'lockout' => 'Bloqueio por tentativas',
        'password_reset' => 'Redefiniu a senha',
        'downloaded' => 'Baixou documento',
        'exported' => 'Exportou',
        'follow_up_closed' => 'Encerrou acompanhamento',
        'roles_updated' => 'Alterou perfis do usuário',
        'permissions_updated' => 'Alterou permissões do perfil',
        'deactivated' => 'Desativou conta',
        'reactivated' => 'Reativou conta',
        'password_reset_link' => 'Enviou link de senha',
    ];

    private const array SUBJECTS = [
        Student::class => 'Estudante',
        StudentDisability::class => 'CID do estudante',
        StudentDocument::class => 'Documento do estudante',
        SupportRequest::class => 'Solicitação de apoio',
        Attendance::class => 'Atendimento',
        Referral::class => 'Encaminhamento',
        User::class => 'Usuário',
    ];

    private const array FIELDS = [
        'name' => 'nome',
        'social_name' => 'nome social',
        'registration' => 'matrícula',
        'course' => 'curso',
        'period' => 'período',
        'shift' => 'turno',
        'modality' => 'modalidade',
        'email' => 'e-mail',
        'phone' => 'telefone',
        'birth_date' => 'data de nascimento',
        'sex' => 'sexo',
        'guardian_name' => 'contato de emergência',
        'guardian_phone' => 'telefone de emergência',
        'guardian_relationship' => 'parentesco',
        'needs_accessibility' => 'acessibilidade',
        'accessibility_resources' => 'recursos de acessibilidade',
        'has_external_follow_up' => 'acompanhamento externo',
        'consent_at' => 'consentimento',
        'status' => 'situação',
        'priority' => 'prioridade',
        'assigned_to' => 'profissional designado',
        'type' => 'tipo',
        'scheduled_at' => 'data/horário',
        'professional_id' => 'profissional',
        'kind' => 'tipo',
        'destination_user_id' => 'destino',
        'destination_name' => 'destino',
        'return_deadline' => 'prazo de retorno',
        'deactivated_at' => 'situação da conta',
        'deleted_at' => 'situação',
    ];

    /**
     * @return array{id: int, date: string, causer: string|null, module: string, event: string, description: string, subject: string|null, fields: list<string>, ip: string|null}
     */
    public static function present(Activity $activity): array
    {
        /** @var User|null $causer */
        $causer = $activity->causer;

        return [
            'id' => (int) $activity->id,
            'date' => $activity->created_at?->format('d/m/Y H:i:s') ?? '',
            'causer' => $causer?->name,
            'module' => self::MODULES[$activity->log_name ?? ''] ?? (string) $activity->log_name,
            'event' => self::EVENTS[$activity->event ?? ''] ?? (string) ($activity->event ?? $activity->description),
            'description' => self::describe($activity),
            'subject' => self::subject($activity),
            'fields' => self::fields($activity),
            'ip' => self::property($activity, 'ip'),
        ];
    }

    /**
     * @param  array<string, string>  $labels
     * @return list<array{value: string, label: string}>
     */
    public static function options(array $labels): array
    {
        return array_map(fn (string $value, string $label): array => ['value' => $value, 'label' => $label], array_keys($labels), $labels);
    }

    private static function subject(Activity $activity): ?string
    {
        if ($activity->subject_type === null) {
            return null;
        }

        $label = self::SUBJECTS[$activity->subject_type] ?? class_basename($activity->subject_type);

        return $label.' nº '.$activity->subject_id;
    }

    /**
     * Só o nome dos campos alterados, nunca os valores.
     *
     * @return list<string>
     */
    private static function fields(Activity $activity): array
    {
        if ($activity->event !== 'updated') {
            return [];
        }

        $changes = $activity->attribute_changes?->get('attributes');

        if (! is_array($changes)) {
            return [];
        }

        return array_values(array_unique(array_map(
            fn (int|string $field): string => self::FIELDS[(string) $field] ?? (string) $field,
            array_keys($changes),
        )));
    }

    private static function describe(Activity $activity): string
    {
        return match ($activity->event) {
            'failed', 'lockout' => 'E-mail usado: '.(self::property($activity, 'email') ?? 'não informado'),
            'exported' => 'Formato: '.mb_strtoupper(self::property($activity, 'format') ?? ''),
            'roles_updated' => 'Perfis: '.(self::property($activity, 'roles') ?? 'nenhum'),
            'permissions_updated' => self::property($activity, 'summary') ?? '',
            default => '',
        };
    }

    private static function property(Activity $activity, string $key): ?string
    {
        $value = $activity->properties?->get($key);

        return is_scalar($value) ? (string) $value : null;
    }
}
