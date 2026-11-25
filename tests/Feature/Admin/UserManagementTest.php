<?php

use App\Models\ACL\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(AccessControlSeeder::class);
});

function roleId(string $slug): int
{
    return (int) Role::query()->where('slug', $slug)->value('id');
}

function accountData(array $overrides = []): array
{
    return [
        'name' => 'Rita Pedagoga',
        'email' => 'rita@unibalsas.edu.br',
        'password' => 'senha-provisoria-123',
        'password_confirmation' => 'senha-provisoria-123',
        'roles' => [roleId('pedagogue')],
        ...$overrides,
    ];
}

describe('contas da equipe', function () {
    test('o administrador cria a conta com perfil, já verificada', function () {
        $this->actingAs(administrator())
            ->post(route('admin.users.store'), accountData())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'rita@unibalsas.edu.br')->sole();

        expect($user->hasRole('pedagogue'))->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull()
            ->and(Hash::check('senha-provisoria-123', $user->password))->toBeTrue()
            ->and($user->hasPermissionTo('attendances.create'))->toBeTrue();
    });

    test('valida e-mail repetido e confirmação de senha, em português', function () {
        User::factory()->create(['email' => 'rita@unibalsas.edu.br']);

        $this->actingAs(administrator())
            ->post(route('admin.users.store'), accountData(['password_confirmation' => 'outra']))
            ->assertSessionHasErrors([
                'email' => 'Já existe uma conta com este e-mail.',
                'password' => 'A confirmação da senha não confere.',
            ]);
    });

    test('lista com filtros e último acesso', function () {
        $rita = User::factory()->create(['name' => 'Rita']);
        $rita->assignRole('pedagogue');
        activity('auth')->causedBy($rita)->event('login')->log('Entrou no sistema');
        User::factory()->create(['name' => 'Ex-servidor', 'deactivated_at' => now()]);

        $this->actingAs(administrator())->get(route('admin.users.index', ['perfil' => 'pedagogue']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Index')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Rita')
                ->where('users.data.0.roles', ['Pedagogo'])
                ->whereNot('users.data.0.last_login', null)
            );

        $this->actingAs(administrator())->get(route('admin.users.index', ['situacao' => 'inativos']))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.active', false));
    });

    test('edita dados e perfis; a troca de perfis vai para a auditoria', function () {
        $admin = administrator();
        $user = User::factory()->create();
        $user->assignRole('pedagogue');
        $user->hasPermissionTo('attendances.create'); // aquece o cache

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), ['name' => 'Novo Nome', 'email' => $user->email, 'roles' => [roleId('assistant')]])
            ->assertSessionHasNoErrors();

        $user->refresh();
        expect($user->name)->toBe('Novo Nome')
            ->and($user->hasRole('assistant'))->toBeTrue()
            ->and($user->hasPermissionTo('attendances.create'))->toBeFalse();

        $log = Activity::query()->where('event', 'roles_updated')->sole();
        expect($log->causer_id)->toBe($admin->id)->and($log->properties->get('roles'))->toBe('Assistente');
    });

    test('a edição não troca a senha', function () {
        $user = User::factory()->create();

        $this->actingAs(administrator())
            ->put(route('admin.users.update', $user), ['name' => 'X', 'email' => $user->email, 'password' => 'nova-senha-123'])
            ->assertSessionHasErrors('password');
    });

    test('envia o link de nova senha para a pessoa', function () {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs(administrator())->post(route('admin.users.password-link', $user))->assertRedirect();

        Notification::assertSentTo($user, ResetPassword::class);
    });
});

describe('perfil de Administrador e menor privilégio', function () {
    test('quem não é administrador não concede o perfil de Administrador', function () {
        $manager = userWithPermissions('users.view', 'users.create', 'users.edit', 'acl.assign_roles');

        $this->actingAs($manager)
            ->post(route('admin.users.store'), accountData(['roles' => [roleId('administrator')]]))
            ->assertSessionHasErrors(['roles' => 'Só um administrador pode conceder ou retirar o perfil de Administrador.']);
    });

    test('o administrador não retira o próprio perfil de Administrador', function () {
        $admin = administrator();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'roles' => [roleId('coordinator')]])
            ->assertSessionHasErrors('roles');

        expect($admin->fresh()->hasRole('administrator'))->toBeTrue();
    });

    test('sem acl.assign_roles não muda perfis', function () {
        $editor = userWithPermissions('users.view', 'users.edit');
        $user = User::factory()->create();

        $this->actingAs($editor)
            ->put(route('admin.users.update', $user), ['name' => 'X', 'email' => $user->email, 'roles' => [roleId('coordinator')]])
            ->assertSessionHasErrors('roles');

        $this->actingAs($editor)
            ->put(route('admin.users.update', $user), ['name' => 'X', 'email' => $user->email])
            ->assertSessionHasNoErrors();
    });

    test('profissionais não acessam a administração', function () {
        $professional = User::factory()->create();
        $professional->assignRole('psychologist');

        $this->actingAs($professional)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($professional)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($professional)->get(route('admin.audit.index'))->assertForbidden();
        $this->actingAs($professional)->post(route('admin.users.store'), accountData())->assertForbidden();
    });
});

describe('desativação (RN-013)', function () {
    test('desativar impede o login e mantém a conta; reativar devolve o acesso', function () {
        $user = User::factory()->create();

        $this->actingAs(administrator())->patch(route('admin.users.deactivate', $user))->assertRedirect();
        expect($user->fresh()->isActive())->toBeFalse();

        auth()->logout();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
        $this->assertGuest();

        $this->actingAs(administrator())->patch(route('admin.users.reactivate', $user));
        auth()->logout();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($user);
    });

    test('quem foi desativado com a sessão aberta é desconectado', function () {
        $user = User::factory()->create(['deactivated_at' => now()]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Sua conta foi desativada. Procure a coordenação do NAE.');
        $this->assertGuest();
    });

    test('ninguém desativa a própria conta', function () {
        $admin = administrator();

        $this->actingAs($admin)->patch(route('admin.users.deactivate', $admin))->assertSessionHasErrors('user');
        expect($admin->fresh()->isActive())->toBeTrue();
    });

    test('só um administrador desativa outro administrador', function () {
        $manager = userWithPermissions('users.view', 'users.delete');

        $this->actingAs($manager)->patch(route('admin.users.deactivate', administrator()))->assertForbidden();
    });

    test('a desativação fica na auditoria', function () {
        $admin = administrator();
        $user = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.users.deactivate', $user));

        $log = Activity::query()->where('log_name', 'users')->where('subject_id', $user->id)->where('event', 'updated')->latest('id')->first();
        expect($log?->causer_id)->toBe($admin->id)
            ->and(array_keys($log?->attribute_changes?->get('attributes') ?? []))->toBe(['deactivated_at']);
    });
});
