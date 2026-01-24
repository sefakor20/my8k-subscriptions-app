<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use Illuminate\View\View;
use Livewire\Component;

class VpnHelp extends Component
{
    public function render(): View
    {
        return view('livewire.customer.vpn-help')
            ->layout('components.layouts.app');
    }
}
