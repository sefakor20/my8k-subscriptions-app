<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\StreamingAppPlatform;
use App\Enums\StreamingAppType;
use App\Models\StreamingApp;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class StreamingAppFormModal extends Component
{
    public bool $show = false;

    public string $mode = 'create';

    public ?string $appId = null;

    // Form fields
    public string $name = '';

    public string $description = '';

    public string $type = 'm3u';

    public string $platform = 'android';

    public string $version = '';

    public string $download_url = '';

    public string $downloader_code = '';

    public string $short_url = '';

    public bool $is_recommended = false;

    public bool $is_active = true;

    public string $sort_order = '0';

    #[On('open-streaming-app-form-modal')]
    public function openModal(string $mode, ?string $appId = null): void
    {
        $this->mode = $mode;
        $this->appId = $appId;

        if ($mode === 'edit' && $appId) {
            $this->loadApp($appId);
        } else {
            $this->resetForm();
        }

        $this->show = true;
    }

    protected function loadApp(string $appId): void
    {
        $app = StreamingApp::find($appId);

        if ($app) {
            $this->name = $app->name;
            $this->description = $app->description ?? '';
            $this->type = $app->type->value;
            $this->platform = $app->platform->value;
            $this->version = $app->version ?? '';
            $this->download_url = $app->download_url;
            $this->downloader_code = $app->downloader_code ?? '';
            $this->short_url = $app->short_url ?? '';
            $this->is_recommended = $app->is_recommended;
            $this->is_active = $app->is_active;
            $this->sort_order = (string) $app->sort_order;
        }
    }

    public function save(): void
    {
        $data = $this->validateAppData();

        try {
            if ($this->mode === 'create') {
                StreamingApp::create($data);
                $this->dispatch('streaming-app-saved', message: 'Streaming app created successfully.');
            } else {
                $app = StreamingApp::find($this->appId);
                if ($app) {
                    $app->update($data);
                    $this->dispatch('streaming-app-saved', message: 'Streaming app updated successfully.');
                }
            }

            $this->closeModal();
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateAppData(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', Rule::enum(StreamingAppType::class)],
            'platform' => ['required', Rule::enum(StreamingAppPlatform::class)],
            'version' => 'nullable|string|max:50',
            'download_url' => 'required|url|max:500',
            'downloader_code' => 'nullable|string|max:50',
            'short_url' => 'nullable|string|max:100',
            'is_recommended' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'required|integer|min:0',
        ];

        $validator = Validator::make([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'type' => $this->type,
            'platform' => $this->platform,
            'version' => $this->version ?: null,
            'download_url' => $this->download_url,
            'downloader_code' => $this->downloader_code ?: null,
            'short_url' => $this->short_url ?: null,
            'is_recommended' => $this->is_recommended,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ], $rules);

        return $validator->validate();
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->appId = null;
        $this->name = '';
        $this->description = '';
        $this->type = 'm3u';
        $this->platform = 'android';
        $this->version = '';
        $this->download_url = '';
        $this->downloader_code = '';
        $this->short_url = '';
        $this->is_recommended = false;
        $this->is_active = true;
        $this->sort_order = '0';
        $this->resetValidation();
    }

    /**
     * @return array<array{value: string, label: string}>
     */
    public function getTypes(): array
    {
        return collect(StreamingAppType::cases())->map(fn($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ])->toArray();
    }

    /**
     * @return array<array{value: string, label: string}>
     */
    public function getPlatforms(): array
    {
        return collect(StreamingAppPlatform::cases())->map(fn($platform) => [
            'value' => $platform->value,
            'label' => $platform->label(),
        ])->toArray();
    }

    public function render(): View
    {
        return view('livewire.admin.streaming-app-form-modal', [
            'types' => $this->getTypes(),
            'platforms' => $this->getPlatforms(),
        ]);
    }
}
