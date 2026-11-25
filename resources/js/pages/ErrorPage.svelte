<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { Button } from '@/components/ui/button';

    let { status }: { status: number } = $props();

    const messages: Record<number, { title: string; description: string }> = {
        403: {
            title: 'Acesso não permitido',
            description: 'O seu perfil não tem permissão para abrir esta página. Se precisar desse acesso, fale com a coordenação do NAE.',
        },
        404: {
            title: 'Página não encontrada',
            description: 'O endereço pode estar errado, ou o registro não existe mais.',
        },
        500: {
            title: 'Algo deu errado',
            description: 'Tivemos um problema ao abrir esta página. Tente de novo em alguns instantes.',
        },
        503: {
            title: 'Sistema em manutenção',
            description: 'A plataforma volta em alguns minutos.',
        },
    };

    const message = $derived(messages[status] ?? messages[500]);
</script>

<AppHead title={message.title} />

<div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 text-center">
    <AppLogoIcon class="size-10 text-primary" />
    <div class="space-y-2">
        <p class="font-mono text-sm text-muted-foreground">Erro {status}</p>
        <h1 class="text-2xl font-semibold tracking-tight">{message.title}</h1>
        <p class="mx-auto max-w-md text-sm text-muted-foreground">{message.description}</p>
    </div>
    <div class="flex flex-wrap justify-center gap-2">
        <Button variant="outline" onclick={() => history.back()}>Voltar</Button>
        <Button asChild>
            {#snippet children(props)}<a {...props} href="/dashboard">Ir para o painel</a>{/snippet}
        </Button>
    </div>
</div>
