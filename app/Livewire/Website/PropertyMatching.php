<?php

namespace App\Livewire\Website;

use App\Support\Crm\LeadContact;
use App\Support\Website\SearchOptions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Inquiry\Models\Inquiry;
use Modules\Lookups\Models\Lookup;
use Modules\PossibleLeads\Support\Visitor;

/**
 * The footer's "Property Matching" form (the design's footer form, plus name and phone so sales can call back):
 * saved as a Contact Us message (form "property_matching") with the unit type and budget, which sends it to the CRM
 * and converts the visitor's earlier clicks (Possible Leads).
 */
class PropertyMatching extends Component
{
    /** Unit type chips shown, the first ones in the admin's Lookups order (the design shows four). */
    private const TYPES = 4;

    /** Tries allowed per visitor (IP) per hour, failed ones included: the "already sent" answers can't be used to
     * check many phones / emails. */
    private const MAX_TRIES = 10;

    public string $type = '';

    public string $budgetMin = '';

    public string $budgetMax = '';

    public string $fullName = '';

    public string $phone = '';

    public string $email = '';

    public bool $sent = false;

    /** The page the form was sent from (Livewire's own requests have another URL). */
    #[Locked]
    public string $url = '';

    public function mount(): void
    {
        $this->url = url()->current();
        $this->type = (string) (array_key_first($this->types()) ?? '');
    }

    public function send(): void
    {
        $key = 'property-matching:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_TRIES)) {
            $this->addError('email', __('Too many requests. Please try again later.'));

            return;
        }
        RateLimiter::hit($key, 3600);

        // "0100 123-4567" and "01001234567" are one number: separators go before the checks and the save.
        $this->phone = preg_replace('/[\s\-.\/()]+/', '', $this->phone);

        $this->validate([
            'type' => ['nullable', Rule::in(array_keys($this->types()))],
            'budgetMin' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'budgetMax' => ['nullable', 'integer', 'min:0', 'max:999999999999', 'gte:budgetMin'],
            'fullName' => ['required', 'string', 'max:191'],
            // As strict as the CRM (see LeadContact), so a request that passes here isn't refused there.
            'phone' => ['required', 'string', 'max:30', ...LeadContact::phoneRules(), $this->once('phone')],
            'email' => ['required', 'max:191', ...LeadContact::emailRules(), $this->once('email')],
        ], messages: [
            'phone.unique' => __('A request was already sent with this phone number.'),
            'email.unique' => __('A request was already sent with this email.'),
        ], attributes: [
            'budgetMin' => __('Minimum budget'),
            'budgetMax' => __('Maximum budget'),
            'fullName' => __('Full name'),
            'phone' => __('Phone'),
            'email' => __('Email'),
        ]);

        // Save the request as a Contact Us message (form "property_matching") with the unit type and budget, which sends it to the CRM.
        Inquiry::create([
            'full_name' => $this->fullName,
            'phone' => $this->phone,
            'email' => $this->email,
            'message' => $this->message(),
            'form' => 'property_matching',  // form name
            'source' => 'Website footer',
            'url' => $this->url,
            'visitor_id' => Visitor::id(request()), // optional, if the visitor has a cookie
            'website_user_id' => auth('website')->id(), // optional, if the user is logged in even if from different IP or device
        ]);

        $this->reset('budgetMin', 'budgetMax', 'fullName', 'phone', 'email');
        $this->sent = true;
    }

    /**
     * One matching request per phone and per email (other Contact Us forms and deleted requests don't count).
     */
    private function once(string $column): Unique
    {
        return Rule::unique('inquiries', $column)->where('form', 'property_matching')->whereNull('deleted_at');
    }

    /**
     * What the visitor is looking for, in English for the sales team (admin and CRM).
     */
    private function message(): string
    {
        $type = filled($this->type) ? Lookup::query()->whereKey(SearchOptions::ids('unittype', $this->type))->value('name_en') : null;
        $money = fn (string $value) => 'EGP '.number_format((int) $value);
        $budget = match (true) {
            filled($this->budgetMin) && filled($this->budgetMax) => $money($this->budgetMin).' – '.$money($this->budgetMax),
            filled($this->budgetMin) => 'from '.$money($this->budgetMin),
            filled($this->budgetMax) => 'up to '.$money($this->budgetMax),
            default => null,
        };

        return collect([
            'Looking for a matching unit.',
            $type ? "Unit type: {$type}" : null,
            $budget ? "Budget: {$budget}" : null,
        ])->filter()->implode("\n");
    }

    /**
     * @return array<string, string> lookup slug => name
     */
    private function types(): array
    {
        return array_slice(SearchOptions::values('unittype'), 0, self::TYPES, true);
    }

    public function render(): View
    {
        return view('livewire.website.property-matching', ['types' => $this->types()]);
    }
}
