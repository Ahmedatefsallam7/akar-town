<?php

namespace Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;
use Modules\WebsiteUsers\Filament\Resources\WebsiteUserResource;

class ListWebsiteUsers extends ListRecords
{
    protected static string $resource = WebsiteUserResource::class;

    /** Filters can come from the link, e.g. the dashboard's "New Registrations" card opens this month's users. */
    #[Url]
    public ?array $tableFilters = null;

    public function getTitle(): string
    {
        return __('admin.website_users.title');
    }
}
