<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class StopImpersonation extends Component
{
    public function stop(): void
    {
        $impersonatorId = session('impersonator_id');

        if (! $impersonatorId) {
            return;
        }

        $admin = User::find($impersonatorId);

        if ($admin && $admin->is_admin) {
            session()->forget('impersonator_id');
            Auth::login($admin);
            $this->redirect(route('admin.customers.index'), navigate: true);
        }
    }

    public function render(): View
    {
        return view('livewire.stop-impersonation');
    }
}
