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

Todos os módulos do SRS (M1 a M9) estão prontos e testados (224 testes
automatizados). O próximo passo é a validação com a equipe do NAE e a
avaliação de usabilidade (M10).

**Versão de testes no ar:** https://nae-unibalsas-teste-kukwe1.laravel.cloud
(Laravel Cloud, servidor nos EUA, só com dados fictícios). As contas e as
senhas são entregues pessoalmente; nenhuma senha fica neste repositório.

## O que cada módulo faz

- **M1 — Core + ACL:** models `Module`/`Permission`/`Role`/`User`, migrations,
  seeders com os módulos/permissões/perfis iniciais do SRS, `Gate::before`
  para o `administrator` (exceto conteúdo sigiloso), middleware
  `permission:<slug>`, cache de permissões por usuário e shared props do
  Inertia (`auth.permissions`, `auth.roles`).
- **M2 — UI/UX + Identidade Visual:** layout Sidebar + Header responsivos
  (avatar com dropdown, breadcrumbs), Design System sobre Tailwind CSS v4 /
  shadcn-svelte / bits-ui / lucide-svelte com a paleta de cores do NAE,
  páginas de erro em português e Painel com dados reais.
- **M3 — Estudantes:** cadastro, edição, listagem, pesquisa (nome/matrícula,
  com ou sem pontos) e filtros (curso/situação); máscaras de telefone e
  matrícula; inativação/reativação com soft delete (RN-013); CID
  (criptografado no banco) e documentos anexados (disco privado, até 10 MB)
  como dados sensíveis, visíveis apenas para quem cadastrou (RN-012),
  inclusive para o administrador (pendência §28.13); permissão
  `students.sensitive`; auditoria sem gravar conteúdo sensível.
- **M4 — Triagem + Atendimentos:** Formulário de Solicitação do Apoio do NAE
  e fila de triagem (prioritárias primeiro); decisão da triagem pela
  psicopedagoga (agendar a 1ª conversa com profissional designado ou devolver
  à coordenação); agendamentos com bloqueio de conflito de horário do
  profissional; registro do atendimento (data, início/término, duração, tipo,
  modalidade, demanda, condução, combinados, próximo retorno) e status
  (agendado, atendido, encaminhado, concluído, cancelado). Descrição da
  solicitação, parecer e conteúdo dos atendimentos são criptografados e só
  visíveis para quem os registrou, sem exceção para o administrador (RN-012).
- **M5 — Acompanhamento + Histórico:** situação do acompanhamento derivada dos
  atendimentos (sem acompanhamento, aguardando 1º atendimento, em
  acompanhamento, encerrado), resumo, linha do tempo unificada (solicitações,
  atendimentos e encaminhamentos), evolução do caso e encerramento com
  motivo; um novo atendimento reabre o acompanhamento.
- **M6 — Encaminhamentos:** internos (outro profissional do NAE) e externos
  (serviço fora do NAE), com origem, destino, motivo, data, prazo de retorno,
  situação e registro do retorno (obrigatório para concluir). Motivo e
  retorno são criptografados e visíveis só para quem encaminhou e para o
  destino (RN-012).
- **M7 — Agenda:** semana (segunda a sexta, e sábado quando houver
  atendimento), filtro por profissional ("Minha agenda" por padrão), próximos
  14 dias agrupados por dia e remarcação com bloqueio de conflito de horário.
- **M8 — Relatórios + Indicadores:** atendimentos, acompanhamentos,
  encaminhamentos e solicitações por período, tipo, profissional, curso e
  modalidade, com evolução mensal. Exportação em PDF (impressão) e CSV, cada
  uma com sua permissão e registrada na auditoria. Só números agregados:
  nenhum nome de estudante nem conteúdo sigiloso.
- **M9 — Segurança + Auditoria + LGPD:** gestão de contas da equipe (criar,
  editar, perfis, desativar/reativar, link de nova senha), sem cadastro
  público e sem exclusão de contas (RN-013); matriz de permissões por perfil;
  trilha de auditoria com filtros (quem, o quê, quando, quais campos — nunca
  os valores); registro de entradas, saídas, senhas erradas e bloqueios;
  contas desativadas perdem o acesso na hora; cabeçalhos de segurança e
  páginas autenticadas fora do cache do navegador; telas em português.

## Stack

- **Backend:** PHP 8.4, Laravel 13, Fortify, Spatie Activity Log
- **Frontend:** Svelte 5, Inertia.js, TypeScript, Tailwind CSS v4, shadcn-svelte
- **Persistência:** SQLite (dev) / MySQL 8 (produção e versão de testes); também testado em PostgreSQL
- **Qualidade:** Pest 4, PHPStan/Larastan, Laravel Pint, Vite+ (`npm run check`), GitHub Actions
- **Hospedagem de testes:** Laravel Cloud (bucket privado para os documentos)

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

Depois de atualizar o projeto (`git pull`), rode as migrations e atualize
permissões e perfis (o comando só acrescenta o que falta):

```bash
php artisan migrate
php artisan db:seed --class=AccessControlSeeder
```

## Desenvolvimento

```bash
composer run dev
```

## Acesso para demonstração (computador local)

Após rodar `composer run dev`, acesse http://localhost:8000 e entre com:

- **E-mail:** test@example.com
- **Senha:** password

Esse usuário é criado pelo seeder **só no computador local** e tem perfil de
Administrador: gerencia contas, perfis e auditoria, mas não vê o conteúdo
sigiloso dos atendimentos (RN-012).

Para ter dados fictícios (estudantes, solicitações, atendimentos,
encaminhamentos e uma conta para cada perfil):

```bash
php artisan db:seed --class=DemoDataSeeder
```

Contas criadas: `psicopedagoga@nae.test`, `psicologa@nae.test`,
`pedagoga@nae.test`, `coordenacao@nae.test` e `assistente@nae.test`. A senha
vem de `NAE_DEMO_PASSWORD` (no computador local, o padrão é `password`; fora
dele o seeder exige uma senha forte e nunca roda em produção).

### Comandos do projeto

| Comando                             | Para quê                                                                                               |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `php artisan nae:criar-admin`       | Cria uma conta de Administrador (pede nome, e-mail e senha, ou use `--name/--email/--password`)        |
| `php artisan nae:preparar-servidor` | Perfis, primeiro administrador e dados fictícios a partir das variáveis `NAE_*` (seguro a cada deploy) |

## Colocar no ar (hospedagem)

Requisitos do servidor: PHP 8.4 (extensões `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`), MySQL 8, Composer e HTTPS. O Node 22 só é necessário para gerar as telas (`npm run build`), o que pode ser feito no próprio computador antes de enviar.

1. **Código e dependências**
    ```bash
    composer install --no-dev --optimize-autoloader
    npm ci && npm run build
    ```
2. **`.env` de produção** (copie de `.env.example` e ajuste):
    ```dotenv
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://endereco-do-sistema
    DB_CONNECTION=mysql
    DB_HOST=...
    DB_DATABASE=...
    DB_USERNAME=...
    DB_PASSWORD=...
    SESSION_SECURE_COOKIE=true
    LOG_LEVEL=warning
    MAIL_MAILER=smtp        # e os dados do e-mail, para o link de nova senha
    ```
    Depois gere uma chave nova: `php artisan key:generate`. Nunca reaproveite a chave nem as senhas do computador local.
3. **Banco, perfis e o primeiro administrador**
    ```bash
    php artisan migrate --force
    php artisan db:seed --force          # em produção cria só módulos, permissões e perfis
    php artisan nae:criar-admin          # pede nome, e-mail e senha
    php artisan optimize
    ```
    As demais contas são criadas pela tela **Usuários**.
4. **Servidor web:** a raiz do site deve ser a pasta `public/`. As pastas `storage/` e `bootstrap/cache/` precisam de permissão de escrita.
5. **Uploads:** no `php.ini`, `upload_max_filesize = 12M` e `post_max_size = 16M` (o sistema aceita laudos de até 10 MB). Os documentos ficam no disco privado (`storage/app/private`), fora da pasta pública, ou num bucket privado indicado em `NAE_DOCUMENTS_DISK`.
6. **Backup:** configure cópia diária do banco MySQL e da pasta `storage/app/private`.

**Ao atualizar o sistema num servidor próprio:**

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=AccessControlSeeder --force
php artisan optimize
php artisan up
```

**Versão de testes com dados fictícios:** use `APP_ENV=staging`, defina uma senha forte em `NAE_DEMO_PASSWORD` e `NAE_DEMO_DATA=true`, e rode `php artisan nae:preparar-servidor`. Não use dados reais de estudantes nessa versão.

### Versão de testes no Laravel Cloud

A versão de testes (dados fictícios) está no [Laravel Cloud](https://cloud.laravel.com), plano Starter, região **US East (Virginia)**. Como foi configurada:

1. **Aplicação:** _New application_ com o repositório `nae_unibalsas`. Em **Settings → General**, a branch é a **`main`** (a `teste` é só para trabalhar) e o acesso ao repositório é somente leitura.
2. **Recursos** (painel do ambiente):
    - **Laravel MySQL** 8.4 (Flex, dorme quando não há uso).
    - **Bucket** `nae-laudos` (Laravel Object Storage), visibilidade **Private**, disco `documentos`, **não** é o disco padrão. Os laudos só são baixados pelo sistema, depois da verificação de permissão.
3. **Settings → General:** PHP **8.4** e Node **22** (as versões em que o projeto é testado).
4. **Custom environment variables** (o `APP_KEY` e os dados do banco o Cloud gera sozinho; mantenha a linha do `APP_KEY`):
    ```dotenv
    APP_NAME="NAE UNIBALSAS"
    VITE_APP_NAME="NAE UNIBALSAS"
    APP_ENV=staging
    APP_DEBUG=false
    LOG_LEVEL=warning
    SESSION_DRIVER=database
    SESSION_SECURE_COOKIE=true
    CACHE_STORE=database
    QUEUE_CONNECTION=sync
    MAIL_MAILER=log
    NAE_DOCUMENTS_DISK=documentos
    NAE_ADMIN_NAME="Administração do NAE"
    NAE_ADMIN_EMAIL=...
    NAE_ADMIN_PASSWORD=...
    NAE_DEMO_DATA=true
    NAE_DEMO_PASSWORD=...
    ```
5. **Settings → Deployments:**
    - _Build commands_: o padrão do Cloud (`composer install --no-dev ...`, `npm ci`, `npm run build`). O Faker está no `require` para os dados fictícios funcionarem sem as dependências de desenvolvimento.
    - _Deploy commands_:
        ```bash
        php artisan migrate --force
        php artisan nae:preparar-servidor
        ```
        O `nae:preparar-servidor` confere perfis e permissões a cada deploy e cria o administrador e os dados fictícios **só na primeira vez**. Depois do primeiro deploy, a variável `NAE_ADMIN_PASSWORD` pode ser apagada.
    - _Push to deploy_ ligado: cada merge na `main` publica a nova versão.
6. **Organização → Settings → Billing:** limite de gasto de **US$ 5** com **Stop compute at the limit**, para nunca passar do crédito mensal.

As demais contas (professor, equipe do NAE) são criadas pela tela **Usuários**, cada pessoa com a sua.

**Encerrar a versão de testes:** em **Settings → Billing → Cancel subscription** antes do fim do primeiro mês, e remova o cartão.

**Antes de usar com dados reais:** o servidor fica fora do Brasil, então é preciso avaliar a transferência internacional de dados (LGPD, art. 33) ou escolher uma hospedagem no Brasil, além de contas individuais e 2FA para a equipe.

### Alternativa sem cartão: Render (grátis)

O plano grátis do Render não pede cartão. Limitações: o site "dorme" depois de 15 minutos sem acesso (a primeira visita leva cerca de 1 minuto para acordar), o banco grátis expira em 30 dias e os arquivos enviados (laudos) somem quando o site dorme ou recebe um novo deploy. Use só dados e arquivos fictícios.

O repositório já traz o `Dockerfile`, o `docker/start.sh` e o `render.yaml`. A cada início, o contêiner roda as migrations e o `php artisan nae:preparar-servidor`, que cria os perfis, o administrador e os dados fictícios só na primeira vez.

1. Gere uma chave nova para o servidor (não use a do seu `.env`): `php artisan key:generate --show`.
2. Em render.com, entre com o GitHub e escolha **New → Blueprint**, com o repositório `nae_unibalsas`. O Render lê o `render.yaml` e cria o site e o banco PostgreSQL grátis.
3. Preencha os valores pedidos na tela: `APP_KEY` (a chave do passo 1), `NAE_ADMIN_EMAIL`, `NAE_ADMIN_PASSWORD` e `NAE_DEMO_PASSWORD` (senhas fortes).
4. Aguarde o deploy e abra o endereço `https://nae-unibalsas.onrender.com` (ou o que aparecer no painel).

Sem Blueprint: crie um **PostgreSQL** (plano Free) e um **Web Service** com o repositório (runtime Docker, plano Free) e copie as variáveis do `render.yaml`, usando a _Internal Database URL_ do banco em `DB_URL`.

## Testes e qualidade

```bash
composer test        # config:clear + pint:check + phpstan + pest
npm run check        # formatação e lint do frontend (o GitHub Actions roda os dois)
./vendor/bin/pint     # formatação
./vendor/bin/phpstan analyse
./vendor/bin/pest
```
