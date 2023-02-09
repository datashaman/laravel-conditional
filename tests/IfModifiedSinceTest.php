<?php

namespace Datashaman\LaravelConditional\Tests;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;

class IfModifiedSinceTest extends TestCase
{
    public function testNoHeader()
    {
        $lastModified = $this->returnLastModified('Fri, 01 Feb 2019 03:45:27 GMT');
        $response = $this->get('/test');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Last-Modified', $lastModified->toRfc7231String());

        Event::assertDispatched(TestEvent::class);
    }

    public function testUnmodified()
    {
        $lastModified = $this->returnLastModified('Fri, 01 Feb 2019 03:45:27 GMT');
        $response = $this->withHeaders([
            'If-Modified-Since' => $lastModified->toRfc7231String(),
        ])->get('/test');
        $response->assertStatus(Response::HTTP_NOT_MODIFIED);

        Event::assertNotDispatched(TestEvent::class);
    }

    public function testModified()
    {
        $lastModified = $this->returnLastModified('Fri, 01 Feb 2019 03:45:27 GMT');
        $response = $this->withHeaders([
            'If-Modified-Since' => $lastModified->copy()->subDays(1)->toRfc7231String(),
        ])->get('/test');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Last-Modified', $lastModified->toRfc7231String());

        Event::assertDispatched(TestEvent::class);
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
        $router->get('/test', function () {
            TestEvent::dispatch();
        })->name('test')->middleware('web');
    }
}
