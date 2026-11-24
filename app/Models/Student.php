<?php

namespace App\Models;

use App\Enums\AccessibilityResource;
use App\Enums\FollowUp\FollowUpClosureReason;
use App\Enums\FollowUp\FollowUpStatus;
use App\Enums\Modality;
use App\Enums\Sex;
use App\Enums\Shift;
use App\Enums\Triage\RequestStatus;
use Carbon\CarbonImmutable;
use Database\Factories\StudentFactory;
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
 * Estudante atendido pelo NAE (SRS §9). Contém apenas dados cadastrais
 * comuns; CID e documentos ficam em tabelas próprias (dados sensíveis).
 *
 * Nunca é excluído definitivamente (RN-013): "inativar" = soft delete.
 *
 * @property int $id
 * @property string $name
 * @property string|null $social_name
 * @property string $registration
 * @property string $course
 * @property int $period
 * @property Shift|null $shift
 * @property Modality $modality
 * @property string|null $email
 * @property string|null $phone
 * @property CarbonImmutable|null $birth_date
 * @property Sex|null $sex
 * @property string|null $guardian_name
 * @property string|null $guardian_phone
 * @property string|null $guardian_relationship
 * @property bool $needs_accessibility
 * @property list<string>|null $accessibility_resources
 * @property bool $has_external_follow_up
 * @property CarbonImmutable|null $consent_at
 * @property CarbonImmutable|null $follow_up_closed_at
 * @property int|null $follow_up_closed_by
 * @property FollowUpClosureReason|null $follow_up_closure_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'name', 'social_name', 'registration', 'course', 'period', 'shift', 'modality',
    'email', 'phone', 'birth_date', 'sex', 'guardian_name', 'guardian_phone',
    'guardian_relationship', 'needs_accessibility', 'accessibility_resources',
    'has_external_follow_up', 'consent_at',
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
            'shift' => Shift::class,
            'modality' => Modality::class,
            'needs_accessibility' => 'boolean',
            'accessibility_resources' => 'array',
            'has_external_follow_up' => 'boolean',
            'consent_at' => 'immutable_datetime',
            'follow_up_closed_at' => 'immutable_datetime',
            'follow_up_closure_reason' => FollowUpClosureReason::class,
        ];
    }

    /**
     * @return list<string>
     */
    public function accessibilityResourceLabels(): array
    {
        return array_values(array_filter(array_map(
            fn (string $value): ?string => AccessibilityResource::tryFrom($value)?->label(),
            $this->accessibility_resources ?? [],
        )));
    }

    /**
     * @return HasMany<StudentDisability, $this>
     */
    public function disabilities(): HasMany
    {
        return $this->hasMany(StudentDisability::class);
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * @return HasMany<Referral, $this>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function followUpCloser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follow_up_closed_by');
    }

    /**
     * Situação atual do acompanhamento (RF-033), derivada dos atendimentos
     * (RN-009). Um atendimento registrado depois do encerramento reabre o
     * acompanhamento.
     */
    public function followUpStatus(): FollowUpStatus
    {
        $lastRegistered = $this->attendances()->registered()->max('registered_at');
        $lastRegistered = $lastRegistered === null ? null : CarbonImmutable::parse((string) $lastRegistered);

        if ($this->follow_up_closed_at !== null
            && ($lastRegistered === null || $lastRegistered->lessThanOrEqualTo($this->follow_up_closed_at))) {
            return FollowUpStatus::Closed;
        }

        if ($lastRegistered !== null) {
            return FollowUpStatus::Active;
        }

        $waiting = $this->supportRequests()->whereIn('status', [RequestStatus::PendingTriage, RequestStatus::Scheduled])->exists()
            || $this->attendances()->where('status', 'scheduled')->exists();

        return $waiting ? FollowUpStatus::Waiting : FollowUpStatus::None;
    }

    /**
     * @return HasMany<SupportRequest, $this>
     */
    public function supportRequests(): HasMany
    {
        return $this->hasMany(SupportRequest::class);
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
