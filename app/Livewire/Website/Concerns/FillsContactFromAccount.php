<?php

namespace App\Livewire\Website\Concerns;

/**
 * A website form's name, e-mail and phone, filled from the signed-in account (they can still be changed).
 */
trait FillsContactFromAccount
{
    /** Livewire runs it when the form is first shown. */
    public function mountFillsContactFromAccount(): void
    {
        $this->fillContactFromAccount();
    }

    protected function fillContactFromAccount(): void
    {
        if ($user = auth('website')->user()) {
            $this->fullName = (string) $user->name;
            $this->email = (string) $user->email;
            $this->phone = (string) $user->phone;
        }
    }
}
