<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\Admin\FailedJobsService;
use Livewire\Attributes\On;
use Livewire\Component;

class FailedJobDetailModal extends Component
{
    public bool $show = false;

    public ?string $jobUuid = null;

    public ?object $job = null;

    /**
     * Open the modal
     */
    #[On('open-failed-job-modal')]
    public function openModal(string $uuid): void
    {
        $service = app(FailedJobsService::class);
        $this->job = $service->getFailedJob($uuid);
        $this->jobUuid = $uuid;
        $this->show = true;
    }

    /**
     * Close the modal
     */
    public function closeModal(): void
    {
        $this->show = false;
        $this->jobUuid = null;
        $this->job = null;
    }

    /**
     * Retry this job
     */
    public function retry(): void
    {
        if (! $this->jobUuid) {
            return;
        }

        $service = app(FailedJobsService::class);
        $service->retryJob($this->jobUuid);

        session()->flash('success', 'Job retry initiated successfully.');
        $this->closeModal();
    }

    /**
     * Delete this job
     */
    public function delete(): void
    {
        if (! $this->jobUuid) {
            return;
        }

        $service = app(FailedJobsService::class);
        $service->deleteJob($this->jobUuid);

        session()->flash('success', 'Job deleted successfully.');
        $this->closeModal();
    }

    /**
     * Render the component
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.failed-job-detail-modal');
    }
}
