<x-mail::message>
{{ __('admin.applicants.mail.greeting', ['name' => $applicant->name], 'en') }}

{{ __('admin.applicants.mail.body', ['job' => $applicant->career?->title_en, 'status' => __("admin.applicants.status_options.{$status}", [], 'en')], 'en') }}

{{ __("admin.applicants.mail.next.{$status}", [], 'en') }}

<div dir="rtl" style="text-align: right">

{{ __('admin.applicants.mail.greeting', ['name' => $applicant->name], 'ar') }}

{{ __('admin.applicants.mail.body', ['job' => $applicant->career?->title_ar, 'status' => __("admin.applicants.status_options.{$status}", [], 'ar')], 'ar') }}

{{ __("admin.applicants.mail.next.{$status}", [], 'ar') }}

</div>

{{ __(config('app.name'), [], 'en') }} | {{ __(config('app.name'), [], 'ar') }}
</x-mail::message>
