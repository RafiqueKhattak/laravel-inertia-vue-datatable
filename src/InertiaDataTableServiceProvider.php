<?php

namespace QBits\InertiaDataTable;

use Illuminate\Support\ServiceProvider;
use QBits\InertiaDataTable\Commands\MakeInertiaTable;

class InertiaDataTableServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/datatable.php' => config_path('datatable.php'),
            ], 'datatable-config');

            $this->publishes([
                __DIR__ . '/../resources/js' => resource_path('js/vendor/datatable'),
            ], 'datatable-components');

            $this->commands([
                MakeInertiaTable::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/datatable.php',
            'datatable'
        );
    }
}
