<?php

namespace Amarenkov\MutableContentTasks;

use Illuminate\Support\ServiceProvider;

use Amarenkov\MutableContent\Domain\LovRegistry;
use Amarenkov\MutableContent\Domain\MutableClassRegistry;

use Amarenkov\MutableContentTasks\Lovs\ActorType;
use Amarenkov\MutableContentTasks\Lovs\ParticipantRole;
use Amarenkov\MutableContentTasks\Lovs\PointType;
use Amarenkov\MutableContentTasks\Models\Actor;
use Amarenkov\MutableContentTasks\Models\Participant;
use Amarenkov\MutableContentTasks\Models\Point;

class MutableContentTasksServiceProvider extends ServiceProvider
{
    // public
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mutable-content-tasks.php', 'mutable-content-tasks');

        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');

        $this->callAfterResolving(MutableClassRegistry::class, function (MutableClassRegistry $registry) {
            $registry
                ->add(Point::class)
                ->add(Actor::class)
                ->add(Participant::class);
        });

        $this->callAfterResolving(LovRegistry::class, function (LovRegistry $registry) {
            $registry
                ->addClass(PointType::class)
                ->addClass(ActorType::class)
                ->addClass(ParticipantRole::class);
        });
    }

    public function boot(): void
    {
        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'mutable-content-tasks-migrations');

        $this->publishes([
            __DIR__.'/../config/mutable-content-tasks.php' => config_path('mutable-content-tasks.php'),
        ], 'mutable-content-tasks-config');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'mutable-content-tasks');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/mutable-content-tasks'),
        ], 'mutable-content-tasks-lang');
    }
}
