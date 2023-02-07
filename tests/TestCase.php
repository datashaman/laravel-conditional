<?php

namespace Datashaman\LaravelConditional\Tests;

use Illuminate\Contracts\Http\Kernel as KernelContract;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Define routes setup.
     *
     * @param  \Illuminate\Routing\Router  $router
     *
     * @return void
     */
    protected function defineRoutes($router)
    {
        $router->get('/test', function () {
            TestEvent::dispatch();
        })->name('test')->middleware('web');
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app->setBasePath(__DIR__ . '/..');

        $app['config']->set('laravel-conditional', [
            'definitions' => [
                [
                    'headers' => [
                    ],
                    'etag' => ETagResolver::class,
                    'last_modified' => LastModifiedResolver::class,
                    'routes' => 'test',
                ],
            ],

            'headers' => [
            ],
        ]);
    }

    /**
     * Resolve application HTTP Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton(KernelContract::class, Kernel::class);
    }
}
