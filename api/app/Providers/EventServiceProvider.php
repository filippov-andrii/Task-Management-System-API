<?php

namespace App\Providers;

use App\Events\TaskUpdated;
use App\Listeners\SendTaskDeadlineNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TaskUpdated::class => [
            SendTaskDeadlineNotification::class,
        ],
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
