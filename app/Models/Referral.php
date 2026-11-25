<?php

namespace App\Models;

use App\Enums\Referral\ReferralKind;
use App\Enums\Referral\ReferralStatus;
use App\Models\Contracts\SensitiveRecord;
use Carbon\CarbonImmutable;
use Database\Factories\ReferralFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Encaminhamento interno ou externo (SRS §12, RF-034 a RF-040).
 *
 * Motivo e retorno são sigilosos: visíveis só para quem encaminhou e, no
 * encaminhamento interno, para o profissional de destino (RN-012).
 *
 * @property int $id
 * @property int $student_id
 * @property int|null $attendance_id
 * @property int $created_by
 * @property ReferralKind $kind
 * @property int|null $destination_user_id
 * @property string|null $destination_name
 * @property string $reason
 * @property CarbonImmutable $referred_on
 * @property CarbonImmutable|null $return_deadline
 * @property ReferralStatus $status
 * @property string|null $return_notes
 * @property CarbonImmutable|null $returned_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Student $student
 * @property-read User $author
 * @property-read User|null $destinationUser
 * @property-read Attendance|null $attendance
 */
#[Fillable(['kind', 'destination_name', 'reason', 'referred_on', 'return_deadline'])]
class Referral extends Model implements SensitiveRecord
{
    /** @use HasFactory<ReferralFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ReferralKind::class,
            'status' => ReferralStatus::class,
            'reason' => 'encrypted',
            'return_notes' => 'encrypted',
            'referred_on' => 'immutable_date',
            'return_deadline' => 'immutable_date',
            'returned_at' => 'immutable_datetime',
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function destinationUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destination_user_id');
    }

    /**
     * @return BelongsTo<Attendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function ownerId(): int
    {
        return $this->created_by;
    }

    /**
     * Quem encaminhou ou, no interno, o profissional que recebeu.
     */
    public function involves(User $user): bool
    {
        return $this->created_by === $user->id || $this->destination_user_id === $user->id;
    }

    public function destinationLabel(): string
    {
        return $this->kind === ReferralKind::Internal
            ? ($this->destinationUser->name ?? 'Profissional do NAE')
            : (string) $this->destination_name;
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen()
            && $this->return_deadline !== null
            && $this->return_deadline->isPast()
            && ! $this->return_deadline->isToday();
    }

    /**
     * @param  Builder<Referral>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', ReferralStatus::open());
    }

    /**
     * Registra quem/quando e as mudanças de situação, nunca o conteúdo sigiloso.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('referrals')
            ->logOnly(['status', 'kind', 'destination_user_id', 'destination_name', 'return_deadline'])
            ->logOnlyDirty();
    }
}
