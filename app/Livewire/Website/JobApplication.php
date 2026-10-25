<?php

namespace App\Livewire\Website;

use App\Livewire\Website\Concerns\FillsContactFromAccount;
use App\Support\Crm\LeadContact;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Modules\Careers\Models\Applicant;
use Modules\Careers\Models\Career;

/**
 * The careers page's "Apply Now" window (the design's #applyModal): saved as an Applicant of the chosen job (status
 * Pending) with the CV on the private disk, as in the admin. The years of experience go before the cover note (the
 * applicants table has no column for them).
 */
class JobApplication extends Component
{
    use FillsContactFromAccount, WithFileUploads;

    /** Applications allowed per visitor (IP) per hour. */
    private const MAX_SENDS = 5;

    public ?int $careerId = null;

    public string $fullName = '';

    public string $phone = '';

    public string $email = '';

    public string $experience = '';

    public string $linkedin = '';

    public string $gender = '';

    /** @var TemporaryUploadedFile|null */
    public $resume = null;

    public string $note = '';

    public bool $sent = false;

    /**
     * An "Apply Now" button was clicked (the window opens on the event "apply-job"): a fresh form for that job.
     */
    public function choose(int $id): void
    {
        $this->resetValidation();
        $this->reset('experience', 'linkedin', 'gender', 'resume', 'note', 'sent');
        $this->careerId = $id;
    }

    public function send(): void
    {
        // "0100 123-4567" and "01001234567" are one number: separators go before the checks and the save.
        $this->phone = preg_replace('/[\s\-.\/()]+/', '', $this->phone);

        $this->validate([
            'fullName' => ['required', 'string', 'max:191'],
            'phone' => ['required', 'string', 'max:30', ...LeadContact::phoneRules()],
            'email' => ['required', 'max:191', ...LeadContact::emailRules()],
            'experience' => ['nullable', 'string', 'max:191'],
            // As in the admin: a web link only (no javascript: or other schemes).
            'linkedin' => ['nullable', 'url:http,https', 'max:500'],
            'gender' => ['nullable', Rule::in(Applicant::GENDERS)],
            // As in the admin: PDF or Word, 5 MB at most.
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'note' => ['nullable', 'string', 'max:3000'],
        ], attributes: [
            'fullName' => __('Full name'),
            'phone' => __('Phone'),
            'email' => __('Email'),
            'experience' => __('Years of Experience'),
            'linkedin' => __('LinkedIn Profile'),
            'gender' => __('Gender'),
            'resume' => __('Resume / CV'),
            'note' => __('Cover Note'),
        ]);

        // Only a job that is still open (the id comes from the browser).
        $career = Career::query()->open()->find($this->careerId);
        if (! $career) {
            $this->addError('resume', __('This job is no longer open.'));

            return;
        }

        $key = 'job-application:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_SENDS)) {
            $this->addError('resume', __('Too many requests. Please try again later.'));

            return;
        }
        RateLimiter::hit($key, 3600);

        $applicant = Applicant::create([
            'career_id' => $career->id,
            'name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'linkedin_profile' => $this->linkedin ?: null,
            'gender' => $this->gender ?: null,
            'cover_letter' => $this->coverLetter(),
        ]);
        $applicant->addMedia($this->resume)->toMediaCollection(Applicant::RESUME);

        $this->reset('fullName', 'phone', 'email', 'experience', 'linkedin', 'gender', 'resume', 'note');
        $this->fillContactFromAccount();
        $this->sent = true;
    }

    /**
     * The cover note for HR: the years of experience first, then the visitor's note.
     */
    private function coverLetter(): ?string
    {
        return collect([
            filled($this->experience) ? 'Experience: '.trim($this->experience)."\n" : null,
            filled($this->note) ? trim($this->note) : null,
        ])->filter()->implode("\n") ?: null;
    }

    public function render(): View
    {
        return view('livewire.website.job-application');
    }
}
