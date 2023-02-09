<?php

namespace Datashaman\LaravelConditional;

use Closure;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
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

        if ($route instanceof Route) {
            $routeName = $route->getName() ?? '';
        } elseif (is_string($route)) {
            $routeName = $route;
        } else {
            throw new Exception('Not sure what to do with this route');
        }

        $definition = $routeName ? Arr::get($this->resolverIndex, $routeName) : [];

        $processETag = Arr::has($definition, 'etag');

        if ($processETag) {
            $eTag = resolve($definition['etag'])->resolve($request);

            $ifNoneMatchHeader = $request->header('If-None-Match');
            if ($ifNoneMatchHeader && $this->matchETag($eTag, $ifNoneMatchHeader)) {
                $statusCode = $request->isMethodCacheable()
                    ? Response::HTTP_NOT_MODIFIED
                    : Response::HTTP_PRECONDITION_FAILED;

                return response('', $statusCode);
            }

            $ifMatchHeader = $request->header('If-Match');
            if ($ifMatchHeader && !$this->matchETag($eTag, $ifMatchHeader)) {
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
            $response->header('ETag', json_encode($eTag));
        }

        if ($processLastModified) {
            $response->header('Last-Modified', $lastModified->toRfc7231String());
        }

        return $response;
    }

    protected function matchETag(?string $eTag, string $header): bool
    {
        if (!$eTag) {
            return false;
        }

        $header = trim($header);

        if ($header === '*') {
            return true;
        }

        $found = collect(explode(',', $header))
            ->map(fn ($tag) => trim($tag))
            ->reject(fn ($tag) => Str::startsWith($tag, 'W/'))
            ->map(fn ($tag) => trim($tag, '"'))
            ->search($eTag);

        return $found !== false;
    }
}
