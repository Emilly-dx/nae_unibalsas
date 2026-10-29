<script module lang="ts">
    import { index } from '@/routes/students';

    export const layout = {
        breadcrumbs: [{ title: 'Estudantes', href: index() }],
    };
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import ChevronLeft from '@lucide/svelte/icons/chevron-left';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Plus from '@lucide/svelte/icons/plus';
    import Search from '@lucide/svelte/icons/search';
    import Users from '@lucide/svelte/icons/users';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { can } from '@/lib/permissions';
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

<div class="flex h-full flex-1 flex-col gap-6 p-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <Heading
            title="Estudantes"
            description="Estudantes encaminhados e atendidos pelo NAE."
        />

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

    <div class="grid gap-3 rounded-lg border p-4 md:grid-cols-[2fr_1.5fr_1fr_auto] md:items-end">
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
        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full min-w-[640px] text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nome</th>
                        <th class="px-4 py-3 font-medium">Matrícula</th>
                        <th class="px-4 py-3 font-medium">Curso</th>
                        <th class="px-4 py-3 font-medium">Período</th>
                        <th class="px-4 py-3 font-medium">Situação</th>
                        <th class="px-4 py-3"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    {#each students.data as student (student.id)}
                        <tr class="border-t transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-medium">
                                <Link
                                    href={show(student.id).url}
                                    class="hover:underline"
                                >
                                    {student.name}
                                </Link>
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {student.registration}
                            </td>
                            <td class="px-4 py-3">{student.course}</td>
                            <td class="px-4 py-3">{student.period}º</td>
                            <td class="px-4 py-3">
                                {#if student.active}
                                    <Badge variant="secondary">Ativo</Badge>
                                {:else}
                                    <Badge variant="outline">Inativo</Badge>
                                {/if}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button variant="ghost" size="sm" asChild>
                                    {#snippet children(props)}
                                        <Link {...props} href={show(student.id).url}>
                                            Ver
                                        </Link>
                                    {/snippet}
                                </Button>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
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
