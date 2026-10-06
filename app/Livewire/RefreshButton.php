<?php

namespace App\Livewire;

use App\Filament\Support\NavigationBadges;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Top bar "Refresh": rebuilds the cached sidebar and dashboard counts, then reloads the page you're on, so lists,
 * counts and your permissions are all up to date. (Everything else is read fresh on every request.)
 */
class RefreshButton extends Component
{
    public function refresh(): void
    {
        NavigationBadges::forget();

        Notification::make()
            ->success()
            ->title(__('admin.refresh.done'))
            ->send();

        // The top bar stays on screen between pages, so reload whatever page is open now.
        $this->js('Livewire.navigate(window.location.href)');
    }

    public function render(): View
    {
        return view('livewire.refresh-button');
    }
}
