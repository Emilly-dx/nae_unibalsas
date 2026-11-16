<?php

namespace App\Models;

use App\Enums\Triage\RequestOrigin;
use App\Enums\Triage\RequestPriority;
use App\Enums\Triage\RequestReason;
use App\Enums\Triage\RequestStatus;
use Carbon\CarbonImmutable;
use Database\Factories\SupportRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Solicitação de apoio ao NAE e sua triagem (SRS §10.1, RN-001, RN-002).
 *
 * @property int $id
 * @property int $student_id
 * @property int $created_by
 * @property string $requester_name
 * @property string|null $requester_email
 * @property RequestOrigin $origin
 * @property list<string>|null $reasons
 * @property string $description
 * @property bool $student_aware
 * @property RequestStatus $status
 * @property RequestPriority $priority
 * @property int|null $assigned_to
 * @property int|null $triaged_by
 * @property string|null $triage_notes
 * @property CarbonImmutable|null $triaged_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Student $student
 * @property-read User|null $assignee
 * @property-read User|null $triager
 */
#[Fillable([
    'requester_name', 'requester_email', 'origin', 'reasons', 'description', 'student_aware',
])]
class SupportRequest extends Model
{
    /** @use HasFactory<SupportRequestFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'origin' => RequestOrigin::class,
            'reasons' => 'array',
            'description' => 'encrypted',
            'student_aware' => 'boolean',
            'status' => RequestStatus::class,
            'priority' => RequestPriority::class,
            'triage_notes' => 'encrypted',
            'triaged_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function triager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triaged_by');
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Fila de triagem: prioritárias primeiro, depois por ordem de chegada.
     *
     * @param  Builder<SupportRequest>  $query
     */
    public function scopeQueue(Builder $query): void
    {
        $query->where('status', RequestStatus::PendingTriage)
            ->orderByRaw('case when priority = ? then 0 else 1 end', [RequestPriority::High->value])
            ->orderBy('created_at');
    }

    /**
     * @return list<string>
     */
    public function reasonLabels(): array
    {
        return array_values(array_filter(array_map(
            fn (string $value): ?string => RequestReason::tryFrom($value)?->label(),
            $this->reasons ?? [],
        )));
    }

    /**
     * O parecer da triagem é visível apenas para quem o registrou (RN-012).
     */
    public function canSeeTriageNotes(User $user): bool
    {
        return $this->triaged_by === $user->id;
    }

    /**
     * Registra quem/quando e a mudança de situação, nunca o conteúdo sensível.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('triage')
            ->logOnly(['status', 'priority', 'assigned_to'])
            ->logOnlyDirty();
    }
}
