<?php

namespace Modules\Users\Filament\Forms;

use Filament\Forms\Components\Select;
use Modules\Users\Models\User;

/**
 * The "Agent" picker of units and projects: an admin user (not suspended) whose agent card shows on the website.
 */
class AgentFields
{
    public static function select(): Select
    {
        return Select::make('agent_id')
            ->label(__('admin.agents.agent'))
            ->helperText(__('admin.agents.agent_help'))
            ->relationship('agent', 'first_name', fn ($query) => $query->where('is_suspended', false))
            ->getOptionLabelFromRecordUsing(fn (User $record) => $record->getFilamentName())
            ->searchable(['first_name', 'last_name', 'email'])
            ->preload();
    }
}
