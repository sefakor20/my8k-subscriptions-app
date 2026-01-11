<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\ProvisioningLog;
use App\Services\Admin\ProvisioningLogsService;
use Livewire\Attributes\On;
use Livewire\Component;

class ProvisioningLogDetailModal extends Component
{
    public bool $show = false;

    public ?string $logId = null;

    public ?ProvisioningLog $log = null;

    /**
     * Open the modal
     */
    #[On('open-log-modal')]
    public function openModal(string $logId): void
    {
        $service = app(ProvisioningLogsService::class);
        $this->log = $service->getLog($logId);
        $this->logId = $logId;
        $this->show = true;
    }

    /**
     * Close the modal
     */
    public function closeModal(): void
    {
        $this->show = false;
        $this->logId = null;
        $this->log = null;
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.provisioning-log-detail-modal');
    }
}
