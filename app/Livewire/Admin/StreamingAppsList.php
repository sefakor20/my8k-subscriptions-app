<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\StreamingAppPlatform;
use App\Enums\StreamingAppType;
use App\Models\StreamingApp;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class StreamingAppsList extends Component
{
    use WithPagination;

    public ?bool $activeFilter = null;

    public string $search = '';

    public string $platformFilter = '';

    public string $typeFilter = '';

    #[Computed]
    public function apps(): LengthAwarePaginator
    {
        $query = StreamingApp::query()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($this->activeFilter !== null) {
            $query->where('is_active', $this->activeFilter);
        }

        if ($this->platformFilter !== '') {
            $query->where('platform', $this->platformFilter);
        }

        if ($this->typeFilter !== '') {
            $query->where('type', $this->typeFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        return $query->paginate(15);
    }

    #[Computed]
    public function platforms(): array
    {
        return StreamingAppPlatform::cases();
    }

    #[Computed]
    public function types(): array
    {
        return StreamingAppType::cases();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPlatformFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function createApp(): void
    {
        $this->dispatch('open-streaming-app-form-modal', mode: 'create');
    }

    public function editApp(string $appId): void
    {
        $this->dispatch('open-streaming-app-form-modal', mode: 'edit', appId: $appId);
    }

    public function toggleActive(string $appId): void
    {
        $app = StreamingApp::find($appId);

        if ($app) {
            $app->update(['is_active' => ! $app->is_active]);
            unset($this->apps);
            session()->flash('success', 'App status updated successfully.');
        }
    }

    public function toggleRecommended(string $appId): void
    {
        $app = StreamingApp::find($appId);

        if ($app) {
            $app->update(['is_recommended' => ! $app->is_recommended]);
            unset($this->apps);
            session()->flash('success', 'App recommendation status updated.');
        }
    }

    public function deleteApp(string $appId): void
    {
        $app = StreamingApp::find($appId);

        if (! $app) {
            session()->flash('error', 'App not found.');

            return;
        }

        $app->delete();
        unset($this->apps);
        session()->flash('success', 'App deleted successfully.');
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->activeFilter = null;
        $this->platformFilter = '';
        $this->typeFilter = '';
        $this->resetPage();
        unset($this->apps);
    }

    #[On('streaming-app-saved')]
    public function refreshApps(string $message = ''): void
    {
        unset($this->apps);
        if ($message !== '') {
            session()->flash('success', $message);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.streaming-apps-list')
            ->layout('components.layouts.app');
    }
}
