<?php

namespace Datashaman\LaravelConditional\Tests;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;

class IfMatchTest extends TestCase
{
    public function testGetNoHeader()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->get('/resource');
        $response->assertHeader('ETag', json_encode($eTag));
        $response->assertStatus(Response::HTTP_OK);

        Event::assertDispatched(TestEvent::class);
    }

    public function testPutNoHeader()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->put('/resource');
        $response->assertHeader('ETag', json_encode($eTag));
        $response->assertStatus(Response::HTTP_OK);

        Event::assertDispatched(TestEvent::class);
    }

    public function testGetMatch()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-Match' => json_encode($eTag),
        ])->get('/resource');
        $response->assertStatus(Response::HTTP_OK);

        Event::assertDispatched(TestEvent::class);
    }

    public function testGetAny()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-Match' => '*',
        ])->get('/resource');
        $response->assertStatus(Response::HTTP_OK);

        Event::assertDispatched(TestEvent::class);
    }

    public function testGetAnyNoEtag()
    {
        $eTag = $this->returnETag(null);
        $response = $this->withHeaders([
            'If-Match' => '*',
        ])->getJson('/resource');
        $response->dump();
        $response->assertStatus(Response::HTTP_PRECONDITION_FAILED);

        Event::assertNotDispatched(TestEvent::class);
    }

    public function testPutMatch()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-Match' => json_encode($eTag),
        ])->put('/resource');
        $response->assertStatus(Response::HTTP_OK);

        Event::assertDispatched(TestEvent::class);
    }

    public function testPutMatchAny()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-Match' => '*',
        ])->put('/resource');
        $response->assertStatus(Response::HTTP_OK);

        Event::assertDispatched(TestEvent::class);
    }

    public function testPutMatchAnyNoEtag()
    {
        $eTag = $this->returnETag(null);
        $response = $this->withHeaders([
            'If-Match' => '*',
        ])->put('/resource');
        $response->assertStatus(Response::HTTP_PRECONDITION_FAILED);

        Event::assertNotDispatched(TestEvent::class);
    }

    public function testGetNoneMatch()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-Match' => json_encode('1234567'),
        ])->get('/resource');
        $response->assertStatus(Response::HTTP_PRECONDITION_FAILED);

        Event::assertNotDispatched(TestEvent::class);
    }

    public function testPutNoneMatch()
    {
        $eTag = $this->returnETag('abcdefg');
        $response = $this->withHeaders([
            'If-Match' => json_encode('1234567'),
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
        $app['config']->set('laravel-conditional.definitions.0.etag', ETagResolver::class);
    }
}
