<?php

namespace App\Http\Controllers\Referrals;

use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Referral\ReferralKind;
use App\Enums\Referral\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referrals\StoreReferralRequest;
use App\Http\Requests\Referrals\UpdateReferralStatusRequest;
use App\Models\Attendance;
use App\Models\Referral;
use App\Models\Student;
use App\Models\User;
use App\Support\Professionals;
use App\Support\ReferralPresenter;
use App\Support\StudentOptions;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Encaminhamentos internos e externos (SRS §12, RF-034 a RF-040).
 */
class ReferralController extends Controller
{
    /** Sugestões de destino externo (lista a validar com o NAE). */
    private const array EXTERNAL_SUGGESTIONS = [
        'Clínica UNIBALSAS',
        'Coordenação do curso',
        'CIA — Comitê de Inclusão e Acessibilidade',
        'Rede pública de saúde (UBS)',
        'CAPS',
    ];

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $kind = $request->string('tipo')->toString();
        $kind = ReferralKind::tryFrom($kind)->value ?? '';
        $status = $request->string('situacao')->toString();
        $status = $status === 'abertos' ? 'abertos' : (ReferralStatus::tryFrom($status)->value ?? '');
        $mine = $request->boolean('meus');
        $search = $request->string('busca')->trim()->toString();

        $referrals = Referral::query()
            ->with(['student', 'author', 'destinationUser'])
            ->when($kind !== '', fn (Builder $query) => $query->where('kind', $kind))
            ->when($status === 'abertos', fn (Builder $query) => $query->open())
            ->when($status !== '' && $status !== 'abertos', fn (Builder $query) => $query->where('status', $status))
            ->when($mine, fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->where('created_by', $user->id)->orWhere('destination_user_id', $user->id),
            ))
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'student',
                fn (Builder $query) => $query->withTrashed()->search($search),
            ))
            ->latest('referred_on')->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Referral $referral): array => ReferralPresenter::summary($referral));

        return Inertia::render('referrals/Index', [
            'referrals' => $referrals,
            'filters' => ['tipo' => $kind, 'situacao' => $status, 'meus' => $mine, 'busca' => $search],
            'kinds' => ReferralKind::options(),
            'statuses' => ReferralStatus::options(),
            'counts' => [
                'open' => Referral::query()->open()->count(),
                'awaitingMe' => Referral::query()->open()->where('destination_user_id', $user->id)->count(),
                'overdue' => Referral::query()->open()->whereDate('return_deadline', '<', CarbonImmutable::today())->count(),
            ],
            'canCreate' => $user->can('referrals.create'),
        ]);
    }

    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $attendance = null;

        if ($request->integer('atendimento') > 0) {
            $attendance = Attendance::query()->with('student')->find($request->integer('atendimento'));
        }

        return Inertia::render('referrals/Create', [
            'students' => StudentOptions::all(),
            'professionals' => Professionals::options()->reject(fn (array $option): bool => $option['value'] === $user->id)->values(),
            'externalSuggestions' => self::EXTERNAL_SUGGESTIONS,
            'kinds' => ReferralKind::options(),
            'selectedStudent' => $attendance->student_id ?? ($request->integer('estudante') ?: null),
            'attendance' => $attendance === null ? null : [
                'id' => $attendance->id,
                'label' => $attendance->scheduled_at->format('d/m/Y').' · '.$attendance->type->label(),
            ],
        ]);
    }

    public function store(StoreReferralRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $referral = DB::transaction(function () use ($data, $user): Referral {
            $referral = new Referral([
                'kind' => ReferralKind::from((string) $data['kind']),
                'destination_name' => $data['destination_name'] ?? null,
                'reason' => (string) $data['reason'],
                'referred_on' => CarbonImmutable::parse((string) $data['referred_on']),
                'return_deadline' => isset($data['return_deadline']) ? CarbonImmutable::parse((string) $data['return_deadline']) : null,
            ]);
            $referral->student()->associate(Student::query()->findOrFail((int) $data['student_id']));
            $referral->attendance_id = isset($data['attendance_id']) ? (int) $data['attendance_id'] : null;
            $referral->destination_user_id = isset($data['destination_user_id']) ? (int) $data['destination_user_id'] : null;
            $referral->created_by = $user->id;
            $referral->status = ReferralStatus::Sent;
            $referral->save();

            // O atendimento que gerou o encaminhamento passa a "Encaminhado" (SRS §10.5).
            $attendance = $referral->attendance;

            if ($attendance !== null && $attendance->status === AttendanceStatus::Attended) {
                $attendance->status = AttendanceStatus::Referred;
                $attendance->save();
            }

            return $referral;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Encaminhamento registrado.']);

        return to_route('referrals.show', $referral);
    }

    public function show(Request $request, Referral $referral): Response
    {
        /** @var User $user */
        $user = $request->user();
        $referral->load(['student', 'author', 'destinationUser', 'attendance']);
        $canViewContent = Gate::allows('viewContent', $referral);

        return Inertia::render('referrals/Show', [
            'referral' => [
                ...ReferralPresenter::summary($referral),
                'returned_at' => $referral->returned_at?->format('d/m/Y H:i'),
                'attendance_label' => $referral->attendance === null ? null
                    : $referral->attendance->scheduled_at->format('d/m/Y').' · '.$referral->attendance->type->label(),
            ],
            // Motivo e retorno: só para quem encaminhou e, no interno, para o destino (RN-012).
            'content' => $canViewContent ? [
                'reason' => $referral->reason,
                'return_notes' => $referral->return_notes,
            ] : null,
            'statusOptions' => array_values(array_filter(
                ReferralStatus::options(),
                fn (array $option): bool => in_array($option['value'], ['in_progress', 'awaiting_return', 'completed'], true),
            )),
            'can' => [
                'update' => Gate::allows('update', $referral),
                'cancel' => Gate::allows('cancel', $referral),
                'reopen' => Gate::allows('reopen', $referral),
                'viewStudent' => $user->can('students.view'),
                'viewAttendance' => $user->can('attendances.view'),
            ],
        ]);
    }

    public function updateStatus(UpdateReferralStatusRequest $request, Referral $referral): RedirectResponse
    {
        Gate::authorize('update', $referral);

        $status = ReferralStatus::from($request->string('status')->toString());
        $notes = $request->string('return_notes')->trim()->toString();

        $referral->status = $status;

        if ($notes !== '') {
            $referral->return_notes = $notes;
            $referral->returned_at = now()->toImmutable();
        }

        $referral->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Encaminhamento atualizado.']);

        return to_route('referrals.show', $referral);
    }

    public function cancel(Referral $referral): RedirectResponse
    {
        Gate::authorize('cancel', $referral);

        $referral->status = ReferralStatus::Cancelled;
        $referral->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Encaminhamento cancelado.']);

        return to_route('referrals.show', $referral);
    }

    public function reopen(Referral $referral): RedirectResponse
    {
        Gate::authorize('reopen', $referral);

        $referral->status = ReferralStatus::Sent;
        $referral->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Encaminhamento reaberto.']);

        return to_route('referrals.show', $referral);
    }
}
