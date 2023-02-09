<?php

namespace Datashaman\LaravelConditional\Tests;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;

class IfUnmodifiedSinceTest extends TestCase
{
    public function testGetUnmodified()
    {
        $lastModified = $this->returnLastModified('Fri, 01 Feb 2019 03:45:27 GMT');
        $response = $this->withHeaders([
            'If-Unmodified-Since' => $lastModified->toRfc7231String(),
        ])->get('/resource');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Last-Modified', $lastModified->toRfc7231String());

        Event::assertDispatched(TestEvent::class);
    }

    public function testPutUnmodified()
    {
        $lastModified = $this->returnLastModified('Fri, 01 Feb 2019 03:45:27 GMT');
        $response = $this->withHeaders([
            'If-Unmodified-Since' => $lastModified->toRfc7231String(),
        ])->put('/resource');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Last-Modified', $lastModified->toRfc7231String());

        Event::assertDispatched(TestEvent::class);
    }

    public function testGetModified()
    {
        $lastModified = $this->returnLastModified('Fri, 01 Feb 2019 03:45:27 GMT');
        $response = $this->withHeaders([
            'If-Unmodified-Since' => $lastModified->copy()->subDays(1)->toRfc7231String(),
        ])->get('/resource');
        $response->assertStatus(Response::HTTP_PRECONDITION_FAILED);

        Event::assertNotDispatched(TestEvent::class);
    }

    public function testPutModified()
    {
        $lastModified = $this->returnLastModified('Fri, 01 Feb 2019 03:45:27 GMT');
        $response = $this->withHeaders([
            'If-Unmodified-Since' => $lastModified->copy()->subDays(1)->toRfc7231String(),
        ])->put('/resource');
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
        $app['config']->set('laravel-conditional.definitions.0.last_modified', LastModifiedResolver::class);
    }
}
