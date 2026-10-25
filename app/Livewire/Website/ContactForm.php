<?php

namespace App\Livewire\Website;

use App\Livewire\Website\Concerns\FillsContactFromAccount;
use App\Support\Crm\LeadContact;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Inquiry\Models\Inquiry;
use Modules\PossibleLeads\Support\Visitor;

/**
 * The contact page's "Send Us a Message" form (the design's #contactForm): saved as a Contact Us message (form
 * "contact_us") with what the visitor is interested in, which sends it to the CRM and converts the visitor's earlier
 * clicks (Possible Leads).
 */
class ContactForm extends Component
{
    use FillsContactFromAccount;

    /** The design's "I'm Interested In" options: key => English label (sent to the sales team; shown translated). */
    public const INTERESTS = [
        'buy' => 'Buying a Property',
        'invest' => 'Investment Opportunities',
        'sell' => 'Listing / Selling',
        'rent' => 'Renting',
        'consult' => 'General Consultation',
    ];

    /** Sends allowed per visitor (IP) per hour. */
    private const MAX_SENDS = 5;

    public string $fullName = '';

    public string $phone = '';

    public string $email = '';

    public string $interest = '';

    public string $message = '';

    public bool $sent = false;

    /** The page the form was sent from (Livewire's own requests have another URL). */
    #[Locked]
    public string $url = '';

    public function mount(): void
    {
        $this->url = url()->current();
    }

    public function send(): void
    {
        // "0100 123-4567" and "01001234567" are one number: separators go before the checks and the save.
        $this->phone = preg_replace('/[\s\-.\/()]+/', '', $this->phone);

        $this->validate([
            'fullName' => ['required', 'string', 'max:191'],
            // As strict as the CRM (see LeadContact), so a request that passes here isn't refused there.
            'phone' => ['required', 'string', 'max:30', ...LeadContact::phoneRules()],
            'email' => ['required', 'max:191', ...LeadContact::emailRules()],
            'interest' => ['nullable', Rule::in(array_keys(self::INTERESTS))],
            'message' => ['required', 'string', 'max:2000'],
        ], attributes: [
            'fullName' => __('Full name'),
            'phone' => __('Phone'),
            'email' => __('Email'),
            'interest' => __("I'm Interested In"),
            'message' => __('Message'),
        ]);

        $key = 'contact-form:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_SENDS)) {
            $this->addError('email', __('Too many requests. Please try again later.'));

            return;
        }
        RateLimiter::hit($key, 3600);

        Inquiry::create([
            'full_name' => $this->fullName,
            'phone' => $this->phone,
            'email' => $this->email,
            'message' => $this->summary(),
            'form' => 'contact_us',
            'source' => 'Contact page',
            'url' => $this->url,
            'visitor_id' => Visitor::id(request()),
            'website_user_id' => auth('website')->id(),
        ]);

        $this->reset('fullName', 'phone', 'email', 'interest', 'message');
        $this->fillContactFromAccount();
        $this->sent = true;
    }

    /**
     * The message in English for the sales team (admin and CRM): what the visitor is interested in, then their words.
     */
    private function summary(): string
    {
        return collect([
            filled($this->interest) ? 'Interested in: '.self::INTERESTS[$this->interest]."\n" : null,
            trim($this->message),
        ])->filter()->implode("\n");
    }

    public function render(): View
    {
        return view('livewire.website.contact-form');
    }
}
