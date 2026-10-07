<?php

namespace Modules\Careers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Mail;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Careers\Database\Factories\ApplicantFactory;
use Modules\Careers\Mail\ApplicationStatusChanged;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A job application for one career: Pending → Reviewed → Accepted or Rejected. The CV is kept on the private disk
 * (personal data): it has no public link and is downloaded from the admin only.
 */
class Applicant extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes, Userstamps;

    public const RESUME = 'resume';

    public const RESUME_DISK = 'local';

    public const PENDING = 'pending';

    public const REVIEWED = 'reviewed';

    public const ACCEPTED = 'accepted';

    public const REJECTED = 'rejected';

    public const STATUSES = [self::PENDING, self::REVIEWED, self::ACCEPTED, self::REJECTED];

    public const GENDERS = ['male', 'female'];

    protected $fillable = [
        'career_id',
        'name',
        'email',
        'phone',
        'status',
        'gender',
        'linkedin_profile',
        'cover_letter',
    ];

    protected $attributes = [
        'status' => self::PENDING,
    ];

    protected static function booted(): void
    {
        // HR changed the status (one by one or in bulk): tell the applicant. Dastan meant to, but checked isDirty()
        // inside "updated", which is always false there, so its e-mail was never sent. Back to "pending" isn't news.
        static::updated(function (Applicant $applicant) {
            if ($applicant->wasChanged('status') && $applicant->status !== self::PENDING) {
                Mail::to($applicant->email)->queue(new ApplicationStatusChanged($applicant));
            }
        });
    }

    protected static function newFactory(): ApplicantFactory
    {
        return ApplicantFactory::new();
    }

    public function career(): BelongsTo
    {
        return $this->belongsTo(Career::class)->withTrashed();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::RESUME)->useDisk(self::RESUME_DISK)->singleFile();
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return collect(self::STATUSES)->mapWithKeys(fn (string $status) => [$status => __("admin.applicants.status_options.{$status}")])->all();
    }

    /**
     * @return array<string, string>
     */
    public static function genderOptions(): array
    {
        return collect(self::GENDERS)->mapWithKeys(fn (string $gender) => [$gender => __("admin.applicants.gender_options.{$gender}")])->all();
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            self::PENDING => 'warning',
            self::REVIEWED => 'info',
            self::ACCEPTED => 'success',
            self::REJECTED => 'danger',
            default => 'gray',
        };
    }

    /**
     * The statuses an application can move to the given one from (used by the bulk actions): an accepted or
     * rejected application is a decision, so bulk actions don't change it (one by one they can).
     *
     * @return array<string>
     */
    public static function statusesBefore(string $status): array
    {
        return match ($status) {
            self::REVIEWED => [self::PENDING],
            self::ACCEPTED, self::REJECTED => [self::PENDING, self::REVIEWED],
            default => [],
        };
    }
}
