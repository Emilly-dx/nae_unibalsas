<?php

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

function validStudentData(array $overrides = []): array
{
    return [
        'name' => 'Maria da Silva',
        'registration' => '26.1.23456',
        'course' => 'Análise e Desenvolvimento de Sistemas',
        'period' => 3,
        'email' => 'maria@example.com',
        'phone' => '(99) 99999-0000',
        'birth_date' => '2004-05-10',
        'sex' => 'female',
        'guardian_name' => null,
        'guardian_phone' => null,
        'guardian_relationship' => null,
        'modality' => 'presential',
        'shift' => 'evening',
        'consent' => '1',
        ...$overrides,
    ];
}

describe('autorização (RF-010)', function () {
    test('visitante é redirecionado para o login', function () {
        $this->get(route('students.index'))->assertRedirect(route('login'));
    });

    test('usuário sem students.view não lista estudantes', function () {
        $this->actingAs(userWithPermissions())
            ->get(route('students.index'))
            ->assertForbidden();
    });

    test('usuário só com students.view não cadastra, edita nem inativa', function () {
        $user = userWithPermissions('students.view');
        $student = Student::factory()->create();

        $this->actingAs($user)->get(route('students.create'))->assertForbidden();
        $this->actingAs($user)->post(route('students.store'), validStudentData())->assertForbidden();
        $this->actingAs($user)->put(route('students.update', $student), validStudentData())->assertForbidden();
        $this->actingAs($user)->delete(route('students.destroy', $student))->assertForbidden();

        expect(Student::query()->count())->toBe(1);
    });

    test('administrador acessa o módulo sem permissões explícitas', function () {
        $this->actingAs(administrator())
            ->get(route('students.index'))
            ->assertOk();
    });
});

describe('listagem e pesquisa (RF-015, RF-016)', function () {
    test('lista apenas estudantes ativos por padrão, em ordem alfabética', function () {
        Student::factory()->create(['name' => 'Bruno']);
        Student::factory()->create(['name' => 'Ana']);
        Student::factory()->inactive()->create(['name' => 'Carlos']);

        $this->actingAs(userWithPermissions('students.view'))
            ->get(route('students.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('students/Index')
                ->has('students.data', 2)
                ->where('students.data.0.name', 'Ana')
                ->where('students.data.1.name', 'Bruno')
            );
    });

    test('pesquisa por nome ou matrícula', function () {
        Student::factory()->create(['name' => 'Joana Lima', 'registration' => '24.1.00111']);
        Student::factory()->create(['name' => 'Pedro Alves', 'registration' => '24.2.00222']);

        $user = userWithPermissions('students.view');

        $this->actingAs($user)
            ->get(route('students.index', ['search' => 'joana']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('students.data', 1)
                ->where('students.data.0.name', 'Joana Lima')
            );

        $this->actingAs($user)
            ->get(route('students.index', ['search' => '222']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('students.data', 1)
                ->where('students.data.0.name', 'Pedro Alves')
            );
    });

    test('filtra por curso e por inativos', function () {
        Student::factory()->create(['course' => 'Direito']);
        Student::factory()->create(['course' => 'Enfermagem']);
        Student::factory()->inactive()->create(['course' => 'Direito']);

        $user = userWithPermissions('students.view');

        $this->actingAs($user)
            ->get(route('students.index', ['course' => 'Direito']))
            ->assertInertia(fn (Assert $page) => $page->has('students.data', 1));

        $this->actingAs($user)
            ->get(route('students.index', ['status' => 'inactive']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('students.data', 1)
                ->where('students.data.0.active', false)
            );
    });
});

describe('cadastro e edição (RF-013, RF-014)', function () {
    test('cadastra um estudante', function () {
        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), validStudentData())
            ->assertRedirect(route('students.show', Student::query()->first()));

        $student = Student::query()->sole();

        expect($student->name)->toBe('Maria da Silva')
            ->and($student->period)->toBe(3)
            ->and($student->birth_date?->toDateString())->toBe('2004-05-10');
    });

    test('valida campos obrigatórios com mensagens em português', function () {
        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), [])
            ->assertSessionHasErrors([
                'name' => 'O campo nome é obrigatório.',
                'registration',
                'course',
                'period',
            ]);
    });

    test('não permite matrícula duplicada, inclusive de estudante inativo', function () {
        Student::factory()->inactive()->create(['registration' => '26.1.23456']);

        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), validStudentData())
            ->assertSessionHasErrors(['registration' => 'Já existe um cadastro com esta matrícula.']);
    });

    test('edita um estudante mantendo a própria matrícula', function () {
        $student = Student::factory()->create(['registration' => '26.1.23456']);

        $this->actingAs(userWithPermissions('students.edit'))
            ->put(route('students.update', $student), validStudentData(['name' => 'Maria Souza']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('students.show', $student));

        expect($student->fresh()?->name)->toBe('Maria Souza');
    });

    test('não aceita sexo fora da lista nem data de nascimento futura', function () {
        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), validStudentData([
                'sex' => 'invalido',
                'birth_date' => now()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors(['sex', 'birth_date']);
    });
});

describe('campos do wireframe (nome social, acessibilidade, LGPD)', function () {
    test('cadastro exige o consentimento e registra quando foi dado', function () {
        $user = userWithPermissions('students.create');

        $this->actingAs($user)
            ->post(route('students.store'), validStudentData(['consent' => null]))
            ->assertSessionHasErrors('consent');

        $this->actingAs($user)->post(route('students.store'), validStudentData());

        expect(Student::query()->sole()->consent_at)->not->toBeNull();
    });

    test('salva turno, modalidade, nome social e recursos de acessibilidade', function () {
        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), validStudentData([
                'social_name' => 'Mari',
                'modality' => 'distance',
                'needs_accessibility' => '1',
                'accessibility_resources' => ['extra_time', 'libras_interpreter'],
            ]))
            ->assertSessionHasNoErrors();

        $student = Student::query()->sole();

        expect($student->social_name)->toBe('Mari')
            ->and($student->modality->label())->toBe('EAD')
            ->and($student->shift?->label())->toBe('Noturno')
            ->and($student->accessibilityResourceLabels())->toBe(['Tempo adicional em provas', 'Intérprete de Libras']);
    });

    test('recursos são descartados quando não há necessidade de acessibilidade', function () {
        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), validStudentData([
                'needs_accessibility' => '0',
                'accessibility_resources' => ['extra_time'],
            ]));

        expect(Student::query()->sole()->accessibility_resources)->toBe([]);
    });

    test('rejeita recurso de acessibilidade desconhecido', function () {
        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), validStudentData([
                'needs_accessibility' => '1',
                'accessibility_resources' => ['teletransporte'],
            ]))
            ->assertSessionHasErrors('accessibility_resources.0');
    });

    test('laudo enviado no cadastro vira documento sensível do autor', function () {
        Storage::fake('local');
        $user = userWithPermissions('students.create', 'students.sensitive');

        $this->actingAs($user)->post(route('students.store'), validStudentData([
            'document' => UploadedFile::fake()->create('laudo.pdf', 50, 'application/pdf'),
        ]))->assertSessionHasNoErrors();

        $document = StudentDocument::query()->sole();

        expect($document->uploaded_by)->toBe($user->id)
            ->and($document->title)->toBe('Laudo ou documento de apoio');
    });

    test('sem students.sensitive não é possível enviar laudo no cadastro', function () {
        Storage::fake('local');

        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), validStudentData([
                'document' => UploadedFile::fake()->create('laudo.pdf', 50, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('document');

        expect(Student::query()->count())->toBe(0);
    });
});

describe('inativação (RN-013)', function () {
    test('inativar não exclui definitivamente', function () {
        $student = Student::factory()->create();

        $this->actingAs(userWithPermissions('students.delete'))
            ->delete(route('students.destroy', $student))
            ->assertRedirect(route('students.index'));

        expect(Student::query()->count())->toBe(0)
            ->and(Student::withTrashed()->count())->toBe(1);
    });

    test('estudante inativo pode ser visualizado e reativado, mas não editado', function () {
        $student = Student::factory()->inactive()->create();
        $user = userWithPermissions('students.view', 'students.edit', 'students.delete');

        $this->actingAs($user)
            ->get(route('students.show', $student))
            ->assertInertia(fn (Assert $page) => $page->where('student.active', false));

        $this->actingAs($user)->get(route('students.edit', $student))->assertNotFound();

        $this->actingAs($user)->patch(route('students.restore', $student));

        expect($student->fresh()?->trashed())->toBeFalse();
    });
});

describe('auditoria (RF-051 a RF-054)', function () {
    test('cadastro e edição ficam registrados com o usuário responsável', function () {
        $user = userWithPermissions('students.create', 'students.edit');

        $this->actingAs($user)->post(route('students.store'), validStudentData());
        $student = Student::query()->sole();
        $this->actingAs($user)->put(route('students.update', $student), validStudentData(['period' => 4]));

        $activities = Activity::query()
            ->where('log_name', 'students')
            ->orderBy('id')
            ->get();

        expect($activities)->toHaveCount(2)
            ->and($activities[0]->event)->toBe('created')
            ->and($activities[1]->event)->toBe('updated')
            ->and($activities[1]->causer_id)->toBe($user->id);
    });
});

test('telefones são salvos só com dígitos e validados com DDD', function () {
    $user = userWithPermissions('students.create');

    $this->actingAs($user)->post(route('students.store'), validStudentData(['phone' => '(99) 98888-7777']));
    expect(Student::query()->sole()->phone)->toBe('99988887777');

    $this->actingAs($user)
        ->post(route('students.store'), validStudentData(['registration' => '24.1.00999', 'phone' => '9999-0000']))
        ->assertSessionHasErrors(['phone' => 'O campo telefone deve ter entre 10 e 11 dígitos (DDD + número).']);
});

describe('matrícula e curso (formato oficial da UNIBALSAS)', function () {
    test('aceita a matrícula com ou sem pontos e salva no formato oficial', function () {
        $user = userWithPermissions('students.create');

        $this->actingAs($user)
            ->post(route('students.store'), validStudentData(['registration' => '24108965']))
            ->assertSessionHasNoErrors();

        expect(Student::query()->sole()->registration)->toBe('24.1.08965');
    });

    test('a mesma matrícula digitada sem pontos é reconhecida como duplicada', function () {
        Student::factory()->create(['registration' => '24.1.08965']);

        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), validStudentData(['registration' => '24108965']))
            ->assertSessionHasErrors(['registration' => 'Já existe um cadastro com esta matrícula.']);
    });

    test('recusa matrícula fora do formato', function () {
        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), validStudentData(['registration' => '2507865']))
            ->assertSessionHasErrors(['registration' => 'Informe a matrícula no formato 24.1.08965 (ano, semestre e número).']);
    });

    test('a pesquisa encontra a matrícula com ou sem pontos', function () {
        Student::factory()->create(['name' => 'Felipe', 'registration' => '25.1.09687']);
        Student::factory()->create(['name' => 'Outra pessoa', 'registration' => '24.1.06598']);
        $user = userWithPermissions('students.view');

        foreach (['25.1.09687', '25109687', '09687'] as $term) {
            $this->actingAs($user)->get(route('students.index', ['search' => $term]))
                ->assertInertia(fn (Assert $page) => $page->has('students.data', 1)->where('students.data.0.name', 'Felipe'));
        }
    });

    test('o curso precisa ser um dos cursos da UNIBALSAS', function () {
        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), validStudentData(['course' => 'Engenharia Espacial']))
            ->assertSessionHasErrors(['course' => 'Selecione um curso da lista.']);
    });

    test('na edição, mantém um curso antigo que não está mais na lista', function () {
        $student = Student::factory()->create(['course' => 'Pedagogia', 'registration' => '20.1.00001']);

        $this->actingAs(userWithPermissions('students.edit'))
            ->put(route('students.update', $student), validStudentData(['course' => 'Pedagogia', 'registration' => '20.1.00001']))
            ->assertSessionHasNoErrors();
    });

    test('aceita a modalidade semipresencial', function () {
        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), validStudentData(['course' => 'Biomedicina', 'modality' => 'hybrid']))
            ->assertSessionHasNoErrors();

        expect(Student::query()->sole()->modality->label())->toBe('Semipresencial');
    });
});
