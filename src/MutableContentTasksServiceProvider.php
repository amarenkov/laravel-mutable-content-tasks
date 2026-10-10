<?php

namespace Amarenkov\MutableContentTasks;

use Illuminate\Support\ServiceProvider;

class MutableContentTasksServiceProvider extends ServiceProvider
{
    // public
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'mutable-content-tasks');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/mutable-content-tasks'),
        ], 'mutable-content-tasks-lang');
    }
}
