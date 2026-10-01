<?php

namespace Modules\Users\Filament\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * Profile page for the logged-in user. Lives outside Filament/Pages so module discovery does not add it to the sidebar.
 */
class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label(__('admin.users.first_name'))
                    ->required()
                    ->maxLength(191)
                    ->autofocus(),
                TextInput::make('last_name')
                    ->label(__('admin.users.last_name'))
                    ->required()
                    ->maxLength(191),
                TextInput::make('username')
                    ->label(__('admin.users.username'))
                    ->unique(ignoreRecord: true)
                    ->maxLength(191),
                $this->getEmailFormComponent(),
                TextInput::make('phone')
                    ->label(__('admin.users.phone'))
                    ->tel()
                    ->unique(ignoreRecord: true)
                    ->maxLength(191)
                    ->regex('/^\+?[0-9]{7,15}$/')
                    ->helperText(__('admin.users.phone_format')),
                // Filament's fields: changing the password or email asks for the current password.
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }
}
