<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: dashboard() },
            { title: 'Visão geral', href: dashboard() },
        ],
    };
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import CalendarDays from '@lucide/svelte/icons/calendar-days';
    import ChartColumn from '@lucide/svelte/icons/chart-column';
    import FileText from '@lucide/svelte/icons/file-text';
    import Inbox from '@lucide/svelte/icons/inbox';
    import Plus from '@lucide/svelte/icons/plus';
    import Route from '@lucide/svelte/icons/route';
    import ShieldCheck from '@lucide/svelte/icons/shield-check';
    import Users from '@lucide/svelte/icons/users';
    import AppHead from '@/components/AppHead.svelte';
    import DemoNotice from '@/components/demo/DemoNotice.svelte';
    import StatCard from '@/components/demo/StatCard.svelte';
    import { Button } from '@/components/ui/button';
    import {
        attendanceChipStyles,
        attendanceTypeLabels,
        demoStudent,
        todayAgenda,
        triageQueue,
    } from '@/lib/demo/data';
    import { can } from '@/lib/permissions';
    import { cn } from '@/lib/utils';
    import { create as createStudent } from '@/routes/students';

    const roleLabels: Record<string, string> = {
        administrator: 'Administrador',
        coordinator: 'Coordenação do NAE',
        psychopedagogue: 'Psicopedagogia',
        psychologist: 'Psicologia',
        pedagogue: 'Pedagogia',
        assistant: 'Assistente',
    };

    const auth = $derived(page.props.auth);
    const demo = $derived(page.props.demoScreens);
    const firstName = $derived(auth.user.name.split(' ')[0]);
    const isAdmin = $derived(auth.roles.includes('administrator'));
    const profile = $derived(
        auth.roles.map((role) => roleLabels[role] ?? role).join(', ') ||
            'Sem perfil atribuído',
    );
    const moduleCount = $derived(
        new Set(auth.permissions.map((slug) => slug.split('.')[0])).size,
    );

    const today = new Intl.DateTimeFormat('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    }).format(new Date());

    const shortcuts = $derived(
        [
            {
                title: 'Novo estudante',
                href: createStudent().url,
                icon: Plus,
                show: can(auth, 'students.create'),
            },
            { title: 'Nova solicitação', href: '/triagem/nova-solicitacao', icon: Inbox, show: demo },
            { title: 'Novo encaminhamento', href: '/encaminhamentos', icon: Route, show: demo },
            { title: 'Relatórios', href: '/relatorios', icon: ChartColumn, show: demo },
        ].filter((shortcut) => shortcut.show),
    );
</script>

<AppHead title="Painel" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Olá, {firstName}</h1>
            <p class="text-sm text-muted-foreground">
                Bem-vindo(a) à plataforma do Núcleo de Apoio ao Estudante da
                UNIBALSAS. Hoje é {today}.
            </p>
        </div>
        {#if demo}
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" asChild>
                    {#snippet children(props)}
                        <Link {...props} href="/triagem/nova-solicitacao">
                            <Plus class="size-4" /> Nova solicitação
                        </Link>
                    {/snippet}
                </Button>
                <Button asChild>
                    {#snippet children(props)}
                        <Link {...props} href="/atendimentos/registrar">
                            <FileText class="size-4" /> Registrar atendimento
                        </Link>
                    {/snippet}
                </Button>
            </div>
        {/if}
    </div>

    {#if demo}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard title="Atendimentos hoje" value={todayAgenda.length} hint="Na agenda do dia" icon={CalendarDays} />
            <StatCard title="Fila de triagem" value={triageQueue.length} hint="Solicitações aguardando análise" icon={Inbox} />
            <StatCard title="Encaminhamentos" value={2} hint="Aguardando retorno" icon={Route} />
            <StatCard title="Em acompanhamento" value={38} hint="Estudantes no semestre" icon={Users} />
        </div>
    {/if}

    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        {#if demo}
            <section class="overflow-hidden rounded-xl border bg-card">
                <header class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-semibold">Agenda de hoje</h2>
                    <Link href="/triagem?aba=agenda" class="text-sm text-primary underline-offset-4 hover:underline">
                        Ver agenda da semana
                    </Link>
                </header>
                <ul class="divide-y">
                    {#each todayAgenda as item (item.time)}
                        {@const student = demoStudent(item.student)}
                        <li class="flex items-center gap-4 px-5 py-3">
                            <span class="w-14 font-mono text-sm font-semibold">{item.time}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{student.name}</p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {student.course} · {student.period}º período
                                </p>
                            </div>
                            <span class={cn('hidden rounded-full px-2.5 py-0.5 text-xs font-medium sm:inline', attendanceChipStyles[item.type])}>
                                {attendanceTypeLabels[item.type]}
                            </span>
                            <Button variant="outline" size="sm" asChild>
                                {#snippet children(props)}
                                    <Link {...props} href={`/atendimentos/registrar?estudante=${encodeURIComponent(student.name)}`}>
                                        Registrar
                                    </Link>
                                {/snippet}
                            </Button>
                        </li>
                    {/each}
                </ul>
                <div class="border-t p-4"><DemoNotice milestone="M4" /></div>
            </section>
        {/if}

        <div class="flex flex-col gap-6">
            {#if shortcuts.length > 0}
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="mb-4 font-semibold">Atalhos</h2>
                    <div class="grid grid-cols-2 gap-3">
                        {#each shortcuts as shortcut (shortcut.title)}
                            <Link
                                href={shortcut.href}
                                class="flex items-center gap-3 rounded-lg border p-3 text-sm font-medium transition-colors hover:bg-accent/50"
                            >
                                <span class="rounded-md bg-muted p-1.5 text-primary">
                                    <shortcut.icon class="size-4" />
                                </span>
                                {shortcut.title}
                            </Link>
                        {/each}
                    </div>
                </section>
            {/if}

            <section class="rounded-xl border bg-card p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold">Seu acesso</h2>
                    <ShieldCheck class="size-4 text-primary" />
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Perfil</dt>
                        <dd class="text-right font-medium">{profile}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Permissões concedidas</dt>
                        <dd class="font-mono">{isAdmin ? 'Acesso total' : auth.permissions.length}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Módulos com acesso</dt>
                        <dd class="font-mono">{isAdmin ? 'Todos' : moduleCount}</dd>
                    </div>
                </dl>
                <p class="mt-4 text-xs text-muted-foreground">
                    Dados do perfil e permissões vindos do módulo M1 (Core + ACL).
                </p>
            </section>
        </div>
    </div>
</div>
