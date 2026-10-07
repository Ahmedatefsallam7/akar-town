<?php

namespace Modules\Careers\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Traits\HasLocalization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Careers\Database\Factories\CareerFactory;
use Modules\Lookups\Models\Concerns\HasLookups;

/**
 * A job opening. It shows on the website while it is published and its closing date hasn't passed (it hides by
 * itself the day after). The salary shows only when "Show Salary" is on.
 */
class Career extends Model
{
    use HasFactory, HasLocalization, HasLookups, HasUniqueSlug, SoftDeletes, Userstamps;

    public const TYPES = ['full_time', 'part_time'];

    public const CURRENCIES = ['EGP', 'USD', 'EUR', 'GBP', 'SAR', 'AED'];

    protected $fillable = [
        'title_en', 'title_ar', 'slug', 'type', 'vacancies', 'department_id', 'closing_date', 'is_active',
        'country_id', 'city_id', 'region_id', 'area_id', 'sub_area_id',
        'description_en', 'description_ar', 'responsibilities', 'requirements',
        'show_salary', 'salary_from', 'salary_to', 'salary_currency',
        'meta_title_en', 'meta_title_ar', 'meta_description_en', 'meta_description_ar', 'meta_keywords_en', 'meta_keywords_ar',
    ];

    protected $casts = [
        'vacancies' => 'integer',
        'closing_date' => 'date',
        'is_active' => 'boolean',
        'responsibilities' => 'array',
        'requirements' => 'array',
        'show_salary' => 'boolean',
        'salary_from' => 'integer',
        'salary_to' => 'integer',
        'meta_keywords_en' => 'array',
        'meta_keywords_ar' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'show_salary' => false,
    ];

    public static function slugSource(): string
    {
        return 'title_en';
    }

    protected static function newFactory(): CareerFactory
    {
        return CareerFactory::new();
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }

    /**
     * Whether anyone applied (also applications in the trash): such a job can't be removed for good.
     */
    public function hasApplicants(): bool
    {
        return array_key_exists('all_applicants_count', $this->attributes)
            ? $this->attributes['all_applicants_count'] > 0
            : $this->applicants()->withTrashed()->exists();
    }

    /**
     * Jobs the website shows: published and not past their closing date (open through the closing day).
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('closing_date')->orWhereDate('closing_date', '>=', today()));
    }

    public function isOpen(): bool
    {
        return $this->is_active && ($this->closing_date === null || $this->closing_date->gte(today()));
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return collect(self::TYPES)->mapWithKeys(fn (string $type) => [$type => __("admin.careers.type_options.{$type}")])->all();
    }

    /**
     * @return array<string, string>
     */
    public static function currencyOptions(): array
    {
        return array_combine(self::CURRENCIES, self::CURRENCIES);
    }
}
