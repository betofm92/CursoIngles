<?php

namespace Tests\Unit\Providers;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_register_and_boot_run_without_exceptions(): void
    {
        $provider = new AppServiceProvider($this->app);

        $provider->register();
        $provider->boot();

        $this->assertTrue(true);
    }
}

