<?php

use App\Models\ACL\Module;
use App\Models\ACL\Permission;
use App\Models\ACL\Role;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

test('the initial modules from the SRS are seeded', function () {
    (new ModuleSeeder)->run();

    expect(Module::query()->pluck('slug')->sort()->values()->all())->toBe([
        'acl', 'attendances', 'profiles', 'referrals', 'reports', 'students', 'users',
    ]);
});

test('the initial permissions from the SRS are seeded under their module', function () {
    (new ModuleSeeder)->run();
    (new PermissionSeeder)->run();

    expect(Permission::query()->count())->toBe(27);
    expect(Permission::query()->where('slug', 'acl.assign_roles')->first()->module->slug)->toBe('acl');
    expect(Permission::query()->where('slug', 'attendances.finish')->exists())->toBeTrue();
});

test('the initial roles from the SRS are seeded', function () {
    (new RoleSeeder)->run();

    expect(Role::query()->pluck('slug')->sort()->values()->all())->toBe([
        'administrator', 'assistant', 'coordinator', 'pedagogue', 'psychologist', 'psychopedagogue',
    ]);
});

test('seeders are idempotent', function () {
    (new ModuleSeeder)->run();
    (new ModuleSeeder)->run();

    expect(Module::query()->count())->toBe(7);
});
