<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    };
</script>

<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import BarChart3 from '@lucide/svelte/icons/bar-chart-3';
    import CalendarClock from '@lucide/svelte/icons/calendar-clock';
    import KeyRound from '@lucide/svelte/icons/key-round';
    import Route from '@lucide/svelte/icons/route';
    import ShieldCheck from '@lucide/svelte/icons/shield-check';
    import Users from '@lucide/svelte/icons/users';
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import {
        Card,
        CardContent,
        CardDescription,
        CardHeader,
        CardTitle,
    } from '@/components/ui/card';

    const auth = $derived(page.props.auth);
    const firstName = $derived(auth.user.name.split(' ')[0]);

    const moduleCount = $derived(
        new Set(auth.permissions.map((slug) => slug.split('.')[0])).size,
    );

    const upcoming = [
        {
            title: 'Estudantes',
            description: 'Cadastro e histórico dos estudantes atendidos.',
            icon: Users,
            milestone: 'M3',
        },
        {
            title: 'Triagem e atendimentos',
            description: 'Agendamento e registro dos atendimentos.',
            icon: CalendarClock,
            milestone: 'M4',
        },
        {
            title: 'Encaminhamentos',
            description: 'Encaminhamentos internos e externos ao NAE.',
            icon: Route,
            milestone: 'M6',
        },
        {
            title: 'Relatórios',
            description: 'Indicadores e relatórios gerenciais.',
            icon: BarChart3,
            milestone: 'M8',
        },
    ];
</script>

<AppHead title="Dashboard" />

<div class="flex h-full flex-1 flex-col gap-6 p-4">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">
            Olá, {firstName}
        </h1>
        <p class="text-sm text-muted-foreground">
            Bem-vindo(a) à plataforma do Núcleo de Apoio ao Estudante da
            UNIBALSAS.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Seu perfil</CardTitle>
                <ShieldCheck class="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-semibold">
                    {auth.roles.length}
                </div>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    {#each auth.roles as role (role)}
                        <Badge variant="secondary">{role}</Badge>
                    {:else}
                        <CardDescription>
                            Nenhum perfil atribuído.
                        </CardDescription>
                    {/each}
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium"
                    >Permissões atribuídas</CardTitle
                >
                <KeyRound class="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-semibold">
                    {auth.permissions.length}
                </div>
                <CardDescription class="mt-1">
                    Concedidas pelos seus perfis de acesso.
                </CardDescription>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium"
                    >Módulos com acesso</CardTitle
                >
                <BarChart3 class="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-semibold">
                    {moduleCount}
                </div>
                <CardDescription class="mt-1">
                    Módulos cobertos pelas suas permissões.
                </CardDescription>
            </CardContent>
        </Card>
    </div>

    <Card>
        <CardHeader>
            <CardTitle>Próximos módulos</CardTitle>
            <CardDescription>
                Roadmap da plataforma conforme o SRS do NAE.
            </CardDescription>
        </CardHeader>
        <CardContent class="grid gap-3 sm:grid-cols-2">
            {#each upcoming as item (item.title)}
                <div
                    class="flex items-start gap-3 rounded-lg border border-border p-4"
                >
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-md bg-accent text-accent-foreground"
                    >
                        <item.icon class="size-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium">{item.title}</span>
                            <Badge variant="outline">{item.milestone}</Badge>
                        </div>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            {item.description}
                        </p>
                    </div>
                </div>
            {/each}
        </CardContent>
    </Card>
</div>
