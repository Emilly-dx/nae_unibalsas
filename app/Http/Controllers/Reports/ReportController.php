<?php

namespace App\Http\Controllers\Reports;

use App\Enums\Attendance\AttendanceType;
use App\Enums\Modality;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportFilterRequest;
use App\Models\User;
use App\Support\Courses;
use App\Support\Professionals;
use App\Support\Reports\IndicatorReport;
use App\Support\Reports\ReportFilters;
use Illuminate\Contracts\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Relatórios gerenciais e indicadores (SRS §14, RF-046 a RF-050).
 *
 * Só números agregados: nenhum relatório mostra nomes de estudantes
 * ou o conteúdo sigiloso dos atendimentos.
 */
class ReportController extends Controller
{
    public function index(ReportFilterRequest $request): Response
    {
        $filters = $request->filters();

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('reports/Index', [
            'report' => (new IndicatorReport($filters))->build(),
            'filters' => $filters->toQuery(),
            'options' => [
                'types' => AttendanceType::options(),
                'professionals' => Professionals::options(),
                'courses' => Courses::options(),
                'modalities' => Modality::options(),
            ],
            'can' => [
                'exportPdf' => $user->can('reports.export_pdf'),
                'exportSpreadsheet' => $user->can('reports.export_spreadsheet'),
            ],
        ]);
    }

    /**
     * Versão para impressão: o navegador salva como PDF (RF-049).
     */
    public function pdf(ReportFilterRequest $request): View
    {
        $filters = $request->filters();

        activity('reports')->causedBy($request->user())
            ->event('exported')
            ->withProperties(['format' => 'pdf', 'filters' => $filters->toQuery()])
            ->log('Relatório exportado em PDF');

        return view('reports.print', [
            'report' => (new IndicatorReport($filters))->build(),
            'appliedFilters' => $this->describeFilters($filters),
            'generatedAt' => now()->format('d/m/Y \à\s H:i'),
            'generatedBy' => $request->user()?->name,
        ]);
    }

    /**
     * Planilha em CSV (abre no Excel), separada por ponto e vírgula.
     */
    public function spreadsheet(ReportFilterRequest $request): StreamedResponse
    {
        $filters = $request->filters();
        $report = (new IndicatorReport($filters))->build();

        activity('reports')->causedBy($request->user())
            ->event('exported')
            ->withProperties(['format' => 'csv', 'filters' => $filters->toQuery()])
            ->log('Relatório exportado em planilha');

        $rows = [['Relatório de indicadores do NAE UNIBALSAS'], ['Período', $report['period']]];

        foreach ($this->describeFilters($filters) as $label => $value) {
            $rows[] = [$label, $value];
        }

        $rows[] = [];
        $rows[] = ['Indicador', 'Quantidade'];

        foreach ($report['summary'] as $item) {
            $rows[] = [$item['label'], $item['value']];
        }

        $rows[] = [];
        $rows[] = ['Atendimentos por tipo', 'Quantidade'];

        foreach ($report['byType'] as $item) {
            $rows[] = [$item['label'], $item['value']];
        }

        $rows[] = [];
        $rows[] = ['Mês', ...array_column($report['monthly']['series'], 'label'), 'Total'];

        foreach ($report['monthly']['labels'] as $index => $month) {
            $values = array_map(fn (array $serie): int => $serie['values'][$index], $report['monthly']['series']);
            $rows[] = [$month, ...$values, array_sum($values)];
        }

        $rows[] = [];
        $rows[] = ['Profissional', 'Realizados', 'Psicopedagógico', 'Psicológico', 'Escuta e orientação', 'Cancelados'];

        foreach ($report['byProfessional'] as $item) {
            $rows[] = array_values($item);
        }

        $rows[] = [];
        $rows[] = ['Curso', 'Estudantes atendidos', 'Atendimentos'];

        foreach ($report['byCourse'] as $item) {
            $rows[] = array_values($item);
        }

        $rows[] = [];
        $rows[] = ['Motivo da solicitação', 'Quantidade'];

        foreach ($report['requests']['byReason'] as $item) {
            $rows[] = [$item['label'], $item['value']];
        }

        $rows[] = [];
        $rows[] = ['Origem da solicitação', 'Quantidade'];

        foreach ($report['requests']['byOrigin'] as $item) {
            $rows[] = [$item['label'], $item['value']];
        }

        $rows[] = [];
        $rows[] = ['Situação dos encaminhamentos', 'Quantidade'];

        foreach ($report['referrals']['byStatus'] as $item) {
            $rows[] = [$item['label'], $item['value']];
        }

        $file = 'relatorio-nae-'.$filters->from->format('Y-m-d').'-a-'.$filters->to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF"); // BOM: acentos certos no Excel.

            foreach ($rows as $row) {
                fputcsv($output, array_map(fn (mixed $value): string => $this->safeCell($value), $row), ';', escape: '', eol: "\r\n");
            }

            fclose($output);
        }, $file, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array<string, string>
     */
    private function describeFilters(ReportFilters $filters): array
    {
        $described = [];

        if ($filters->type !== null) {
            $described['Tipo de atendimento'] = $filters->type->label();
        }

        if ($filters->professionalId !== null) {
            $described['Profissional'] = (string) User::query()->whereKey($filters->professionalId)->value('name');
        }

        if ($filters->course !== null) {
            $described['Curso'] = $filters->course;
        }

        if ($filters->modality !== null) {
            $described['Modalidade do curso'] = $filters->modality->label();
        }

        return $described;
    }

    /**
     * Evita que o Excel interprete um texto como fórmula (CSV injection).
     */
    private function safeCell(mixed $value): string
    {
        $text = is_scalar($value) ? (string) $value : '';

        return $text !== '' && ! is_numeric($text) && in_array($text[0], ['=', '+', '-', '@'], true) ? "'".$text : $text;
    }
}
