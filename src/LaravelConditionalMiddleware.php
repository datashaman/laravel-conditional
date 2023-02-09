<?php

namespace Datashaman\LaravelConditional;

use Closure;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LaravelConditionalMiddleware
{
    protected array $resolverIndex = [];

    public function __construct()
    {
        $this->resolverIndex = [];

        foreach (config('laravel-conditional.definitions') as $definition) {
            if (Arr::has($definition, 'route')) {
                $routes = [$definition['route']];
            } else if (Arr::has($definition, 'routes')) {
                $routes = (array) $definition['routes'];
            } else {
                throw new Exception('Routes must be defined in a definition');
            }

            foreach ($routes as $routeName) {
                $this->resolverIndex[$routeName] = $definition;
            }
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $route = $request->route();
        $routeName = $route ? $route->getName() : '';
        $definition = $routeName ? Arr::get($this->resolverIndex, $routeName) : [];

        $processETag = Arr::has($definition, 'etag');

        if ($processETag) {
            $eTag = resolve($definition['etag'])->resolve($request);

            $ifMatchHeader = $request->header('If-Match');

            if (
                $ifNoneMatchHeader = $request->header('If-None-Match')
                && $this->matchETag($eTag, $ifNoneMatchHeader)
            ) {
                return response('', Response::HTTP_NOT_MODIFIED);
            }

            if (
                $ifMatchHeader = $request->header('If-Match')
                && !$this->matchETag($eTag, $ifMatchHeader)
            ) {
                return response('', Response::HTTP_PRECONDITION_FAILED);
            }
        }

        $processLastModified = Arr::has($definition, 'last_modified');

        if ($processLastModified) {
            $lastModified = resolve($definition['last_modified'])->resolve($request);

            $ifModifiedSinceHeader = $request->header('If-Modified-Since');

            if ($ifModifiedSinceHeader) {
                $ifModifiedSince = Carbon::parse($ifModifiedSinceHeader);
                if ($lastModified <= $ifModifiedSince) {
                    return response('', Response::HTTP_NOT_MODIFIED);
                }
            }

            $ifUnmodifiedSinceHeader = $request->header('If-Unmodified-Since');

            if ($ifUnmodifiedSinceHeader) {
                $ifUnmodifiedSince = Carbon::parse($ifUnmodifiedSinceHeader);
                if ($lastModified > $ifUnmodifiedSince) {
                    return response('', Response::HTTP_PRECONDITION_FAILED);
                }
            }
        }

        $response = $next($request);

        if ($processETag) {
            $response->header('ETag', $eTag);
        }

        if ($processLastModified) {
            $response->header('Last-Modified', $lastModified->toRfc7231String());
        }

        return $response;
    }

    protected function matchETag(string $eTag, string $header): bool
    {
        $header = trim($header);

        if ($header === '*') {
            return true;
        }

        $found = collect(explode(',', $header))
            ->map(fn ($tag) => trim($tag, "\" \n\r\t\v\x00"))
            ->reject(fn ($tag) => Str::startsWith($tag, 'W/'))
            ->search($eTag);

        return $found !== false;
    }
}
