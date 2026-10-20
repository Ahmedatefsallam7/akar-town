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
use Modules\Lookups\Models\Lookup;
use Modules\PossibleLeads\Support\Visitor;
use Modules\Projects\Models\Project;

/**
 * The project page's "Send an Enquiry" form (the design's #projContactForm): saved as a Contact Us message (form
 * "project_enquiry") naming the project, unit type and budget, which sends it to the CRM and converts the visitor's
 * earlier clicks (Possible Leads).
 */
class ProjectEnquiry extends Component
{
    use FillsContactFromAccount;

    /** The design's budget ranges: key => English label (sent to the sales team; shown translated). */
    public const BUDGETS = [
        '2-5' => '2 – 5 Million EGP',
        '5-10' => '5 – 10 Million EGP',
        '10-20' => '10 – 20 Million EGP',
        '20+' => '20 Million EGP+',
    ];

    /** Sends allowed per visitor (IP) per hour. */
    private const MAX_SENDS = 5;

    #[Locked]
    public int $projectId;

    /** The project's unit types: [id, name, area range] (from the page, so no query here). */
    #[Locked]
    public array $types = [];

    public string $fullName = '';

    public string $phone = '';

    public string $email = '';

    public string $type = '';

    public string $budget = '';

    public string $message = '';

    public bool $sent = false;

    /** The page the form was sent from (Livewire's own requests have another URL). */
    #[Locked]
    public string $url = '';

    public function mount(int $projectId, array $types = []): void
    {
        $this->projectId = $projectId;
        $this->types = array_map(fn (array $type) => [$type['id'], $type['name'], $type['area']], $types);
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
            'type' => ['nullable', Rule::in(array_map(fn (array $type) => (string) $type[0], $this->types))],
            'budget' => ['nullable', Rule::in(array_keys(self::BUDGETS))],
            'message' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'fullName' => __('Full name'),
            'phone' => __('Phone'),
            'email' => __('Email'),
            'type' => __('Unit Type'),
            'budget' => __('Budget Range'),
            'message' => __('Message'),
        ]);

        $key = 'project-enquiry:'.request()->ip();
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
            'form' => 'project_enquiry',
            'source' => 'Project page',
            'url' => $this->url,
            'visitor_id' => Visitor::id(request()),
            'website_user_id' => auth('website')->id(),
        ]);

        $this->reset('fullName', 'phone', 'email', 'type', 'budget', 'message');
        $this->fillContactFromAccount();
        $this->sent = true;
    }

    /**
     * The enquiry in English for the sales team (admin and CRM): project, unit type, budget, then the visitor's message.
     */
    private function summary(): string
    {
        $project = Project::query()->whereKey($this->projectId)->value('title_en');
        $type = filled($this->type) ? Lookup::query()->whereKey((int) $this->type)->value('name_en') : null;

        return collect([
            "Project: {$project}",
            $type ? "Unit type: {$type}" : null,
            filled($this->budget) ? 'Budget: '.self::BUDGETS[$this->budget] : null,
            filled($this->message) ? "\n".trim($this->message) : null,
        ])->filter()->implode("\n");
    }

    public function render(): View
    {
        return view('livewire.website.project-enquiry');
    }
}
