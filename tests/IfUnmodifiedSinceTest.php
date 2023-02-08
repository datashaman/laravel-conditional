<?php

namespace Datashaman\LaravelConditional\Tests;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;

class IfUnmodifiedSinceTest extends TestCase
{
    public function testUnmodified()
    {
        $lastModified = $this->returnLastModified('Fri, 01 Feb 2019 03:45:27 GMT');
        $response = $this->withHeaders([
            'If-Unmodified-Since' => $lastModified->toRfc7231String(),
        ])->post('/test');
        $response->assertStatus(200);

        Event::assertDispatched(TestEvent::class);
    }

    public function testModified()
    {
        $lastModified = $this->returnLastModified('Fri, 01 Feb 2019 03:45:27 GMT');

        $headers = [
            'If-Unmodified-Since' => $lastModified->copy()->subDays(1)->toRfc7231String(),
        ];

        $response = $this
            ->withHeaders($headers)
            ->post('/test');

        $response->assertStatus(412);

        Event::assertNotDispatched(TestEvent::class);
    }

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
        parent::defineEnvironment($app);

        $app['config']->set('laravel-conditional', [
            'definitions' => [
                [
                    'last_modified' => LastModifiedResolver::class,
                    'routes' => 'test',
                ],
            ],
        ]);
    }

    /**
     * Define routes setup.
     *
     * @param  \Illuminate\Routing\Router  $router
     *
     * @return void
     */
    protected function defineRoutes($router)
    {
        $router->post('/test', function () {
            TestEvent::dispatch();
        })->name('test')->middleware('web');
    }

    public function returnLastModified(string $lastModified): Carbon
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
