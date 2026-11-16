<?php

namespace App\Models;

use App\Enums\Attendance\AttendanceModality;
use App\Enums\Attendance\AttendanceStatus;
use App\Enums\Attendance\AttendanceType;
use App\Models\Contracts\SensitiveRecord;
use Carbon\CarbonImmutable;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Agendamento/atendimento do NAE (SRS §10.2–10.5, RN-007, RN-008).
 *
 * O conteúdo do registro (demanda, condução, combinados) é sigiloso: só o
 * profissional responsável o vê, sem bypass do administrador (RN-012).
 *
 * @property int $id
 * @property int $student_id
 * @property int|null $support_request_id
 * @property int $professional_id
 * @property int $scheduled_by
 * @property AttendanceType $type
 * @property AttendanceStatus $status
 * @property AttendanceModality $modality
 * @property bool $is_first_conversation
 * @property CarbonImmutable $scheduled_at
 * @property int $planned_minutes
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $ended_at
 * @property string|null $demand
 * @property string|null $conduct
 * @property string|null $agreements
 * @property CarbonImmutable|null $next_return_on
 * @property CarbonImmutable|null $registered_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Student $student
 * @property-read User $professional
 * @property-read SupportRequest|null $supportRequest
 */
#[Fillable([
    'type', 'modality', 'is_first_conversation', 'scheduled_at', 'planned_minutes',
    'started_at', 'ended_at', 'demand', 'conduct', 'agreements', 'next_return_on',
])]
class Attendance extends Model implements SensitiveRecord
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AttendanceType::class,
            'status' => AttendanceStatus::class,
            'modality' => AttendanceModality::class,
            'is_first_conversation' => 'boolean',
            'scheduled_at' => 'immutable_datetime',
            'planned_minutes' => 'integer',
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
            'demand' => 'encrypted',
            'conduct' => 'encrypted',
            'agreements' => 'encrypted',
            'next_return_on' => 'immutable_date',
            'registered_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
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
    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    /**
     * @return BelongsTo<SupportRequest, $this>
     */
    public function supportRequest(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class);
    }

    public function ownerId(): int
    {
        return $this->professional_id;
    }

    /**
     * Duração efetiva em minutos (RF-028), quando início e término foram informados.
     */
    public function durationMinutes(): ?int
    {
        if ($this->started_at === null || $this->ended_at === null) {
            return null;
        }

        return (int) $this->started_at->diffInMinutes($this->ended_at);
    }

    /**
     * @param  Builder<Attendance>  $query
     */
    public function scopeBetween(Builder $query, CarbonImmutable $from, CarbonImmutable $to): void
    {
        $query->where('scheduled_at', '>=', $from)->where('scheduled_at', '<', $to);
    }

    /**
     * @param  Builder<Attendance>  $query
     */
    public function scopeNotCancelled(Builder $query): void
    {
        $query->where('status', '!=', AttendanceStatus::Cancelled);
    }

    /**
     * @param  Builder<Attendance>  $query
     */
    public function scopeRegistered(Builder $query): void
    {
        $query->whereIn('status', [AttendanceStatus::Attended, AttendanceStatus::Completed, AttendanceStatus::Referred]);
    }

    /**
     * Registra agenda e mudanças de status, nunca o conteúdo sigiloso.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('attendances')
            ->logOnly(['status', 'type', 'scheduled_at', 'professional_id'])
            ->logOnlyDirty();
    }
}
