<?php

namespace App\Http\Controllers\Students;

use App\Actions\Students\AttachStudentDocument;
use App\Enums\AccessibilityResource;
use App\Enums\FollowUp\FollowUpClosureReason;
use App\Enums\FollowUp\FollowUpStatus;
use App\Enums\Sex;
use App\Enums\Shift;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\StoreStudentRequest;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Models\Attendance;
use App\Models\Referral;
use App\Models\Student;
use App\Models\StudentDisability;
use App\Models\StudentDocument;
use App\Models\SupportRequest;
use App\Models\User;
use App\Support\AttendancePresenter;
use App\Support\ReferralPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cadastro de estudantes (SRS §9, RF-013 a RF-016).
 * A autorização por ação é feita nas rotas (permission:students.*).
 */
class StudentController extends Controller
{
    private const int PER_PAGE = 15;

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $status = in_array($status, ['active', 'inactive', 'all'], true) ? $status : 'active';
        $course = $request->string('course')->trim()->toString();
        $search = $request->string('search')->trim()->toString();

        $students = Student::query()
            ->when($status === 'inactive', fn ($query) => $query->onlyTrashed())
            ->when($status === 'all', fn ($query) => $query->withTrashed())
            ->search($search)
            ->when($course !== '', fn ($query) => $query->where('course', $course))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Student $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'social_name' => $student->social_name,
                'registration' => $student->registration,
                'course' => $student->course,
                'period' => $student->period,
                'shift_label' => $student->shift?->label(),
                'modality_label' => $student->modality->label(),
                'email' => $student->email,
                'phone' => $student->phone,
                'needs_accessibility' => $student->needs_accessibility,
                'accessibility_resources' => $student->accessibilityResourceLabels(),
                'active' => $student->deleted_at === null,
                'follow_up' => $student->followUpStatus()->value,
                'follow_up_label' => $student->followUpStatus()->label(),
            ]);

        return Inertia::render('students/Index', [
            'students' => $students,
            'filters' => [
                'search' => $search,
                'course' => $course,
                'status' => $status,
            ],
            'courses' => Student::withTrashed()->distinct()->orderBy('course')->pluck('course'),
        ]);
    }

    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('students/Create', [
            ...$this->formOptions(),
            'canAttachDocument' => $user->can('students.sensitive'),
        ]);
    }

    public function store(StoreStudentRequest $request, AttachStudentDocument $attach): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $student = Student::query()->create([
            ...$request->studentData(),
            'consent_at' => now(),
        ]);

        $document = $request->file('document');

        if ($document instanceof UploadedFile) {
            $attach->handle($student, $user, $document, $request->validated('document_title') ?: 'Laudo ou documento de apoio');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estudante cadastrado com sucesso.']);

        return to_route('students.show', $student);
    }

    public function show(Request $request, Student $student): Response
    {
        /** @var User $user */
        $user = $request->user();
        $canManageSensitive = $user->can('students.sensitive');

        return Inertia::render('students/Show', [
            'student' => $this->present($student),
            // RN-012: cada usuário só recebe os registros sensíveis que ele mesmo cadastrou.
            'disabilities' => $canManageSensitive
                ? $student->disabilities()->ownedBy($user)->latest()->get()
                    ->map(fn (StudentDisability $disability): array => [
                        'id' => $disability->id,
                        'cid_code' => $disability->cid_code,
                        'description' => $disability->description,
                        'created_at' => $disability->created_at?->toIso8601String(),
                    ])
                : [],
            'documents' => $canManageSensitive
                ? $student->documents()->ownedBy($user)->latest()->get()
                    ->map(fn (StudentDocument $document): array => [
                        'id' => $document->id,
                        'title' => $document->title,
                        'original_name' => $document->original_name,
                        'mime_type' => $document->mime_type,
                        'size' => $document->size,
                        'created_at' => $document->created_at?->toIso8601String(),
                    ])
                : [],
            'canManageSensitive' => $canManageSensitive,
            // Histórico (RN-009): só metadados; o conteúdo dos atendimentos é sigiloso.
            'history' => [
                'attendances' => $user->can('attendances.view')
                    ? $student->attendances()->with(['student', 'professional'])->latest('scheduled_at')->get()
                        ->map(fn (Attendance $attendance): array => [
                            ...AttendancePresenter::summary($attendance),
                            // Evolução do caso (RF-032): o profissional vê o resumo dos próprios registros.
                            'excerpt' => $attendance->professional_id === $user->id && $attendance->registered_at !== null
                                ? Str::limit((string) $attendance->demand, 180)
                                : null,
                        ])
                    : null,
                'requests' => $user->can('triage.view')
                    ? $student->supportRequests()->latest()->get()
                        ->map(fn (SupportRequest $item): array => [
                            'id' => $item->id,
                            'date' => $item->created_at?->format('d/m/Y'),
                            'date_iso' => $item->created_at?->toIso8601String(),
                            'origin' => $item->origin->label(),
                            'status_label' => $item->status->label(),
                        ])
                    : null,
                'referrals' => $user->can('referrals.view')
                    ? $student->referrals()->with(['student', 'author', 'destinationUser'])->latest('referred_on')->get()
                        ->map(fn (Referral $referral): array => ReferralPresenter::summary($referral))
                    : null,
            ],
            'followUp' => $this->followUp($student, $user),
            'can' => [
                'createRequest' => $user->can('triage.create') && $student->deleted_at === null,
                'schedule' => $user->can('attendances.schedule') && $student->deleted_at === null,
                'register' => $user->can('attendances.create') && $student->deleted_at === null,
                'refer' => $user->can('referrals.create') && $student->deleted_at === null,
            ],
        ]);
    }

    /**
     * Situação atual e resumo do acompanhamento (SRS §11, RF-030 a RF-033).
     *
     * @return array<string, mixed>|null
     */
    private function followUp(Student $student, User $user): ?array
    {
        if (! $user->can('attendances.view')) {
            return null;
        }

        $status = $student->followUpStatus();
        $registered = $student->attendances()->registered()->with('professional')->orderBy('scheduled_at')->get();

        return [
            'status' => $status->value,
            'status_label' => $status->label(),
            'started_at' => $registered->first()?->scheduled_at->format('d/m/Y'),
            'last_at' => $registered->last()?->scheduled_at->format('d/m/Y'),
            'attendances' => $registered->count(),
            'professionals' => $registered->map(fn (Attendance $attendance): string => $attendance->professional->name)->unique()->values(),
            'open_referrals' => $student->referrals()->open()->count(),
            'closed_at' => $status === FollowUpStatus::Closed ? $student->follow_up_closed_at?->format('d/m/Y') : null,
            'closure_reason' => $status === FollowUpStatus::Closed ? $student->follow_up_closure_reason?->label() : null,
            'closed_by' => $status === FollowUpStatus::Closed ? $student->followUpCloser?->name : null,
            'canClose' => $status === FollowUpStatus::Active && $user->can('attendances.finish'),
            'closureReasons' => FollowUpClosureReason::options(),
        ];
    }

    public function edit(Student $student): Response
    {
        return Inertia::render('students/Edit', [
            'student' => $this->present($student),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $student->fill($request->studentData());

        if ($request->boolean('consent') && $student->consent_at === null) {
            $student->consent_at = now();
        }

        $student->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dados do estudante atualizados.']);

        return to_route('students.show', $student);
    }

    /**
     * Inativa o estudante. Não há exclusão definitiva (RN-013).
     */
    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estudante inativado.']);

        return to_route('students.index');
    }

    public function restore(Student $student): RedirectResponse
    {
        $student->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estudante reativado.']);

        return to_route('students.show', $student);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'sexOptions' => Sex::options(),
            'shiftOptions' => Shift::options(),
            'accessibilityOptions' => AccessibilityResource::options(),
            'courses' => Student::withTrashed()->distinct()->orderBy('course')->pluck('course'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'social_name' => $student->social_name,
            'registration' => $student->registration,
            'course' => $student->course,
            'period' => $student->period,
            'shift' => $student->shift?->value,
            'shift_label' => $student->shift?->label(),
            'modality' => $student->modality->value,
            'modality_label' => $student->modality->label(),
            'needs_accessibility' => $student->needs_accessibility,
            'accessibility_resources' => $student->accessibility_resources ?? [],
            'accessibility_resource_labels' => $student->accessibilityResourceLabels(),
            'has_external_follow_up' => $student->has_external_follow_up,
            'consent_at' => $student->consent_at?->toIso8601String(),
            'email' => $student->email,
            'phone' => $student->phone,
            'birth_date' => $student->birth_date?->toDateString(),
            'sex' => $student->sex?->value,
            'sex_label' => $student->sex?->label(),
            'guardian_name' => $student->guardian_name,
            'guardian_phone' => $student->guardian_phone,
            'guardian_relationship' => $student->guardian_relationship,
            'active' => $student->deleted_at === null,
            'created_at' => $student->created_at?->toIso8601String(),
            'updated_at' => $student->updated_at?->toIso8601String(),
        ];
    }
}
