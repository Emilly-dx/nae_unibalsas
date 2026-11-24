<?php

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(AccessControlSeeder::class);
});

describe('trilha de auditoria (RF-051 a RF-054)', function () {
    test('lista quem fez o quê, quando e quais campos mudaram, sem os valores', function () {
        $paula = User::factory()->create(['name' => 'Paula']);
        $student = Student::factory()->create();
        $this->actingAs($paula);
        $student->update(['phone' => '99988887777', 'course' => 'Direito']);

        $this->actingAs(administrator())->get(route('admin.audit.index', ['modulo' => 'students', 'acao' => 'updated']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Audit')
                ->has('activities.data', 1)
                ->where('activities.data.0.causer', 'Paula')
                ->where('activities.data.0.event', 'Alterou')
                ->where('activities.data.0.module', 'Estudantes')
                ->where('activities.data.0.subject', "Estudante nº {$student->id}")
                ->where('activities.data.0.fields', ['curso', 'telefone'])
            )
            ->assertDontSee('99988887777');
    });

    test('o conteúdo sigiloso nunca aparece na auditoria', function () {
        $attendance = Attendance::factory()->registered()->create(['demand' => 'Relato sigiloso do estudante']);
        $attendance->update(['conduct' => 'Condução sigilosa']);

        $this->actingAs(administrator())->get(route('admin.audit.index'))
            ->assertOk()
            ->assertDontSee('Relato sigiloso do estudante')
            ->assertDontSee('Condução sigilosa');
    });

    test('filtra por usuário e período', function () {
        $paula = User::factory()->create();
        activity('reports')->causedBy($paula)->event('exported')->withProperties(['format' => 'pdf'])->log('x');
        activity('reports')->causedBy(User::factory()->create())->event('exported')->log('y');

        $this->actingAs(administrator())->get(route('admin.audit.index', ['usuario' => $paula->id, 'modulo' => 'reports']))
            ->assertInertia(fn (Assert $page) => $page->has('activities.data', 1)->where('activities.data.0.description', 'Formato: PDF'));

        $this->actingAs(administrator())->get(route('admin.audit.index', ['modulo' => 'reports', 'de' => now()->addDay()->toDateString()]))
            ->assertInertia(fn (Assert $page) => $page->has('activities.data', 0));
    });

    test('exige audit.view', function () {
        $this->actingAs(userWithPermissions('users.view'))->get(route('admin.audit.index'))->assertForbidden();
        $this->actingAs(userWithPermissions('audit.view'))->get(route('admin.audit.index'))->assertOk();
    });
});

describe('acessos ao sistema', function () {
    test('login, saída e senha errada ficam registrados', function () {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'errada']);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
        $this->post(route('logout'));

        $events = Activity::query()->where('log_name', 'auth')->orderBy('id')->pluck('event')->all();
        expect($events)->toBe(['failed', 'login', 'logout']);

        $failed = Activity::query()->where('event', 'failed')->sole();
        expect($failed->properties->get('email'))->toBe($user->email)
            ->and($failed->properties->has('password'))->toBeFalse();
    });
});
