<?php

namespace Datashaman\LaravelConditional;

use Closure;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

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

        $processLastModified = Arr::has($definition, 'last_modified');

        if ($processLastModified) {
            $resolver = resolve($definition['last_modified']);

            $lastModified = $resolver->resolve($request);

            $ifModifiedSinceHeader = $request->header('If-Modified-Since');
            $ifUnmodifiedSinceHeader = $request->header('If-Unmodified-Since');

            if ($ifModifiedSinceHeader) {
                $ifModifiedSince = Carbon::parse($ifModifiedSinceHeader);

                if ($lastModified <= $ifModifiedSince) {
                    return response('', Response::HTTP_NOT_MODIFIED);
                }
            }

            if ($ifUnmodifiedSinceHeader) {
                $ifUnmodifiedSince = Carbon::parse($ifUnmodifiedSinceHeader);

                if ($lastModified > $ifUnmodifiedSince) {
                    return response('', Response::HTTP_PRECONDITION_FAILED);
                }
            }
        }

        $response = $next($request);

        if ($processLastModified) {
            $response->header('Last-Modified', $lastModified->toRfc7231String());
        }

        return $response;
    }
}
