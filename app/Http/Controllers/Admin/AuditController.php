<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit\AuditPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * Trilha de auditoria (SRS §15.6, RF-051 a RF-054): quem fez o quê e quando.
 */
class AuditController extends Controller
{
    private const int PER_PAGE = 30;

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'modulo' => ['nullable', 'string', 'max:50'],
            'acao' => ['nullable', 'string', 'max:50'],
            'usuario' => ['nullable', 'integer'],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date'],
        ]);

        $filters = [
            'modulo' => (string) ($validated['modulo'] ?? ''),
            'acao' => (string) ($validated['acao'] ?? ''),
            'usuario' => isset($validated['usuario']) ? (string) $validated['usuario'] : '',
            'de' => (string) ($validated['de'] ?? ''),
            'ate' => (string) ($validated['ate'] ?? ''),
        ];

        $activities = Activity::query()
            ->with('causer')
            ->when($filters['modulo'] !== '', fn (Builder $query) => $query->where('log_name', $filters['modulo']))
            ->when($filters['acao'] !== '', fn (Builder $query) => $query->where('event', $filters['acao']))
            ->when($filters['usuario'] !== '', fn (Builder $query) => $query
                ->where('causer_type', (new User)->getMorphClass())
                ->where('causer_id', (int) $filters['usuario']))
            ->when($filters['de'] !== '', fn (Builder $query) => $query->where('created_at', '>=', CarbonImmutable::parse($filters['de'])->startOfDay()))
            ->when($filters['ate'] !== '', fn (Builder $query) => $query->where('created_at', '<=', CarbonImmutable::parse($filters['ate'])->endOfDay()))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Activity $activity): array => AuditPresenter::present($activity));

        return Inertia::render('admin/Audit', [
            'activities' => $activities,
            'filters' => $filters,
            'modules' => AuditPresenter::options(AuditPresenter::MODULES),
            'events' => AuditPresenter::options(AuditPresenter::EVENTS),
            'users' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user): array => ['value' => $user->id, 'label' => $user->name])
                ->values(),
        ]);
    }
}
