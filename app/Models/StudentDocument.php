<?php

namespace App\Models;

use App\Models\Contracts\SensitiveRecord;
use Carbon\CarbonImmutable;
use Database\Factories\StudentDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Documento anexado ao estudante (RF-017). Informação sensível:
 * visível apenas para quem anexou (RN-012, §15.8).
 *
 * @property int $id
 * @property int $student_id
 * @property int $uploaded_by
 * @property string $title
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Student $student
 */
#[Fillable(['title', 'path', 'original_name', 'mime_type', 'size'])]
class StudentDocument extends Model implements SensitiveRecord
{
    /** @use HasFactory<StudentDocumentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public const string DISK = 'local';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function ownerId(): int
    {
        return $this->uploaded_by;
    }

    /**
     * @param  Builder<StudentDocument>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('uploaded_by', $user->id);
    }

    /**
     * Registra o evento (quem/quando) sem gravar o conteúdo sensível no log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('students.sensitive');
    }
}
