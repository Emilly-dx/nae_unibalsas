<script module lang="ts">
    import { edit as editAppearance } from '@/routes/appearance';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Aparência',
                href: editAppearance(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppearanceTabs from '@/components/AppearanceTabs.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
</script>

<AppHead title="Aparência" />

<h1 class="sr-only">Aparência</h1>

<div class="space-y-6">
    <Heading
        variant="small"
        title="Aparência"
        description="Escolha o tema claro, escuro ou o do sistema"
    />
    <AppearanceTabs />
</div>
