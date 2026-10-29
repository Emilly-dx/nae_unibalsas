<?php

namespace App\Http\Controllers\Students;

use App\Actions\Students\AttachStudentDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\StoreStudentDocumentRequest;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documentos do estudante (RF-017/RF-018). Os arquivos ficam no disco
 * privado e só são entregues por este controller, após a policy
 * (RN-012, §15.8).
 */
class StudentDocumentController extends Controller
{
    public function store(StoreStudentDocumentRequest $request, Student $student, AttachStudentDocument $attach): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var UploadedFile $file */
        $file = $request->file('file');

        $attach->handle($student, $user, $file, (string) $request->validated('title'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Documento anexado.']);

        return to_route('students.show', $student);
    }

    /**
     * O estudante pode estar inativo (os documentos continuam acessíveis
     * ao autor), mas documentos removidos não são baixados.
     */
    public function download(int $student, int $document): StreamedResponse
    {
        $studentModel = Student::withTrashed()->findOrFail($student);
        $documentModel = $studentModel->documents()->findOrFail($document);

        Gate::authorize('view', $documentModel);

        activity('students.sensitive')
            ->performedOn($documentModel)
            ->event('downloaded')
            ->log('downloaded');

        return Storage::disk(StudentDocument::DISK)->download(
            $documentModel->path,
            $documentModel->original_name,
        );
    }

    /**
     * Remove da visualização (soft delete); o arquivo é preservado (RN-013).
     */
    public function destroy(Student $student, StudentDocument $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $document->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Documento removido.']);

        return to_route('students.show', $student);
    }
}
