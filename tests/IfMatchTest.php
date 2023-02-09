<?php

namespace Datashaman\LaravelConditional\Tests;

use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;

class IfMatchTest extends TestCase
{
    public function testNoHeader()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->get('/test');
        $response->assertHeader('ETag', $eTag);

        Event::assertDispatched(TestEvent::class);
    }

    public function testMatch()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-Match' => $eTag,
        ])->get('/test');
        $response->assertStatus(200);

        Event::assertDispatched(TestEvent::class);
    }

    public function testNoneMatch()
    {
        $eTag = $this->returnETag('abcdefg');

        $headers = [
            'If-Match' => '1234567',
        ];

        $response = $this
            ->withHeaders($headers)
            ->get('/test');

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
                    'etag' => ETagResolver::class,
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
        $router->get('/test', function () {
            TestEvent::dispatch();
        })->name('test')->middleware('web');
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
}
