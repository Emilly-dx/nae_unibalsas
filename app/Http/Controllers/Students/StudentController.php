<?php

namespace App\Http\Controllers\Students;

use App\Enums\Sex;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\StoreStudentRequest;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Models\Student;
use App\Models\StudentDisability;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'registration' => $student->registration,
                'course' => $student->course,
                'period' => $student->period,
                'active' => $student->deleted_at === null,
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

    public function create(): Response
    {
        return Inertia::render('students/Create', [
            'sexOptions' => Sex::options(),
            'courses' => Student::withTrashed()->distinct()->orderBy('course')->pluck('course'),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $student = Student::query()->create($request->validated());

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
        ]);
    }

    public function edit(Student $student): Response
    {
        return Inertia::render('students/Edit', [
            'student' => $this->present($student),
            'sexOptions' => Sex::options(),
            'courses' => Student::withTrashed()->distinct()->orderBy('course')->pluck('course'),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());

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
    private function present(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'registration' => $student->registration,
            'course' => $student->course,
            'period' => $student->period,
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
