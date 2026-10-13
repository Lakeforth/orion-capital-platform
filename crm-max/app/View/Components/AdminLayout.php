<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdminLayout extends Component
{
    public bool $modernShell;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        // The refreshed Admin CRM uses one consistent shell across Dashboard,
        // Customers, and the remaining central pages. CRM-Max keeps its own
        // dedicated leadmax layout and is not affected by this component.
        $this->modernShell = true;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('layouts.admin', ['modernShell' => $this->modernShell]);
    }
}
