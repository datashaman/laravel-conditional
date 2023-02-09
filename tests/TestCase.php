<?php

namespace Datashaman\LaravelConditional\Tests;

use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
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

    public function returnETag(string $eTag): string
    {
        $this->instance(
            ETagResolver::class,
            Mockery::mock(ETagResolver::class, function (MockInterface $mock) use ($eTag) {
                $mock
                    ->shouldReceive('resolve')
                    ->once()
                    ->andReturn($eTag);
            })
        );

        return $eTag;
    }

    protected function returnLastModified(string $lastModified): Carbon
    {
        $lastModified = Carbon::parse($lastModified);

        $this->instance(
            LastModifiedResolver::class,
            Mockery::mock(LastModifiedResolver::class, function (MockInterface $mock) use ($lastModified) {
                $mock
                    ->shouldReceive('resolve')
                    ->once()
                    ->andReturn($lastModified);
            })
        );

        return $lastModified;
    }
}
