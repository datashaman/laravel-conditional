<?php

namespace Datashaman\LaravelConditional;

use Illuminate\Support\ServiceProvider;

class LaravelConditionalServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/laravel-conditional.php' => config_path('laravel-conditional.php'),
        ]);
    }
}
