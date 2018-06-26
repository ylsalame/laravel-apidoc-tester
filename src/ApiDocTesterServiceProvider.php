<?php

namespace YLSalame\LaravelApiDocTester;

use Illuminate\Support\ServiceProvider;

class ApiDocTesterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ApiDocTester::class
            ]);
        }
    }
}
