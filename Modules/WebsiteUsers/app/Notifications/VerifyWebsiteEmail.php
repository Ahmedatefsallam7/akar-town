<?php

namespace Modules\WebsiteUsers\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Laravel's "verify your e-mail" message, with a signed link (60 minutes) to the website's verify route (by e-mail, no database id).
 */
class VerifyWebsiteEmail extends VerifyEmail
{
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute('account.verify', Carbon::now()->addMinutes(60), [
            'email' => $notifiable->getEmailForVerification(),
        ]);
    }
}
