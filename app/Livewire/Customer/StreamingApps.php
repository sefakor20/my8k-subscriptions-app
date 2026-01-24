<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Enums\StreamingAppPlatform;
use App\Enums\StreamingAppType;
use App\Models\StreamingApp;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class StreamingApps extends Component
{
    public string $platformFilter = '';

    public string $typeFilter = '';

    #[Computed]
    public function apps(): Collection
    {
        $query = StreamingApp::query()
            ->active()
            ->ordered();

        if ($this->platformFilter !== '') {
            $query->where('platform', $this->platformFilter);
        }

        if ($this->typeFilter !== '') {
            $query->where('type', $this->typeFilter);
        }

        return $query->get();
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

    #[Computed]
    public function recommendedApps(): Collection
    {
        return StreamingApp::query()
            ->active()
            ->recommended()
            ->ordered()
            ->get();
    }

    public function resetFilters(): void
    {
        $this->platformFilter = '';
        $this->typeFilter = '';
        unset($this->apps);
    }

    public function render(): View
    {
        return view('livewire.customer.streaming-apps')
            ->layout('components.layouts.app');
    }
}
