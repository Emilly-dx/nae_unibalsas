<?php

namespace Database\Factories\ACL;

use App\Models\ACL\Module;
use App\Models\ACL\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = fake()->unique()->word();

        return [
            'module_id' => Module::factory(),
            'name' => ucfirst($action),
            'slug' => $action,
        ];
    }
}
