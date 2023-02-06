<?php

namespace Datashaman\LaravelConditional\Tests;

use Datashaman\LaravelConditional\LaravelConditionalMiddleware;
use Illuminate\Contracts\Http\Kernel;

class BasicTest extends TestCase
{
    public function testTheThing()
    {
        $kernel = $this->app->make(Kernel::class);

        $kernel->prependMiddleware(new class extends LaravelConditionalMiddleware {
            protected array $resolvers = [
                Resolver::class => [
                    'test',
                ],
            ];
        });

        $response = $this->get('/test');
        dd($response);
    }
}
