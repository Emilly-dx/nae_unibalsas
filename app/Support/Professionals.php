<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Profissionais do NAE: usuários cujo perfil permite registrar atendimentos.
 */
class Professionals
{
    /**
     * @return Builder<User>
     */
    public static function query(): Builder
    {
        return User::query()->whereHas(
            'roles.permissions',
            fn (Builder $query) => $query->where('slug', 'attendances.create'),
        );
    }

    /**
     * @return list<int>
     */
    public static function ids(): array
    {
        return array_values(self::query()->pluck('id')->map(fn ($id): int => (int) $id)->all());
    }

    /**
     * @return Collection<int, array{value: int, label: string}>
     */
    public static function options(): Collection
    {
        return self::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (User $user): array => ['value' => $user->id, 'label' => $user->name])
            ->values();
    }
}
