<?php

namespace App\Models;

use App\Enums\Sex;
use Carbon\CarbonImmutable;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Estudante atendido pelo NAE (SRS §9). Contém apenas dados cadastrais
 * comuns; CID e documentos ficam em tabelas próprias (dados sensíveis).
 *
 * Nunca é excluído definitivamente (RN-013): "inativar" = soft delete.
 *
 * @property int $id
 * @property string $name
 * @property string $registration
 * @property string $course
 * @property int $period
 * @property string|null $email
 * @property string|null $phone
 * @property CarbonImmutable|null $birth_date
 * @property Sex|null $sex
 * @property string|null $guardian_name
 * @property string|null $guardian_phone
 * @property string|null $guardian_relationship
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'name', 'registration', 'course', 'period', 'email', 'phone', 'birth_date',
    'sex', 'guardian_name', 'guardian_phone', 'guardian_relationship',
])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => 'integer',
            'birth_date' => 'immutable_date',
            'sex' => Sex::class,
        ];
    }

    /**
     * @return HasMany<StudentDisability, $this>
     */
    public function disabilities(): HasMany
    {
        return $this->hasMany(StudentDisability::class);
    }

    /**
     * @return HasMany<StudentDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    /**
     * Pesquisa por nome ou matrícula (RF-016).
     *
     * @param  Builder<Student>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('registration', 'like', "%{$term}%");
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('students')
            ->logFillable()
            ->logOnlyDirty();
    }
}
