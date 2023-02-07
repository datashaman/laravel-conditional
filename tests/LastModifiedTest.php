<?php

namespace Datashaman\LaravelConditional\Tests;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;

class LastModifiedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    public function testNoHeader()
    {
        $lastModified = $this->returnLastModified('Fri, 01 Feb 2019 03:45:27 GMT');
        $response = $this->get('/test');
        $response->assertHeader('Last-Modified', $lastModified->toRfc7231String());

        Event::assertDispatched(TestEvent::class);
    }

    public function testUnmodified()
    {
        $lastModified = $this->returnLastModified('Fri, 01 Feb 2019 03:45:27 GMT');
        $response = $this->withHeaders([
            'If-Modified-Since' => $lastModified->toRfc7231String(),
        ])->get('/test');
        $response->assertStatus(304);

        Event::assertNotDispatched(TestEvent::class);
    }

    public function testModified()
    {
        $lastModified = $this->returnLastModified('Fri, 01 Feb 2019 03:45:27 GMT');

        $headers = [
            'If-Modified-Since' => $lastModified->copy()->subDays(1)->toRfc7231String(),
        ];

        $response = $this
            ->withHeaders($headers)
            ->get('/test');

        $response->assertStatus(200);
        $response->assertHeader('Last-Modified', $lastModified->toRfc7231String());

        Event::assertDispatched(TestEvent::class);
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
