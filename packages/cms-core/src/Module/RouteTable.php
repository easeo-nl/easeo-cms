<?php
declare(strict_types=1);

namespace Easeo\Cms\Module;

/**
 * Internal collection of registered routes. Used by Registry —
 * modules call Registry::route() rather than touching this directly.
 *
 * Routes are stored as: pattern => [method, handler, owner-module-name].
 * Duplicate patterns raise DuplicateRouteException.
 *
 * Match-order is registration-order; first registered wins. The
 * eventual router-integration (later batch) merges this with the
 * core routes such that core routes win over module routes (modules
 * cannot shadow /, /blog, etc.).
 */
final class RouteTable
{
    /** @var array<string,array{method:string,handler:callable,owner:string}> */
    private array $routes = [];

    /**
     * @param 'GET'|'POST'|'PUT'|'DELETE'|'ANY' $method
     */
    public function add(string $pattern, string $method, callable $handler, string $owner): void
    {
        $key = strtoupper($method) . ' ' . $pattern;
        if (isset($this->routes[$key])) {
            $existingOwner = $this->routes[$key]['owner'];
            throw new DuplicateRouteException(
                "Route '$method $pattern' is already registered by module '$existingOwner' (now being registered by '$owner')"
            );
        }
        $this->routes[$key] = ['method' => strtoupper($method), 'handler' => $handler, 'owner' => $owner];
    }

    /**
     * @return array<string,array{method:string,handler:callable,owner:string}>
     */
    public function all(): array
    {
        return $this->routes;
    }

    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * Find the first matching route for a given method+path. Returns null
     * if no match. Used by the future router-integration (batch 4).
     *
     * Pattern matching is simple literal-equality for batch 1 — placeholder-
     * syntax (e.g. /hello/{name}) is left to a later expansion when an
     * actual module needs it.
     *
     * @return array{method:string,handler:callable,owner:string}|null
     */
    public function match(string $method, string $path): ?array
    {
        $key = strtoupper($method) . ' ' . $path;
        if (isset($this->routes[$key])) {
            return $this->routes[$key];
        }
        // Try ANY-method fallback
        $anyKey = 'ANY ' . $path;
        return $this->routes[$anyKey] ?? null;
    }
}
