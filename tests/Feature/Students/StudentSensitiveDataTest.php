<?php

use App\Models\Student;
use App\Models\StudentDisability;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Storage::fake(StudentDocument::DISK);
});

function professional(): User
{
    return userWithPermissions('students.view', 'students.sensitive');
}

describe('CID (SRS §9.2, RN-012)', function () {
    test('profissional com students.sensitive registra um CID', function () {
        $user = professional();
        $student = Student::factory()->create();

        $this->actingAs($user)
            ->post(route('students.disabilities.store', $student), ['cid_code' => ' f84.0 '])
            ->assertRedirect(route('students.show', $student));

        $disability = StudentDisability::query()->sole();

        expect($disability->cid_code)->toBe('F84.0')
            ->and($disability->created_by)->toBe($user->id);
    });

    test('o CID é armazenado criptografado no banco', function () {
        $this->actingAs(professional())
            ->post(route('students.disabilities.store', Student::factory()->create()), ['cid_code' => 'F84.0']);

        expect(DB::table('student_disabilities')->value('cid_code'))->not->toContain('F84');
    });

    test('rejeita CID em formato inválido', function () {
        $this->actingAs(professional())
            ->post(route('students.disabilities.store', Student::factory()->create()), ['cid_code' => 'abc'])
            ->assertSessionHasErrors('cid_code');
    });

    test('sem students.sensitive não registra CID', function () {
        $this->actingAs(userWithPermissions('students.view', 'students.edit'))
            ->post(route('students.disabilities.store', Student::factory()->create()), ['cid_code' => 'F84.0'])
            ->assertForbidden();
    });

    test('cada profissional vê apenas os CIDs que ele mesmo cadastrou', function () {
        $student = Student::factory()->create();
        $author = professional();
        $colleague = professional();

        StudentDisability::factory()->for($student)->create(['created_by' => $author->id, 'cid_code' => 'F84.0']);

        $this->actingAs($author)
            ->get(route('students.show', $student))
            ->assertInertia(fn (Assert $page) => $page
                ->component('students/Show')
                ->has('disabilities', 1)
                ->where('disabilities.0.cid_code', 'F84.0')
            );

        $this->actingAs($colleague)
            ->get(route('students.show', $student))
            ->assertInertia(fn (Assert $page) => $page->has('disabilities', 0));
    });

    test('o administrador não vê dados sensíveis de outros usuários (§28.13)', function () {
        $student = Student::factory()->create();
        $disability = StudentDisability::factory()->for($student)->create(['created_by' => professional()->id]);

        $this->actingAs(administrator())
            ->get(route('students.show', $student))
            ->assertInertia(fn (Assert $page) => $page->has('disabilities', 0));

        $this->actingAs(administrator())
            ->delete(route('students.disabilities.destroy', [$student, $disability]))
            ->assertForbidden();
    });

    test('outro profissional não remove o CID; o autor remove sem exclusão definitiva', function () {
        $student = Student::factory()->create();
        $author = professional();
        $disability = StudentDisability::factory()->for($student)->create(['created_by' => $author->id]);

        $this->actingAs(professional())
            ->delete(route('students.disabilities.destroy', [$student, $disability]))
            ->assertForbidden();

        $this->actingAs($author)
            ->delete(route('students.disabilities.destroy', [$student, $disability]))
            ->assertRedirect(route('students.show', $student));

        expect(StudentDisability::query()->count())->toBe(0)
            ->and(StudentDisability::withTrashed()->count())->toBe(1);
    });

    test('não é possível acessar CID de outro estudante pela URL', function () {
        $author = professional();
        $disability = StudentDisability::factory()->create(['created_by' => $author->id]);
        $otherStudent = Student::factory()->create();

        $this->actingAs($author)
            ->delete(route('students.disabilities.destroy', [$otherStudent, $disability]))
            ->assertNotFound();
    });

    test('o log de auditoria registra o evento sem o conteúdo sensível', function () {
        $this->actingAs(professional())
            ->post(route('students.disabilities.store', Student::factory()->create()), ['cid_code' => 'F84.0']);

        $activity = Activity::query()->where('log_name', 'students.sensitive')->sole();

        expect($activity->event)->toBe('created')
            ->and(json_encode($activity->attribute_changes ?? []))->not->toContain('F84');
    });
});

describe('documentos (RF-017, RF-018, §15.8)', function () {
    test('anexa documento no disco privado', function () {
        $user = professional();
        $student = Student::factory()->create();

        $this->actingAs($user)
            ->post(route('students.documents.store', $student), [
                'title' => 'Laudo neurológico',
                'file' => UploadedFile::fake()->create('laudo.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect(route('students.show', $student));

        $document = StudentDocument::query()->sole();

        expect($document->uploaded_by)->toBe($user->id)
            ->and($document->original_name)->toBe('laudo.pdf');

        Storage::disk(StudentDocument::DISK)->assertExists($document->path);
    });

    test('rejeita tipos de arquivo não permitidos', function () {
        $this->actingAs(professional())
            ->post(route('students.documents.store', Student::factory()->create()), [
                'title' => 'Script',
                'file' => UploadedFile::fake()->create('script.exe', 10),
            ])
            ->assertSessionHasErrors('file');
    });

    test('apenas quem anexou baixa o documento', function () {
        $student = Student::factory()->create();
        $author = professional();

        $this->actingAs($author)->post(route('students.documents.store', $student), [
            'title' => 'Laudo',
            'file' => UploadedFile::fake()->create('laudo.pdf', 10, 'application/pdf'),
        ]);
        $document = StudentDocument::query()->sole();

        $this->actingAs($author)
            ->get(route('students.documents.download', [$student, $document]))
            ->assertOk()
            ->assertDownload('laudo.pdf');

        $this->actingAs(professional())
            ->get(route('students.documents.download', [$student, $document]))
            ->assertForbidden();

        $this->actingAs(administrator())
            ->get(route('students.documents.download', [$student, $document]))
            ->assertForbidden();
    });

    test('documento removido não pode mais ser baixado', function () {
        $student = Student::factory()->create();
        $author = professional();
        $document = StudentDocument::factory()->for($student)->create(['uploaded_by' => $author->id]);

        $this->actingAs($author)->delete(route('students.documents.destroy', [$student, $document]));

        $this->actingAs($author)
            ->get(route('students.documents.download', [$student, $document]))
            ->assertNotFound();

        expect(StudentDocument::withTrashed()->count())->toBe(1);
    });

    test('a página do estudante lista só os documentos do próprio usuário', function () {
        $student = Student::factory()->create();
        $author = professional();
        StudentDocument::factory()->for($student)->create(['uploaded_by' => $author->id]);
        StudentDocument::factory()->for($student)->create(['uploaded_by' => professional()->id]);

        $this->actingAs($author)
            ->get(route('students.show', $student))
            ->assertInertia(fn (Assert $page) => $page->has('documents', 1));
    });
});
