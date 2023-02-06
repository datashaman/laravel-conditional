<?php

namespace Datashaman\LaravelConditional;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

abstract class LaravelConditionalMiddleware
{
    protected array $resolverIndex = [];

    public function __construct()
    {
        $this->resolverIndex = [];

        foreach ($this->resolvers as $resolver => $routeNames) {
            foreach ($routeNames as $routeName) {
                $this->resolverIndex[$routeName] = $resolver;
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
        $routeName = $request->route()->getName();

        if (!Arr::has($this->resolverIndex, $routeName)) {
            return $next($request);
        }

        $resolver = resolve($this->resolverIndex[$routeName]);

        $lastModified = $resolver($request);

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

        $response = $next($request);

        $response->header('Last-Modified', $lastModified->toRfc7231String());

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
