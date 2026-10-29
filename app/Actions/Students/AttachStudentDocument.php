<?php

namespace App\Actions\Students;

use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Anexa um documento (dado sensível) ao estudante, no disco privado,
 * com o usuário que enviou como único autorizado a vê-lo (RN-012).
 */
class AttachStudentDocument
{
    public function handle(Student $student, User $user, UploadedFile $file, string $title): StudentDocument
    {
        $path = $file->store("students/{$student->id}/documents", StudentDocument::DISK);

        if ($path === false) {
            throw new RuntimeException('Não foi possível salvar o arquivo.');
        }

        $document = new StudentDocument([
            'title' => $title,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);
        $document->student()->associate($student);
        $document->uploaded_by = $user->id;
        $document->save();

        return $document;
    }
}
