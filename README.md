# NAE UNIBALSAS

Plataforma Web de Gestão do Núcleo de Apoio ao Estudante (NAE) da UNIBALSAS.

Consulte [`CLAUDE.md`](./CLAUDE.md) para o contexto completo do projeto: escopo,
milestones, regras de negócio e requisitos definidos no SRS.

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
