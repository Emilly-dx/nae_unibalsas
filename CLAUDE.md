# CLAUDE.md — NAE UNIBALSAS

Plataforma Web de Gestão do Núcleo de Apoio ao Estudante (NAE) da UNIBALSAS.
Projeto de iniciação científica desenvolvido junto ao orientador, com requisitos
levantados junto à coordenadora do NAE (Mayara Costa Ramalho). O documento de
referência completo é o SRS (Software Requirements Specification) v1.0 — este
arquivo resume as regras que devem orientar qualquer trabalho neste
repositório. Em caso de dúvida, o SRS é a fonte de verdade.

## Stack (fixa pelo SRS — não trocar sem validar com o orientador)

- **Backend:** PHP 8.4, Laravel 13, `laravel/svelte-starter-kit`, Laravel Fortify
- **Frontend:** Svelte 5, Inertia.js, TypeScript, Tailwind CSS v4, bits-ui,
  shadcn-svelte, lucide-svelte, Wayfinder, svelte-sonner
- **Persistência:** MySQL 8 ou MariaDB em produção; SQLite em dev.
  Database Queue, Database Cache, Database Session.
- **Qualidade:** Pest 4 (testes), PHPStan + Larastan (análise estática),
  Laravel Pint (formatação)
- **Auditoria:** Spatie Activity Log

## Arquitetura

**Single-Tenant.** Não introduzir `workspace_id`, `tenant_id`,
`workspace_user` ou qualquer estrutura de multi-tenancy — está
explicitamente fora do escopo (seção 1.5 / RN-015).

```
User -> Role -> Permission -> Module
```

## Domínio / stakeholders

- **Mayara Costa Ramalho** — Psicopedagoga e Coordenadora do NAE, principal
  validadora de requisitos, fluxos e protótipos.
- **Roles previstas:** `administrator`, `coordinator`, `psychopedagogue`,
  `psychologist`, `pedagogue`, `assistant`.
- **Estudante** é atendido pelo NAE, mas **não** tem acesso direto à
  plataforma nesta versão.
- Coordenações de curso da UNIBALSAS encaminham estudantes ao NAE (não usam
  o sistema diretamente nesta versão, apenas originam o processo).

## Fluxo geral

```
Coordenação do curso -> Encaminhamento ao NAE -> Triagem (Psicopedagoga)
  -> Atendimento direto OU Encaminhamento (Interno: Psicologia | Externo: Clínica/serviço)
  -> Registro -> Conclusão
```

- Encaminhamento para **Psicologia** = **interno** ao NAE.
- Encaminhamento para **Clínica UNIBALSAS** ou outro serviço = **externo**.

## Milestones (ordem do roadmap — seguir esta sequência)

| ID  | Milestone                                                              |
| --- | ---------------------------------------------------------------------- |
| M1  | Core + ACL (autenticação, usuários, perfis, permissões)                |
| M2  | UI/UX + Identidade Visual (Design System antes dos módulos de negócio) |
| M3  | Estudantes (cadastro)                                                  |
| M4  | Triagem + Atendimentos (agendamento e registro)                        |
| M5  | Acompanhamento + Histórico                                             |
| M6  | Encaminhamentos (internos/externos)                                    |
| M7  | Agenda                                                                 |
| M8  | Relatórios + Indicadores                                               |
| M9  | Segurança + Auditoria + LGPD                                           |
| M10 | Validação + Usabilidade (SUS)                                          |

**Segurança é transversal a todos os milestones** — não implementar apenas
no M9. Autorização sempre no backend (`Gate`/`permission` middleware); as
permissões enviadas ao frontend via Inertia são só para UI, nunca a fonte
de autorização.

## M1 — Core + ACL (referência de implementação)

- Models: `App\Models\ACL\Module`, `App\Models\ACL\Permission`,
  `App\Models\ACL\Role`, `App\Models\User`.
- Tabelas: `users`, `roles`, `permissions`, `modules`, `role_user`,
  `role_permission`.
- `User belongsToMany Role belongsToMany Permission belongsTo Module`.
- `Gate::before` dá acesso total ao role `administrator`.
- Middleware `permission:<slug>` usa `Gate::allows()`.
- Cache de permissões em `permissions.user.{id}`, invalidado ao alterar
  Roles/Permissions do usuário.
- `HandleInertiaRequests` compartilha `auth.user`, `auth.permissions`,
  `auth.roles`.
- Métodos esperados no `User`: `roles()`, `permissionSlugs()`,
  `hasPermission()`, `hasPermissionTo()`, `allPermissions()`,
  `clearPermissionsCache()`, `hasRole()`, `assignRole()`, `syncRoles()`,
  `avatarUrl()`.
- Módulos/permissões iniciais: `profiles`, `users`, `acl` (ver SRS seção 7.5)
  e módulos de negócio previstos: `students`, `attendances`, `referrals`,
  `reports` (seção 7.6) — permissões granulares por ação
  (`view`, `create`, `edit`, `delete`, etc.).

## Regras de negócio obrigatórias

- **RN-011/RN-012 — Privacidade por registro:** informação sensível
  cadastrada por um usuário só pode ser vista por esse mesmo usuário
  (autorização a nível de registro, não apenas por perfil/role). Regra
  inicial, mas obrigatória até nova validação.
- **RN-013 — Não há exclusão definitiva** de registros clínicos ou
  institucionais (soft delete / inativação, nunca `DELETE` físico).
- **RN-009/RN-010 — Acompanhamento** é o conjunto/histórico de atendimentos
  do estudante, não uma entidade duplicada. Estudante não tem múltiplos
  acompanhamentos simultâneos independentes — nova necessidade gera
  encaminhamento, não um segundo acompanhamento paralelo.
- **RN-003/RN-005 — Encaminhamento interno**: entre profissionais do NAE
  (ex.: Psicopedagoga -> Psicólogo). **RN-004 — Externo**: para fora do NAE
  (ex.: Clínica UNIBALSAS).
- **RN-014 — Sem integração** com o sistema acadêmico da UNIBALSAS nesta
  versão.
- Informações sensíveis/restritas (seção 15.2): diagnóstico, laudo,
  informações psicológicas/psicopedagógicas, observações do profissional,
  documentos anexados. Documentos seguem a mesma regra de autorização do
  registro ao qual pertencem.
- Princípio do menor privilégio: usuários só recebem as permissões
  necessárias às suas atividades.

## Pontos explicitamente pendentes de validação com o NAE (seção 28)

Não tratar como definitivos até validação — se a implementação de um
módulo depender de um destes pontos, sinalizar a decisão tomada e por quê
(ou parar e perguntar):

1. Campos exatos da triagem.
2. Diferença operacional entre os status `ATENDIDO` e `CONCLUÍDO`.
3. Status definitivos dos encaminhamentos.
4. Regras de edição de registros históricos.
5. Necessidade de versionamento das alterações.
6. Quem pode cancelar um atendimento.
7. Necessidade de registrar motivo do cancelamento.
8. Regra de conflito de horários na agenda.
9. Duração padrão dos atendimentos.
10. Indicadores oficiais desejados pelo NAE.
11. Permissões definitivas por perfil.
12. Regras de acesso a informações sensíveis entre profissionais da mesma
    área.
13. Acesso do administrador às informações sensíveis.
14. Política de documentos anexados.
15. Política de retenção dos dados.

## Fora de escopo (não implementar sem pedido explícito)

Multi-Tenancy, integração automática com o sistema acadêmico, integrações
externas não validadas, app mobile nativo, portal público, automação de
diagnóstico, substituição da avaliação profissional dos membros do NAE.

## Fluxo de desenvolvimento por milestone

```
Requisito -> Regra de negócio -> Modelagem -> Migration -> Model/Domain
  -> Validation -> Authorization -> Backend -> Inertia -> Svelte
  -> Testes -> Auditoria -> Validação
```

## Critérios de aceite de qualquer funcionalidade

Requisito implementado; interface integrada ao backend; autorização
implementada; validações implementadas; estados de erro tratados;
responsivo; registros auditáveis (Spatie Activity Log); testes Pest
implementados; sem regressões conhecidas; demonstrável aos stakeholders.

## Convenções de qualidade

- `composer test` roda: `config:clear` -> `pint --test` -> `phpstan analyse`
  -> `php artisan test` (Pest). Rodar antes de considerar algo pronto.
- Testes novos em Pest (sintaxe funcional), não em classes PHPUnit.
- Nenhuma funcionalidade nova sem cobertura mínima de testes nos pontos
  críticos de negócio (autenticação, autorização, dados sensíveis).
