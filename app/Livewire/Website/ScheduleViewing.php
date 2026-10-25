<?php

namespace App\Livewire\Website;

use App\Livewire\Website\Concerns\FillsContactFromAccount;
use App\Support\Crm\LeadContact;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Inquiry\Models\Meeting;
use Modules\PossibleLeads\Support\Visitor;
use Modules\Units\Models\Unit;

/**
 * The property page's "Schedule a Visit / Schedule Viewing" window (Dastan's schedule-viewing form; the design only has
 * the buttons): saved as a Meeting (Pending) for that property, which sends it to the CRM and converts the visitor's
 * earlier clicks (Possible Leads). Opened by the window event "schedule-viewing".
 */
class ScheduleViewing extends Component
{
    use FillsContactFromAccount;

    /** Sends allowed per visitor (IP) per hour. */
    private const MAX_SENDS = 5;

    #[Locked]
    public int $unitId;

    /** The page the form was sent from (Livewire's own requests have another URL). */
    #[Locked]
    public string $url = '';

    public string $fullName = '';

    public string $phone = '';

    public string $email = '';

    public string $type = 'site_visit';

    public string $meetingAt = '';

    public string $notes = '';

    public bool $sent = false;

    public function mount(int $unitId): void
    {
        $this->unitId = $unitId;
        $this->url = url()->current();
    }

    /** "Book Another": an empty booking (the contact details stay). */
    public function again(): void
    {
        $this->resetValidation();
        $this->reset('type', 'meetingAt', 'notes', 'sent');
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
            'type' => ['required', Rule::in(Meeting::TYPES)],
            // From an hour from now, up to a year ahead.
            'meetingAt' => ['required', 'date', 'after:'.now()->addHour()->toDateTimeString(), 'before:'.now()->addYear()->toDateTimeString()],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'fullName' => __('Full name'),
            'phone' => __('Phone'),
            'email' => __('Email'),
            'type' => __('Meeting Type'),
            'meetingAt' => __('Preferred Date & Time'),
            'notes' => __('Message'),
        ]);

        $key = 'schedule-viewing:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_SENDS)) {
            $this->addError('meetingAt', __('Too many requests. Please try again later.'));

            return;
        }
        RateLimiter::hit($key, 3600);

        $unit = Unit::query()->whereKey($this->unitId)->value('name_en');

        Meeting::create([
            'name' => $this->fullName,
            'phone' => $this->phone,
            'email' => $this->email ?: null,
            'type' => $this->type,
            'meeting_at' => Carbon::parse($this->meetingAt),
            'notes_en' => filled($this->notes) ? trim($this->notes) : null,
            // Which property, for the admin and the CRM.
            'source' => 'Property page: '.$unit,
            'url' => $this->url,
            'visitor_id' => Visitor::id(request()),
            'website_user_id' => auth('website')->id(),
        ]);

        $this->again();
        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.website.schedule-viewing');
    }
}
