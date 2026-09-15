# NAE UNIBALSAS

Plataforma Web de Gestão do Núcleo de Apoio ao Estudante (NAE) da UNIBALSAS.

Consulte [`CLAUDE.md`](./CLAUDE.md) para o contexto completo do projeto: escopo,
milestones, regras de negócio e requisitos definidos no SRS.

## Progresso

| ID  | Milestone                                               | Status       |
| --- | ------------------------------------------------------- | ------------ |
| M1  | Core + ACL (autenticação, usuários, perfis, permissões) | ✅ Concluído |
| M2  | UI/UX + Identidade Visual (Design System)               | ✅ Concluído |
| M3  | Estudantes (cadastro)                                   | ⏳ Próximo   |
| M4  | Triagem + Atendimentos                                  | Pendente     |
| M5  | Acompanhamento + Histórico                              | Pendente     |
| M6  | Encaminhamentos                                         | Pendente     |
| M7  | Agenda                                                  | Pendente     |
| M8  | Relatórios + Indicadores                                | Pendente     |
| M9  | Segurança + Auditoria + LGPD                            | Pendente     |
| M10 | Validação + Usabilidade (SUS)                           | Pendente     |

- **M1 — Core + ACL:** models `Module`/`Permission`/`Role`/`User`, migrations,
  seeders com os módulos/permissões/perfis iniciais do SRS, `Gate::before`
  para o `administrator`, middleware `permission:<slug>`, cache de
  permissões por usuário e shared props do Inertia (`auth.permissions`,
  `auth.roles`).
- **M2 — UI/UX + Identidade Visual:** layout Sidebar + Header responsivos
  (avatar com dropdown, breadcrumbs), Design System sobre Tailwind CSS v4 /
  shadcn-svelte / bits-ui / lucide-svelte com a paleta de cores do NAE, e
  Dashboard inicial com os dados reais de perfil/permissões do usuário.

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
php artisan migrate
npm run build
```

## Desenvolvimento

```bash
composer run dev
```

## Testes e qualidade

```bash
composer test        # config:clear + pint:check + phpstan + pest
./vendor/bin/pint     # formatação
./vendor/bin/phpstan analyse
./vendor/bin/pest
```
