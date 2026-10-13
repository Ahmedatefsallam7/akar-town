<?php

namespace Modules\PossibleLeads\Filament\Resources\PossibleLeadResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\PossibleLeads\Filament\Resources\PossibleLeadResource;
use Modules\PossibleLeads\Filament\Widgets\PossibleLeadsStats;

class ListPossibleLeads extends ListRecords
{
    protected static string $resource = PossibleLeadResource::class;

    public function getTitle(): string
    {
        return __('admin.possible_leads.title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.possible_leads.subheading');
    }

    protected function getHeaderWidgets(): array
    {
        return [PossibleLeadsStats::class];
    }
}
