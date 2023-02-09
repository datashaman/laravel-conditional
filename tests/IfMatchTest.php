<?php

namespace Datashaman\LaravelConditional\Tests;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;

class IfMatchTest extends TestCase
{
    public function testNoHeader()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->get('/test');
        $response->assertHeader('ETag', json_encode($eTag));
        $response->assertStatus(Response::HTTP_OK);

        Event::assertDispatched(TestEvent::class);
    }

    public function testMatch()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-Match' => json_encode($eTag),
        ])->get('/test');
        $response->assertStatus(Response::HTTP_OK);

        Event::assertDispatched(TestEvent::class);
    }

    public function testNoneMatch()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-Match' => json_encode('1234567'),
        ])->get('/test');
        $response->assertStatus(Response::HTTP_PRECONDITION_FAILED);

        Event::assertNotDispatched(TestEvent::class);
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
}
