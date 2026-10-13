<?php

namespace App\View\Components\Mail;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BasicLayout extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public $message, public $title = null)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('layouts.mail.basic');
    }
}
