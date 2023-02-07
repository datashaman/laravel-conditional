<?php

namespace Datashaman\LaravelConditional;

use Closure;
use Exception;
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

            foreach ($routes as $route) {
                $this->resolverIndex[$route] = $definition;
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
        $route = $request->route()?->getName();

        if (!$route) {
            return $next($request);
        }

        $definition = Arr::get($this->resolverIndex, $route);

        if (!$definition) {
            return $next($request);
        }

        if (Arr::has($definition, 'last_modified')) {
            $resolver = resolve($definition['last_modified']);

            $lastModified = $resolver->resolve($request);

            $ifModifiedSinceHeader = $request->header('If-Modified-Since');
            $ifUnmodifiedSinceHeader = $request->header('If-Unmodified-Since');

            if ($ifModifiedSinceHeader) {
                $ifModifiedSince = Carbon::parse($ifModifiedSinceHeader);

                if ($lastModified <= $ifModifiedSince) {
                    abort(304);
                }
            }

            if ($ifUnmodifiedSinceHeader) {
                $ifUnmodifiedSince = Carbon::parse($ifUnmodifiedSinceHeader);

                if ($lastModified > $ifUnmodifiedSince) {
                    abort(412);
                }
            }
        }

        $response = $next($request);

        if (Arr::has($definition, 'last_modified')) {
            $response->header('Last-Modified', $lastModified->toRfc7231String());
        }

        foreach ($this->getHeaders() as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }

    protected function getHeaders()
    {
        return [
        ];
    }
}
