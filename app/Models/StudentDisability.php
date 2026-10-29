<?php

namespace App\Models;

use App\Models\Contracts\SensitiveRecord;
use Carbon\CarbonImmutable;
use Database\Factories\StudentDisabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * CID relacionado à deficiência/acessibilidade do estudante (SRS §9.2).
 * Informação sensível: visível apenas para quem cadastrou (RN-012).
 *
 * @property int $id
 * @property int $student_id
 * @property int $created_by
 * @property string $cid_code
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Student $student
 * @property-read User $author
 */
#[Fillable(['cid_code', 'description'])]
class StudentDisability extends Model implements SensitiveRecord
{
    /** @use HasFactory<StudentDisabilityFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cid_code' => 'encrypted',
            'description' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ownerId(): int
    {
        return $this->created_by;
    }

    /**
     * @param  Builder<StudentDisability>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('created_by', $user->id);
    }

    /**
     * Registra o evento (quem/quando) sem gravar o conteúdo sensível no log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('students.sensitive');
    }
}
