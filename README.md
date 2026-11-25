# NAE UNIBALSAS

Plataforma Web de Gestão do Núcleo de Apoio ao Estudante (NAE) da UNIBALSAS.

## Progresso

| ID  | Milestone                                               | Status    |
| --- | ------------------------------------------------------- | --------- |
| M1  | Core + ACL (autenticação, usuários, perfis, permissões) | Concluído |
| M2  | UI/UX + Identidade Visual (Design System)               | Concluído |
| M3  | Estudantes (cadastro)                                   | Concluído |
| M4  | Triagem + Atendimentos                                  | Concluído |
| M5  | Acompanhamento + Histórico                              | Concluído |
| M6  | Encaminhamentos                                         | Concluído |
| M7  | Agenda                                                  | Concluído |
| M8  | Relatórios + Indicadores                                | Concluído |
| M9  | Segurança + Auditoria + LGPD                            | Concluído |
| M10 | Validação + Usabilidade (SUS)                           | Próximo   |

## Situação atual

Todos os módulos do SRS (M1 a M9) funcionam com dados reais. O próximo passo
é a validação com a equipe do NAE e a avaliação de usabilidade (M10).

Depois de atualizar o projeto, rode as migrations e atualize permissões e
perfis (o comando só acrescenta o que falta):

```bash
php artisan migrate
php artisan db:seed --class=AccessControlSeeder
```

### Dados de demonstração

```bash
php artisan db:seed --class=DemoDataSeeder
```

Cria estudantes, solicitações e atendimentos **fictícios** e uma conta para
cada perfil (senha `password`): `psicopedagoga@nae.test`,
`psicologa@nae.test`, `pedagoga@nae.test`, `coordenacao@nae.test` e
`assistente@nae.test`. Não roda em produção.

- **M1 — Core + ACL:** models `Module`/`Permission`/`Role`/`User`, migrations,
  seeders com os módulos/permissões/perfis iniciais do SRS, `Gate::before`
  para o `administrator`, middleware `permission:<slug>`, cache de
  permissões por usuário e shared props do Inertia (`auth.permissions`,
  `auth.roles`).
- **M2 — UI/UX + Identidade Visual:** layout Sidebar + Header responsivos
  (avatar com dropdown, breadcrumbs), Design System sobre Tailwind CSS v4 /
  shadcn-svelte / bits-ui / lucide-svelte com a paleta de cores do NAE, e
  Dashboard inicial com os dados reais de perfil/permissões do usuário.
- **M3 — Estudantes:** cadastro, edição, listagem, pesquisa (nome/matrícula)
  e filtros (curso/situação); inativação/reativação com soft delete (RN-013);
  CID (criptografado no banco) e documentos anexados (disco privado) como
  dados sensíveis, visíveis apenas para quem cadastrou (RN-012), inclusive
  para o administrador (pendência §28.13); nova permissão
  `students.sensitive`; auditoria via Spatie Activity Log sem gravar conteúdo
  sensível no log.
- **M4 — Triagem + Atendimentos:** Formulário de Solicitação do Apoio do NAE
  e fila de triagem (prioritárias primeiro); decisão da triagem pela
  psicopedagoga (agendar a 1ª conversa com profissional designado ou devolver
  à coordenação); agendamentos com bloqueio de conflito de horário do
  profissional; agenda semanal; registro do atendimento (data, início/término,
  duração, tipo, modalidade, demanda, condução, combinados, próximo retorno)
  e status (agendado, atendido, concluído, cancelado). Descrição da
  solicitação, parecer e conteúdo dos atendimentos são criptografados; o
  conteúdo do atendimento e o parecer só são visíveis para quem os registrou,
  sem exceção para o administrador (RN-012). Novas permissões `triage.view`,
  `triage.create`, `triage.perform` e `attendances.schedule`. Histórico do
  estudante e Painel com dados reais.
- **M5 — Acompanhamento + Histórico:** situação atual do acompanhamento
  derivada dos atendimentos (sem acompanhamento, aguardando 1º atendimento,
  em acompanhamento, encerrado), resumo (início, último atendimento,
  profissionais, encaminhamentos em aberto), linha do tempo unificada
  (solicitações, atendimentos e encaminhamentos), evolução do caso (o
  profissional vê o resumo dos próprios registros) e encerramento com motivo;
  um novo atendimento reabre o acompanhamento.
- **M6 — Encaminhamentos:** internos (para outro profissional do NAE) e
  externos (serviço fora do NAE), com origem, destino, motivo, data, prazo de
  retorno, situação (enviado, em andamento, aguardando retorno, concluído,
  cancelado) e registro do retorno (obrigatório para concluir). Encaminhar a
  partir de um atendimento muda o status dele para "Encaminhado". Motivo e
  retorno são criptografados e visíveis só para quem encaminhou e para o
  profissional de destino, sem exceção para o administrador (RN-012).
- **M7 — Agenda:** tela própria com a semana (segunda a sexta, e sábado
  quando houver atendimento), filtro por profissional ("Minha agenda" por
  padrão para profissionais), lista dos próximos 14 dias agrupada por dia e
  remarcação de agendamentos (data, horário, duração, profissional, tipo e
  modalidade), com bloqueio de conflito de horário e registro na auditoria.
- **M8 — Relatórios + Indicadores:** estudantes atendidos, atendimentos
  realizados/agendados/cancelados, novos acompanhamentos e encerramentos,
  encaminhamentos internos/externos, evolução mensal por tipo, por
  profissional, por curso, distribuição das demandas e origem das
  solicitações. Filtros por período, tipo, profissional, curso e modalidade.
  Exportação em PDF (versão para impressão) e Excel (CSV), cada uma com sua
  permissão e registrada na auditoria. Só números agregados: nenhum nome de
  estudante nem conteúdo sigiloso.
- **M9 — Segurança + Auditoria + LGPD:** gestão de contas da equipe (criar,
  editar, perfis, desativar/reativar, link de nova senha), sem cadastro
  público e sem exclusão de contas (RN-013); matriz de permissões por perfil;
  trilha de auditoria com filtros (quem, o quê, quando, quais campos — nunca
  os valores); registro de entradas, saídas, senhas erradas e bloqueios;
  contas desativadas perdem o acesso na hora; cabeçalhos de segurança e
  páginas autenticadas fora do cache do navegador; telas de login e
  configurações em português.

## Stack

- **Backend:** PHP 8.4, Laravel 13, Fortify, Spatie Activity Log
- **Frontend:** Svelte 5, Inertia.js, TypeScript, Tailwind CSS v4, shadcn-svelte
- **Persistência:** SQLite (dev) / MySQL 8 ou MariaDB (produção)
- **Qualidade:** Pest 4, PHPStan/Larastan, Laravel Pint

## Setup local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build
```

## Desenvolvimento

```bash
composer run dev
```

## Acesso para demonstração

Após rodar `composer run dev`, acesse http://localhost:8000 e entre com:

- **E-mail:** test@example.com
- **Senha:** password

Esse usuário é criado pelo seeder e tem perfil de Administrador: gerencia contas, perfis e auditoria, mas não vê o conteúdo sigiloso dos atendimentos (RN-012).

## Testes e qualidade

```bash
composer test        # config:clear + pint:check + phpstan + pest
./vendor/bin/pint     # formatação
./vendor/bin/phpstan analyse
./vendor/bin/pest
```
