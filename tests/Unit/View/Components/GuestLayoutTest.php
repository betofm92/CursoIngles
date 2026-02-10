<?php

namespace Tests\Unit\View\Components;

use App\View\Components\GuestLayout;
use Tests\TestCase;

class GuestLayoutTest extends TestCase
{
    public function test_render_returns_guest_layout_view(): void
    {
        $component = new GuestLayout();

        $view = $component->render();

        $this->assertSame('layouts.guest', $view->name());
    }
}

