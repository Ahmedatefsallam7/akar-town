<?php

namespace App\Livewire\Website;

use App\Livewire\Website\Concerns\FillsContactFromAccount;
use App\Support\Crm\LeadContact;
use App\Support\Website\SearchOptions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Modules\Lookups\Models\Lookup;
use Modules\Lookups\Support\LocationTree;
use Modules\PossibleLeads\Support\Visitor;
use Modules\UnitRequests\Models\UnitRequest;

/**
 * The navbar's "Submit Property" window (the design's submit-modal.js): an owner offers a unit, saved as a sell request
 * (Pending, sent to the CRM). The form is only built when the window opens (window event "open-submit-property"). The
 * place is picked from the admin's locations tree (country → city → region → area → sub-area, each list opening when
 * the level above is chosen), or, when it isn't there, typed as a full address; the compound goes in the address. The
 * photos are kept with the request, the first one as its featured image.
 */
class SubmitProperty extends Component
{
    use FillsContactFromAccount, WithFileUploads;

    /** The design's "For Sale / For Rent" => the offering type (Lookups) it is saved as. */
    public const LISTINGS = ['sale' => 'resale', 'rent' => 'rent'];

    /** Sends allowed per visitor (IP) per hour. */
    private const MAX_SENDS = 5;

    /** The form is shown (the window was opened once). */
    public bool $ready = false;

    public string $fullName = '';

    public string $phone = '';

    /** Not on the form: the signed-in account's e-mail, kept with the request. */
    public string $email = '';

    public string $type = '';

    public string $listing = 'sale';

    public string $compound = '';

    /** The picked place, one id per level of the locations tree (country … sub-area). */
    public array $place = [null, null, null, null, null];

    /** "My area isn't listed": the full address is typed instead. */
    public bool $manual = false;

    public string $fullAddress = '';

    public string $price = '';

    public string $bedrooms = '';

    public string $bathrooms = '';

    public string $unitArea = '';

    /** @var list<TemporaryUploadedFile> */
    public array $photos = [];

    public bool $sent = false;

    public function open(): void
    {
        $this->ready = true;
    }

    /** "Submit Another": an empty form (the contact details stay). */
    public function again(): void
    {
        $this->resetValidation();
        $this->reset('type', 'listing', 'compound', 'place', 'manual', 'fullAddress', 'price', 'bedrooms', 'bathrooms', 'unitArea', 'photos', 'sent');
    }

    /** A level changed: the levels under it are chosen again. */
    public function updatedPlace(mixed $value, string $level): void
    {
        foreach (array_keys($this->place) as $i) {
            if ($i > (int) $level) {
                $this->place[$i] = null;
            }
        }
    }

    /**
     * The tree's lists to show: the countries, then the children of each chosen level (until one has none).
     *
     * @return array<int, array<int, string>> level => id => name
     */
    private function levels(): array
    {
        $levels = [];
        foreach (array_keys($this->place) as $i) {
            $parent = $i === 0 ? null : (int) $this->place[$i - 1];
            if ($i > 0 && ! $parent) {
                break;
            }
            $options = LocationTree::options($parent);
            if (! $options) {
                break;
            }
            $levels[$i] = $options;
        }

        return $levels;
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function send(): void
    {
        // "0100 123-4567" and "01001234567" are one number: separators go before the checks and the save.
        $this->phone = preg_replace('/[\s\-.\/()]+/', '', $this->phone);

        $this->validate([
            'fullName' => ['required', 'string', 'max:191'],
            // As strict as the CRM (see LeadContact), so a request that passes here isn't refused there.
            'phone' => ['required', 'string', 'max:30', ...LeadContact::phoneRules()],
            'email' => ['nullable', 'max:191', ...LeadContact::emailRules()],
            'type' => ['required', Rule::in(array_keys(SearchOptions::values('unittype')))],
            'listing' => ['required', Rule::in(array_keys(self::LISTINGS))],
            // Compound + full address share the address column (191).
            'compound' => ['nullable', 'string', 'max:60'],
            'fullAddress' => [Rule::requiredIf($this->manual), 'nullable', 'string', 'max:120'],
            'price' => ['required', 'integer', 'min:1', 'max:100000000000'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'unitArea' => ['required', 'integer', 'min:1', 'max:1000000'],
            'photos' => ['array', 'max:'.UnitRequest::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], attributes: [
            'fullName' => __('Full name'),
            'phone' => __('Phone'),
            'type' => __('Property Type'),
            'listing' => __('Listing Type'),
            'compound' => __('Area / Compound'),
            'fullAddress' => __('Full address'),
            'place.0' => __('Country'),
            'place.1' => __('City'),
            'price' => __('Price'),
            'bedrooms' => __('Bedrooms'),
            'bathrooms' => __('Bathrooms'),
            'unitArea' => __('Unit Area'),
            'photos' => __('Photos'),
            'photos.*' => __('Photos'),
        ]);

        // From the tree: at least the country and, when it has cities, the city; each value under the one above it.
        $place = [];
        if (! $this->manual) {
            $levels = $this->levels();
            foreach ($levels as $i => $options) {
                $id = (int) $this->place[$i];
                if ($id && ! isset($options[$id])) {
                    $this->place[$i] = null;
                    $id = 0;
                }
                if (! $id && $i < 2) {
                    $this->addError("place.{$i}", __('validation.required', ['attribute' => $i === 0 ? __('Country') : __('City')]));

                    return;
                }
                if ($id) {
                    $place[LocationTree::LEVELS[$i]] = $id;
                }
            }
            if (! $place) {
                $this->addError('place.0', __('validation.required', ['attribute' => __('Country')]));

                return;
            }
        }

        $key = 'submit-property:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_SENDS)) {
            $this->addError('photos', __('Too many requests. Please try again later.'));

            return;
        }
        RateLimiter::hit($key, 3600);

        $where = $place ? Lookup::query()->find(end($place), ['id', 'name_en', 'name_ar']) : null;
        $type = Lookup::query()->whereKey(SearchOptions::ids('unittype', $this->type))->firstOrFail();
        $offering = Lookup::query()
            ->where('slug', self::LISTINGS[$this->listing])
            ->whereHas('parent', fn ($query) => $query->where('slug', 'offeringtype'))
            ->first();

        $request = UnitRequest::create([
            'seller_name' => $this->fullName,
            'phone' => $this->phone,
            'email' => $this->email ?: null,
            // A title for the admin list, in both languages: "Villa for Sale in New Cairo".
            'name_en' => $this->title($type, $where, 'en'),
            'name_ar' => $this->title($type, $where, 'ar'),
            ...$place,
            'address' => collect([trim($this->compound), $this->manual ? trim($this->fullAddress) : null])->filter()->implode(' — ') ?: null,
            'price' => (int) $this->price,
            'area' => (int) $this->unitArea,
            'bedrooms' => $this->bedrooms === '' ? null : (int) $this->bedrooms,
            'bathrooms' => $this->bathrooms === '' ? null : (int) $this->bathrooms,
            'visitor_id' => Visitor::id(request()),
            'website_user_id' => auth('website')->id(),
        ]);

        $request->lookupsOfType('unittype')->sync([$type->id]);
        if ($offering) {
            $request->lookupsOfType('offeringtype')->sync([$offering->id]);
        }

        foreach ($this->photos as $i => $photo) {
            // A random name with the file's real extension, never the uploader's (as the admin's uploads).
            $name = Str::ulid().'.'.($photo->guessExtension() ?? 'jpg');
            if ($i === 0) {
                $request->addMedia($photo->getRealPath())->preservingOriginal()->usingFileName($name)->toMediaCollection(UnitRequest::FEATURED_IMAGE);
            }
            $request->addMedia($photo)->usingFileName($name)->toMediaCollection(UnitRequest::PHOTOS);
        }

        $this->again();
        $this->sent = true;
    }

    private function title(Lookup $type, ?Lookup $place, string $locale): string
    {
        $name = fn (Lookup $lookup) => $lookup->{"name_{$locale}"} ?: $lookup->name_en;
        $rent = $this->listing === 'rent';

        // A typed address has no place name: "Villa for Sale".
        return $place
            ? __($rent ? ':type for Rent in :place' : ':type for Sale in :place', ['type' => $name($type), 'place' => $name($place)], $locale)
            : __($rent ? ':type for Rent' : ':type for Sale', ['type' => $name($type)], $locale);
    }

    public function render(): View
    {
        $settings = app(\Modules\GeneralSettings\Settings\SubmitPropertySettings::class);
        $locale = app()->getLocale();

        return view('livewire.website.submit-property', [
            'types' => $this->ready ? SearchOptions::values('unittype') : [],
            'levels' => $this->ready && ! $this->manual ? $this->levels() : [],
            'intro' => $settings->{"why_list_mini_description_{$locale}"},
            'points' => array_values(array_filter($settings->points, fn (array $point) => filled($point["header_{$locale}"] ?? null))),
        ]);
    }
}
