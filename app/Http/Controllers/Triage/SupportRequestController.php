<?php

namespace App\Http\Controllers\Triage;

use App\Actions\Attendances\ScheduleAttendance;
use App\Enums\Attendance\AttendanceModality;
use App\Enums\Attendance\AttendanceType;
use App\Enums\Triage\RequestOrigin;
use App\Enums\Triage\RequestPriority;
use App\Enums\Triage\RequestReason;
use App\Enums\Triage\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Triage\ReturnSupportRequestRequest;
use App\Http\Requests\Triage\StoreSupportRequestRequest;
use App\Http\Requests\Triage\TriageScheduleRequest;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\SupportRequest;
use App\Models\User;
use App\Support\AttendancePresenter;
use App\Support\Professionals;
use App\Support\StudentOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Solicitações de apoio e decisão da triagem (RF-019 a RF-021, RN-001, RN-002).
 */
class SupportRequestController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('triage/requests/Create', [
            'students' => StudentOptions::all(),
            'origins' => RequestOrigin::options(),
            'reasons' => RequestReason::options(),
            'selectedStudent' => $request->integer('estudante') ?: null,
        ]);
    }

    public function store(StoreSupportRequestRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $supportRequest = new SupportRequest($request->safe()->except('student_id'));
        $supportRequest->student()->associate(Student::query()->findOrFail($request->integer('student_id')));
        $supportRequest->created_by = $user->id;
        $supportRequest->status = RequestStatus::PendingTriage;
        $supportRequest->priority = RequestPriority::Normal;
        $supportRequest->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Solicitação registrada na fila de triagem.']);

        return $user->can('triage.perform')
            ? to_route('triage.requests.show', $supportRequest)
            : to_route('triage.index');
    }

    public function show(Request $request, SupportRequest $supportRequest): Response
    {
        /** @var User $user */
        $user = $request->user();
        $supportRequest->load(['student', 'assignee', 'triager']);
        $student = $supportRequest->student;

        return Inertia::render('triage/requests/Show', [
            'request' => [
                'id' => $supportRequest->id,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->social_name ?: $student->name,
                    'registration' => $student->registration,
                    'course' => $student->course,
                    'period' => $student->period,
                ],
                'requester_name' => $supportRequest->requester_name,
                'requester_email' => $supportRequest->requester_email,
                'origin' => $supportRequest->origin->label(),
                'reasons' => $supportRequest->reasonLabels(),
                'description' => $supportRequest->description,
                'student_aware' => $supportRequest->student_aware,
                'status' => $supportRequest->status->value,
                'status_label' => $supportRequest->status->label(),
                'priority' => $supportRequest->priority->value,
                'priority_label' => $supportRequest->priority->label(),
                'assignee' => $supportRequest->assignee?->name,
                'triager' => $supportRequest->triager?->name,
                'triaged_at' => $supportRequest->triaged_at?->format('d/m/Y H:i'),
                // Parecer: somente para quem fez a triagem (RN-012).
                'triage_notes' => $supportRequest->canSeeTriageNotes($user) ? $supportRequest->triage_notes : null,
                'triage_notes_hidden' => $supportRequest->triage_notes !== null && ! $supportRequest->canSeeTriageNotes($user),
                'created_at' => $supportRequest->created_at?->format('d/m/Y'),
            ],
            'attendances' => $supportRequest->attendances()->with(['student', 'professional'])->get()
                ->map(fn (Attendance $attendance): array => AttendancePresenter::summary($attendance)),
            'canPerform' => $user->can('triage.perform') && $supportRequest->status === RequestStatus::PendingTriage,
            'professionals' => Professionals::options(),
            'priorities' => RequestPriority::options(),
            'types' => AttendanceType::options(),
            'modalities' => AttendanceModality::options(),
        ]);
    }

    /**
     * Decisão da triagem: define prioridade e profissional e agenda a 1ª conversa.
     */
    public function schedule(TriageScheduleRequest $request, SupportRequest $supportRequest, ScheduleAttendance $schedule): RedirectResponse
    {
        abort_unless($supportRequest->status === RequestStatus::PendingTriage, 409, 'Esta solicitação já foi triada.');

        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $slot = $request->slot();

        DB::transaction(function () use ($supportRequest, $schedule, $user, $data, $slot): void {
            $schedule->handle(
                $supportRequest->student,
                (int) $data['assigned_to'],
                $user,
                $slot,
                $supportRequest,
                firstConversation: true,
            );

            $supportRequest->priority = RequestPriority::from($data['priority']);
            $supportRequest->assigned_to = (int) $data['assigned_to'];
            $supportRequest->triage_notes = $data['triage_notes'] ?? null;
            $supportRequest->triaged_by = $user->id;
            $supportRequest->triaged_at = now()->toImmutable();
            $supportRequest->status = RequestStatus::Scheduled;
            $supportRequest->save();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => '1ª conversa agendada.']);

        return to_route('triage.index', ['aba' => 'agenda', 'semana' => $data['date']]);
    }

    /**
     * Decisão da triagem: devolver à coordenação para complementação.
     */
    public function giveBack(ReturnSupportRequestRequest $request, SupportRequest $supportRequest): RedirectResponse
    {
        abort_unless($supportRequest->status === RequestStatus::PendingTriage, 409, 'Esta solicitação já foi triada.');

        /** @var User $user */
        $user = $request->user();

        $supportRequest->triage_notes = $request->string('triage_notes')->toString();
        $supportRequest->triaged_by = $user->id;
        $supportRequest->triaged_at = now()->toImmutable();
        $supportRequest->status = RequestStatus::Returned;
        $supportRequest->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Solicitação devolvida à coordenação.']);

        return to_route('triage.index');
    }
}
