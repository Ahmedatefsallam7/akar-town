<?php

namespace Modules\WebsiteUsers\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

/**
 * Laravel's "reset your password" e-mail, with the link to the website's page (not the admin's).
 */
class ResetWebsitePassword extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return route('account.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
    }
}
