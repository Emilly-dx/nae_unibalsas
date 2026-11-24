<!DOCTYPE html>
<html lang="pt-BR" translate="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Relatório NAE UNIBALSAS — {{ $report['period'] }}</title>
    @php
        $colors = ['psychopedagogical' => '#c8163a', 'psychological' => '#2563eb', 'listening' => '#b45309'];
        $monthTotals = [];
        foreach ($report['monthly']['labels'] as $index => $label) {
            $monthTotals[$index] = array_sum(array_map(fn ($serie) => $serie['values'][$index], $report['monthly']['series']));
        }
        $maxMonth = max(1, ...array_values($monthTotals ?: [0]));
    @endphp
    <style>
        @page { size: A4; margin: 16mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, Helvetica, sans-serif; color: #1f1f23; font-size: 11pt; margin: 0; background: #fff; }
        .page { max-width: 190mm; margin: 0 auto; padding: 16px; }
        .toolbar { display: flex; gap: 8px; justify-content: flex-end; padding: 12px 16px; background: #f4f4f5; border-bottom: 1px solid #e4e4e7; }
        .toolbar button { font: inherit; padding: 8px 14px; border-radius: 6px; border: 1px solid #d4d4d8; background: #fff; cursor: pointer; }
        .toolbar button.primary { background: #c8163a; border-color: #c8163a; color: #fff; }
        header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 3px solid #c8163a; padding-bottom: 10px; margin-bottom: 16px; }
        h1 { font-size: 18pt; margin: 0; }
        h2 { font-size: 12pt; margin: 22px 0 8px; color: #c8163a; break-after: avoid; }
        .muted { color: #6b6b73; font-size: 9.5pt; }
        .filters { margin: 0 0 12px; font-size: 9.5pt; }
        .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .card { border: 1px solid #e4e4e7; border-radius: 8px; padding: 10px 12px; break-inside: avoid; }
        .card .value { font-size: 18pt; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; font-size: 10pt; break-inside: auto; }
        th, td { text-align: left; padding: 5px 8px; border-bottom: 1px solid #e4e4e7; }
        th { background: #f4f4f5; font-weight: 600; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
        tr { break-inside: avoid; }
        .chart { display: flex; align-items: flex-end; gap: 6px; height: 150px; border-bottom: 1px solid #a1a1aa; padding-top: 14px; }
        .bar { flex: 1; display: flex; flex-direction: column-reverse; position: relative; }
        .bar span.total { position: absolute; top: -14px; width: 100%; text-align: center; font-size: 8pt; color: #52525b; }
        .labels { display: flex; gap: 6px; margin-top: 4px; }
        .labels span { flex: 1; text-align: center; font-size: 8pt; color: #6b6b73; }
        .legend { display: flex; gap: 14px; font-size: 9pt; margin: 6px 0; }
        .legend i { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 4px; vertical-align: -1px; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #e4e4e7; font-size: 8.5pt; color: #6b6b73; }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @media print { .toolbar { display: none; } .page { padding: 0; } }
    </style>
</head>
<body>
<div class="toolbar">
    <span class="muted" style="margin-right:auto;align-self:center">Na janela de impressão, escolha <strong>Salvar como PDF</strong>.</span>
    <button type="button" onclick="window.close()">Fechar</button>
    <button type="button" class="primary" onclick="window.print()">Imprimir / salvar PDF</button>
</div>
<div class="page">
    <header>
        <div>
            <h1>Relatório de indicadores</h1>
            <div class="muted">Núcleo de Apoio ao Estudante — UNIBALSAS</div>
        </div>
        <div style="text-align:right">
            <strong>{{ $report['period'] }}</strong>
            <div class="muted">Gerado em {{ $generatedAt }}{{ $generatedBy ? ' por '.$generatedBy : '' }}</div>
        </div>
    </header>

    @if (count($appliedFilters) > 0)
        <p class="filters"><strong>Filtros:</strong>
            @foreach ($appliedFilters as $label => $value){{ $label }}: {{ $value }}@if (! $loop->last) · @endif @endforeach
        </p>
    @endif

    <h2>Resumo do período</h2>
    <div class="cards">
        @foreach ($report['summary'] as $item)
            <div class="card">
                <div class="muted">{{ $item['label'] }}</div>
                <div class="value">{{ number_format($item['value'], 0, ',', '.') }}</div>
            </div>
        @endforeach
    </div>

    <h2>Atendimentos realizados por mês e tipo</h2>
    <div class="legend">
        @foreach ($report['monthly']['series'] as $serie)
            <span><i style="background: {{ $colors[$serie['key']] ?? '#71717a' }}"></i>{{ $serie['label'] }}</span>
        @endforeach
    </div>
    <div class="chart">
        @foreach ($report['monthly']['labels'] as $index => $label)
            <div class="bar" style="height: {{ round($monthTotals[$index] / $maxMonth * 100) }}%">
                @if ($monthTotals[$index] > 0)<span class="total">{{ $monthTotals[$index] }}</span>@endif
                @foreach ($report['monthly']['series'] as $serie)
                    @if ($monthTotals[$index] > 0)
                        <div style="height: {{ $serie['values'][$index] / $monthTotals[$index] * 100 }}%; background: {{ $colors[$serie['key']] ?? '#71717a' }}"></div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
    <div class="labels">
        @foreach ($report['monthly']['labels'] as $label)<span>{{ $label }}</span>@endforeach
    </div>

    <div class="two">
        <div>
            <h2>Atendimentos por tipo</h2>
            <table>
                @foreach ($report['byType'] as $item)
                    <tr><td>{{ $item['label'] }}</td><td class="num">{{ $item['value'] }}</td></tr>
                @endforeach
                @foreach ($report['byModality'] as $item)
                    <tr><td>Modalidade: {{ $item['label'] }}</td><td class="num">{{ $item['value'] }}</td></tr>
                @endforeach
            </table>
        </div>
        <div>
            <h2>Encaminhamentos</h2>
            <table>
                <tr><td>Internos</td><td class="num">{{ $report['referrals']['internal'] }}</td></tr>
                <tr><td>Externos</td><td class="num">{{ $report['referrals']['external'] }}</td></tr>
                @foreach ($report['referrals']['byStatus'] as $item)
                    <tr><td>Situação: {{ $item['label'] }}</td><td class="num">{{ $item['value'] }}</td></tr>
                @endforeach
            </table>
        </div>
    </div>

    <h2>Atendimentos por profissional</h2>
    <table>
        <thead><tr><th>Profissional</th><th class="num">Realizados</th><th class="num">Psicoped.</th><th class="num">Psicológ.</th><th class="num">Escuta</th><th class="num">Cancelados</th></tr></thead>
        <tbody>
        @forelse ($report['byProfessional'] as $item)
            <tr><td>{{ $item['name'] }}</td><td class="num">{{ $item['total'] }}</td><td class="num">{{ $item['psychopedagogical'] }}</td><td class="num">{{ $item['psychological'] }}</td><td class="num">{{ $item['listening'] }}</td><td class="num">{{ $item['cancelled'] }}</td></tr>
        @empty
            <tr><td colspan="6" class="muted">Sem atendimentos no período.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="two">
        <div>
            <h2>Atendimentos por curso</h2>
            <table>
                <thead><tr><th>Curso</th><th class="num">Estudantes</th><th class="num">Atend.</th></tr></thead>
                <tbody>
                @forelse ($report['byCourse'] as $item)
                    <tr><td>{{ $item['course'] }}</td><td class="num">{{ $item['students'] }}</td><td class="num">{{ $item['attendances'] }}</td></tr>
                @empty
                    <tr><td colspan="3" class="muted">Sem atendimentos no período.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div>
            <h2>Demandas das solicitações ({{ $report['requests']['total'] }})</h2>
            <table>
                @foreach ($report['requests']['byReason'] as $item)
                    <tr><td>{{ $item['label'] }}</td><td class="num">{{ $item['value'] }}</td></tr>
                @endforeach
            </table>
            <p class="muted">Uma solicitação pode ter mais de um motivo.</p>
        </div>
    </div>

    <footer>
        Relatório com números agregados. Não contém nomes de estudantes nem o conteúdo dos atendimentos, que é sigiloso (LGPD).
    </footer>
</div>
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body>
</html>
