<?php

namespace Puchan\LaravelApiDocs\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use ReflectionClass;

class RouteScanner
{
    /**
     * Get all API routes with their details
     */
    public function getApiRoutes(): array
    {
        $routes = RouteFacade::getRoutes();
        $apiRoutes = [];
        $filters = config('api-docs.route_filters', []);

        foreach ($routes as $route) {
            // Only process API routes
            if (!$this->isApiRoute($route, $filters)) {
                continue;
            }

            $routeData = $this->parseRoute($route);
            if ($routeData) {
                $apiRoutes[] = $routeData;
            }
        }

        // Group by controller
        return $this->groupByController($apiRoutes);
    }

    /**
     * Check if route is an API route
     */
    private function isApiRoute(Route $route, array $filters): bool
    {
        $uri = $route->uri();

        // Check include prefixes
        $includePrefixes = $filters['include_prefixes'] ?? ['api/'];
        $matchesPrefix = false;
        foreach ($includePrefixes as $prefix) {
            if (str_starts_with($uri, $prefix)) {
                $matchesPrefix = true;
                break;
            }
        }

        if (!$matchesPrefix) {
            return false;
        }

        // Check exclude patterns
        $excludePatterns = $filters['exclude_patterns'] ?? [];
        foreach ($excludePatterns as $pattern) {
            if (fnmatch($pattern, $uri)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse route details
     */
    private function parseRoute(Route $route): ?array
    {
        $action = $route->getAction();

        if (!isset($action['controller'])) {
            return null;
        }

        [$controller, $method] = explode('@', $action['controller']);

        return [
            'uri' => $route->uri(),
            'methods' => $route->methods(),
            'name' => $route->getName(),
            'controller' => class_basename($controller),
            'controller_full' => $controller,
            'method' => $method,
            'parameters' => $this->getRouteParameters($route),
            'middleware' => $this->getMiddleware($route),
            'docblock' => $this->getMethodDocblock($controller, $method),
        ];
    }

    /**
     * Get route parameters from URI
     */
    private function getRouteParameters(Route $route): array
    {
        preg_match_all('/\{([^\}]+)\}/', $route->uri(), $matches);

        $parameters = [];
        foreach ($matches[1] as $param) {
            $optional = str_ends_with($param, '?');
            $paramName = rtrim($param, '?');

            $parameters[] = [
                'name' => $paramName,
                'required' => !$optional,
                'type' => 'path',
            ];
        }

        return $parameters;
    }

    /**
     * Get middleware for route
     */
    private function getMiddleware(Route $route): array
    {
        return array_values(array_filter($route->middleware(), function ($middleware) {
            return !in_array($middleware, ['api', 'web']);
        }));
    }

    /**
     * Get method docblock comment
     */
    private function getMethodDocblock(string $controller, string $method): ?string
    {
        try {
            if (!class_exists($controller)) {
                return null;
            }

            $reflection = new ReflectionClass($controller);
            if (!$reflection->hasMethod($method)) {
                return null;
            }

            $reflectionMethod = $reflection->getMethod($method);
            $docComment = $reflectionMethod->getDocComment();

            if ($docComment) {
                // Clean up docblock
                $docComment = preg_replace('/^\s*\*\s*/m', '', $docComment);
                $docComment = trim(str_replace(['/**', '*/'], '', $docComment));
                return $docComment;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Group routes by controller
     */
    private function groupByController(array $routes): array
    {
        $grouped = [];

        foreach ($routes as $route) {
            $controller = $route['controller'];

            if (!isset($grouped[$controller])) {
                $grouped[$controller] = [
                    'name' => $controller,
                    'full_name' => $route['controller_full'],
                    'routes' => [],
                ];
            }

            $grouped[$controller]['routes'][] = $route;
        }

        return array_values($grouped);
    }
}
