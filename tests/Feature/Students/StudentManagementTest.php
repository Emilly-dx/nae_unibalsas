<?php

use App\Models\Student;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

function validStudentData(array $overrides = []): array
{
    return [
        'name' => 'Maria da Silva',
        'registration' => '2026123456',
        'course' => 'Análise e Desenvolvimento de Sistemas',
        'period' => 3,
        'email' => 'maria@example.com',
        'phone' => '(99) 99999-0000',
        'birth_date' => '2004-05-10',
        'sex' => 'female',
        'guardian_name' => null,
        'guardian_phone' => null,
        'guardian_relationship' => null,
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
        Student::factory()->create(['name' => 'Joana Lima', 'registration' => '111']);
        Student::factory()->create(['name' => 'Pedro Alves', 'registration' => '222']);

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
        Student::factory()->inactive()->create(['registration' => '2026123456']);

        $this->actingAs(userWithPermissions('students.create'))
            ->post(route('students.store'), validStudentData())
            ->assertSessionHasErrors(['registration' => 'Já existe um cadastro com esta matrícula.']);
    });

    test('edita um estudante mantendo a própria matrícula', function () {
        $student = Student::factory()->create(['registration' => '2026123456']);

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
