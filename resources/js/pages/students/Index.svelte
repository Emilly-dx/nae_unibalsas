<script module lang="ts">
    import { index } from '@/routes/students';

    export const layout = {
        breadcrumbs: [
            { title: 'Painel', href: '/dashboard' },
            { title: 'Estudantes', href: index() },
        ],
    };
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Download from '@lucide/svelte/icons/download';
    import Plus from '@lucide/svelte/icons/plus';
    import Search from '@lucide/svelte/icons/search';
    import Users from '@lucide/svelte/icons/users';
    import { toast } from 'svelte-sonner';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { getInitials } from '@/lib/initials';
    import { can } from '@/lib/permissions';
    import { cn } from '@/lib/utils';
    import { create, show } from '@/routes/students';
    import type { Paginated, StudentListItem } from '@/types';

    type Filters = {
        search: string;
        course: string;
        status: 'active' | 'inactive' | 'all';
    };

    let {
        students,
        filters,
        courses,
    }: {
        students: Paginated<StudentListItem>;
        filters: Filters;
        courses: string[];
    } = $props();

    // Estado local dos filtros, inicializado a partir do servidor.
    // svelte-ignore state_referenced_locally
    let search = $state(filters.search);
    // svelte-ignore state_referenced_locally
    let course = $state(filters.course);
    // svelte-ignore state_referenced_locally
    let status = $state<Filters['status']>(filters.status);

    const canCreate = $derived(can(page.props.auth, 'students.create'));
    const demo = $derived(page.props.demoScreens);

    let selectedId = $state<number | null>(null);
    const selected = $derived(
        students.data.find((student) => student.id === selectedId) ?? students.data[0] ?? null,
    );
    const hasFilters = $derived(
        search !== '' || course !== '' || status !== 'active',
    );

    let debounce: ReturnType<typeof setTimeout> | undefined;

    function applyFilters(): void {
        clearTimeout(debounce);

        const query: Record<string, string> = {};

        if (search.trim() !== '') {
            query.search = search.trim();
        }

        if (course !== '') {
            query.course = course;
        }

        if (status !== 'active') {
            query.status = status;
        }

        router.get(index().url, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['students', 'filters'],
        });
    }

    function onSearchInput(): void {
        clearTimeout(debounce);
        debounce = setTimeout(applyFilters, 350);
    }

    function clearFilters(): void {
        search = '';
        course = '';
        status = 'active';
        applyFilters();
    }

    const selectClass =
        'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
</script>

<AppHead title="Estudantes" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <Heading
            title="Estudantes"
            description="Cadastro e histórico dos estudantes atendidos pelo NAE."
        />

        <div class="flex flex-wrap gap-2">
        <Button variant="outline" onclick={() => toast.info('A exportação será implementada no módulo de relatórios (M8).')}>
            <Download class="size-4" />
            Exportar
        </Button>
        {#if canCreate}
            <Button asChild>
                {#snippet children(props)}
                    <Link {...props} href={create().url}>
                        <Plus class="size-4" />
                        Novo estudante
                    </Link>
                {/snippet}
            </Button>
        {/if}
        </div>
    </div>

    <div class="grid gap-3 rounded-xl border bg-card p-4 md:grid-cols-[2fr_1.5fr_1fr_auto] md:items-end">
        <div class="grid gap-2">
            <Label for="search">Pesquisar</Label>
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    id="search"
                    type="search"
                    class="pl-9"
                    placeholder="Nome ou matrícula"
                    bind:value={search}
                    oninput={onSearchInput}
                />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="course">Curso</Label>
            <select
                id="course"
                class={selectClass}
                bind:value={course}
                onchange={applyFilters}
            >
                <option value="">Todos os cursos</option>
                {#each courses as option (option)}
                    <option value={option}>{option}</option>
                {/each}
            </select>
        </div>

        <div class="grid gap-2">
            <Label for="status">Situação</Label>
            <select
                id="status"
                class={selectClass}
                bind:value={status}
                onchange={applyFilters}
            >
                <option value="active">Ativos</option>
                <option value="inactive">Inativos</option>
                <option value="all">Todos</option>
            </select>
        </div>

        <Button variant="ghost" disabled={!hasFilters} onclick={clearFilters}>
            Limpar filtros
        </Button>
    </div>

    {#if students.data.length === 0}
        <div
            class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed p-10 text-center"
        >
            <Users class="size-8 text-muted-foreground" />
            <div>
                <p class="font-medium">Nenhum estudante encontrado</p>
                <p class="text-sm text-muted-foreground">
                    {hasFilters
                        ? 'Tente ajustar a pesquisa ou os filtros.'
                        : 'Os estudantes cadastrados aparecerão aqui.'}
                </p>
            </div>
        </div>
    {:else}
        <div class="grid gap-6 xl:grid-cols-[1.8fr_1fr]">
        <div class="overflow-hidden rounded-xl border bg-card">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-5 py-3 font-medium">Estudante</th>
                        <th class="px-4 py-3 font-medium">Curso / período</th>
                        <th class="px-4 py-3 font-medium">Modalidade</th>
                        <th class="px-4 py-3 font-medium">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    {#each students.data as student (student.id)}
                        <tr
                            class={cn(
                                'cursor-pointer border-t transition-colors hover:bg-muted/40',
                                selected?.id === student.id && 'bg-muted/60',
                            )}
                            onclick={() => (selectedId = student.id)}
                        >
                            <td class="px-5 py-3">
                                <button type="button" class="text-left font-medium hover:underline" onclick={() => (selectedId = student.id)}>
                                    {student.social_name || student.name}
                                </button>
                                <p class="font-mono text-xs text-muted-foreground">{student.registration}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p>{student.course}</p>
                                <p class="text-xs text-muted-foreground">{student.period}º período</p>
                            </td>
                            <td class="px-4 py-3">
                                <p>{student.modality_label}</p>
                                {#if student.shift_label}<p class="text-xs text-muted-foreground">{student.shift_label}</p>{/if}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    {#if student.active}
                                        <Badge variant="secondary">Ativo</Badge>
                                    {:else}
                                        <Badge variant="outline">Inativo</Badge>
                                    {/if}
                                    {#if student.needs_accessibility}
                                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300">Acessibilidade</span>
                                    {/if}
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
        </div>

        {#if selected}
            <aside class="flex flex-col gap-4 rounded-xl border bg-card p-5 xl:sticky xl:top-4 xl:self-start">
                <div class="flex items-center gap-3">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-accent font-semibold text-accent-foreground">
                        {getInitials(selected.social_name || selected.name)}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{selected.social_name || selected.name}</p>
                        <p class="truncate text-xs text-muted-foreground">{selected.course} · {selected.period}º período</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    {#if selected.active}<Badge variant="secondary">Ativo</Badge>{:else}<Badge variant="outline">Inativo</Badge>{/if}
                    <Badge variant="outline">{selected.modality_label}</Badge>
                </div>
                <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
                    {#if selected.social_name}<dt class="text-muted-foreground">Nome civil</dt><dd>{selected.name}</dd>{/if}
                    <dt class="text-muted-foreground">Matrícula</dt><dd class="font-mono">{selected.registration}</dd>
                    <dt class="text-muted-foreground">E-mail</dt><dd class="truncate">{selected.email || '—'}</dd>
                    <dt class="text-muted-foreground">Telefone</dt><dd>{selected.phone || '—'}</dd>
                    <dt class="text-muted-foreground">Turno</dt><dd>{selected.shift_label || '—'}</dd>
                </dl>
                {#if selected.accessibility_resources.length > 0}
                    <div>
                        <p class="mb-1.5 text-sm font-medium">Recursos de acessibilidade</p>
                        <div class="flex flex-wrap gap-1.5">
                            {#each selected.accessibility_resources as resource (resource)}
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700 dark:bg-blue-950 dark:text-blue-300">{resource}</span>
                            {/each}
                        </div>
                    </div>
                {/if}
                <div class="border-t pt-4">
                    <p class="mb-2 text-sm font-semibold">Histórico</p>
                    <p class="text-sm text-muted-foreground">
                        Solicitações, triagem e atendimentos aparecerão aqui com o módulo M4.
                    </p>
                </div>
                <div class="mt-2 flex flex-col gap-2">
                    {#if demo && selected.active}
                        <Button asChild>
                            {#snippet children(props)}
                                <Link {...props} href="/atendimentos/registrar">Registrar atendimento</Link>
                            {/snippet}
                        </Button>
                        <Button variant="outline" asChild>
                            {#snippet children(props)}<Link {...props} href="/encaminhamentos">Novo encaminhamento</Link>{/snippet}
                        </Button>
                    {/if}
                    <Button variant="ghost" asChild>
                        {#snippet children(props)}<Link {...props} href={show(selected.id).url}>Ver cadastro completo</Link>{/snippet}
                    </Button>
                </div>
            </aside>
        {/if}
        </div>

        <div class="flex flex-col items-center justify-between gap-3 text-sm sm:flex-row">
            <p class="text-muted-foreground">
                Mostrando {students.from}–{students.to} de {students.total}
                {students.total === 1 ? 'estudante' : 'estudantes'}
            </p>

            {#if students.last_page > 1}
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!students.prev_page_url}
                        onclick={() =>
                            students.prev_page_url &&
                            router.get(students.prev_page_url, {}, { preserveScroll: true })}
                    >
                        <ChevronLeft class="size-4" />
                        Anterior
                    </Button>
                    <span class="text-muted-foreground">
                        Página {students.current_page} de {students.last_page}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!students.next_page_url}
                        onclick={() =>
                            students.next_page_url &&
                            router.get(students.next_page_url, {}, { preserveScroll: true })}
                    >
                        Próxima
                        <ChevronRight class="size-4" />
                    </Button>
                </div>
            {/if}
        </div>
    {/if}
</div>
