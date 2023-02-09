<?php

namespace Datashaman\LaravelConditional\Tests;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;

class IfNoneMatchTest extends TestCase
{
    public function testGetNoHeader()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->get('/resource');
        $response->assertHeader('ETag', json_encode($eTag));

        Event::assertDispatched(TestEvent::class);
    }

    public function testPutNoHeader()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->put('/resource');
        $response->assertHeader('ETag', json_encode($eTag));

        Event::assertDispatched(TestEvent::class);
    }

    public function testGetMatch()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-None-Match' => json_encode($eTag),
        ])->get('/resource');
        $response->assertStatus(Response::HTTP_NOT_MODIFIED);

        Event::assertNotDispatched(TestEvent::class);
    }

    public function testPutMatch()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-None-Match' => json_encode($eTag),
        ])->put('/resource');
        $response->assertStatus(Response::HTTP_PRECONDITION_FAILED);

        Event::assertNotDispatched(TestEvent::class);
    }

    public function testGetNoneMatch()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-None-Match' => json_encode('1234567'),
        ])->get('/resource');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('ETag', json_encode($eTag));

        Event::assertDispatched(TestEvent::class);
    }

    public function testPutNoneMatch()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-None-Match' => json_encode('1234567'),
        ])->put('/resource');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('ETag', json_encode($eTag));

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
        $app['config']->set('laravel-conditional.definitions.0.etag', ETagResolver::class);
    }
}
