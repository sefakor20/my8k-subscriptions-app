<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\NotificationCategory;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Notifications extends Component
{
    /** @var array<string, bool> */
    public array $preferences = [];

    public function mount(NotificationService $notificationService): void
    {
        $user = Auth::user();
        $userPreferences = $notificationService->getUserPreferences($user);

        foreach ($userPreferences as $pref) {
            $this->preferences[$pref['category']->value] = $pref['is_enabled'];
        }
    }

    public function togglePreference(string $categoryValue, NotificationService $notificationService): void
    {
        $category = NotificationCategory::from($categoryValue);

        if (! $category->isOptional()) {
            return;
        }

        $newValue = ! ($this->preferences[$categoryValue] ?? true);
        $this->preferences[$categoryValue] = $newValue;

        $notificationService->updatePreference(
            Auth::user(),
            $category,
            $newValue,
        );

        $this->dispatch('preference-updated');
    }

    public function render()
    {
        return view('livewire.settings.notifications', [
            'categories' => NotificationCategory::configurable(),
            'criticalCategory' => NotificationCategory::Critical,
        ])->layout('components.layouts.app');
    }
}
