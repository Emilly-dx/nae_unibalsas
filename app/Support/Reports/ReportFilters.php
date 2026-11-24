<?php

namespace App\Support\Reports;

use App\Enums\Attendance\AttendanceType;
use App\Enums\Modality;
use Carbon\CarbonImmutable;

/**
 * Filtros dos relatórios gerenciais (RF-048).
 */
final readonly class ReportFilters
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public ?AttendanceType $type = null,
        public ?int $professionalId = null,
        public ?string $course = null,
        public ?Modality $modality = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input  dados já validados (de, ate, tipo, profissional, curso, modalidade)
     */
    public static function fromArray(array $input): self
    {
        $from = is_string($input['de'] ?? null) && $input['de'] !== ''
            ? CarbonImmutable::parse($input['de'])->startOfDay()
            : CarbonImmutable::now()->startOfYear();

        $to = is_string($input['ate'] ?? null) && $input['ate'] !== ''
            ? CarbonImmutable::parse($input['ate'])->endOfDay()
            : $from->endOfYear();

        return new self(
            from: $from,
            to: $to,
            type: is_string($input['tipo'] ?? null) ? AttendanceType::tryFrom($input['tipo']) : null,
            professionalId: is_numeric($input['profissional'] ?? null) ? (int) $input['profissional'] : null,
            course: is_string($input['curso'] ?? null) && $input['curso'] !== '' ? $input['curso'] : null,
            modality: is_string($input['modalidade'] ?? null) ? Modality::tryFrom($input['modalidade']) : null,
        );
    }

    /**
     * Valores para a query string e para o formulário de filtros.
     *
     * @return array{de: string, ate: string, tipo: string, profissional: string, curso: string, modalidade: string}
     */
    public function toQuery(): array
    {
        return [
            'de' => $this->from->toDateString(),
            'ate' => $this->to->toDateString(),
            'tipo' => $this->type->value ?? '',
            'profissional' => $this->professionalId === null ? '' : (string) $this->professionalId,
            'curso' => $this->course ?? '',
            'modalidade' => $this->modality->value ?? '',
        ];
    }

    public function filtersStudents(): bool
    {
        return $this->course !== null || $this->modality !== null;
    }

    public function periodLabel(): string
    {
        return $this->from->format('d/m/Y').' a '.$this->to->format('d/m/Y');
    }
}
