<?php

namespace Puchan\LaravelApiDocs\Services;

class ApiDocGenerator
{
    private RouteScanner $routeScanner;
    private SchemaReader $schemaReader;
    private QueryParamDetector $queryParamDetector;

    public function __construct()
    {
        $this->routeScanner = new RouteScanner();
        $this->schemaReader = new SchemaReader();
        $this->queryParamDetector = new QueryParamDetector();
    }

    /**
     * Generate complete API documentation
     */
    public function generate(): array
    {
        $routes = $this->routeScanner->getApiRoutes();

        // Enrich routes with additional data
        foreach ($routes as &$controller) {
            foreach ($controller['routes'] as &$route) {
                // Get model table schema
                $tableName = $this->schemaReader->getModelTableFromController($controller['full_name']);
                if ($tableName) {
                    $route['table_schema'] = $this->schemaReader->getTableSchema($tableName);
                }

                // Detect all parameters
                $allParams = $this->queryParamDetector->detectQueryParams(
                    $controller['full_name'],
                    $route['method']
                );

                // Separate query params and body fields based on HTTP method
                $isModifyingRequest = in_array('POST', $route['methods']) ||
                                     in_array('PUT', $route['methods']) ||
                                     in_array('PATCH', $route['methods']);

                if ($isModifyingRequest) {
                    // For POST/PUT/PATCH: form_request goes to body, others to query params
                    $route['query_params'] = array_values(array_filter($allParams, function($param) {
                        return in_array($param['source'] ?? '', ['request_usage', 'pagination']);
                    }));
                    $route['body_fields'] = $this->getBodyFields($allParams, $route);
                } else {
                    // For GET/DELETE: all params are query params
                    $route['query_params'] = $allParams;
                }

                // Add response example
                $route['response_example'] = $this->generateResponseExample($route);
            }
        }

        return [
            'title' => config('api-docs.title'),
            'version' => config('api-docs.version'),
            'base_url' => config('api-docs.base_url'),
            'generated_at' => now()->toIso8601String(),
            'controllers' => $routes,
        ];
    }

    /**
     * Get body fields for POST/PUT/PATCH requests
     */
    private function getBodyFields(array $allParams, array $route): array
    {
        $bodyFields = [];

        // Get fields from detected params (FormRequest validation)
        foreach ($allParams as $param) {
            if ($param['source'] === 'form_request') {
                $bodyFields[] = $param;
            }
        }

        // If no FormRequest found, try to get from table schema
        if (empty($bodyFields) && isset($route['table_schema']['columns'])) {
            foreach ($route['table_schema']['columns'] as $column) {
                // Skip auto-generated columns
                if (in_array($column['name'], ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }

                $bodyFields[] = [
                    'name' => $column['name'],
                    'type' => $column['type'],
                    'required' => $column['required'] ?? false,
                    'nullable' => $column['nullable'] ?? false,
                    'max_length' => $column['max_length'] ?? null,
                    'source' => 'table_schema',
                ];
            }
        }

        return $bodyFields;
    }

    /**
     * Generate response example
     */
    private function generateResponseExample(array $route): array
    {
        $example = [
            'success' => true,
            'status' => 200,
            'message' => 'Success',
        ];

        // Add pagination meta if it's a list endpoint
        $usePagination = false;
        foreach ($route['query_params'] ?? [] as $param) {
            if ($param['source'] === 'pagination') {
                $usePagination = true;
                break;
            }
        }

        // Try to get resource fields, fallback to table schema
        $dataExample = $this->getResourceExample($route['controller_full'], $route['method']);

        if (!$dataExample && isset($route['table_schema']['columns'])) {
            // Fallback to table schema
            $dataExample = [];
            foreach ($route['table_schema']['columns'] as $column) {
                $dataExample[$column['name']] = $this->getExampleValue($column);
            }
        }

        if ($dataExample) {
            // If paginated, wrap in array; otherwise single object
            if ($usePagination) {
                $example['data'] = [$dataExample];
            } else {
                $example['data'] = $dataExample;
            }
        } else {
            // No schema available, use generic data
            $example['data'] = $usePagination ? [] : null;
        }

        if ($usePagination) {
            $example['meta'] = [
                'size' => 20,
                'page' => 1,
                'total_pages' => 1,
                'total_items' => 1,
            ];
        }

        return $example;
    }

    /**
     * Get resource example from Resource class
     */
    private function getResourceExample(string $controllerClass, string $method): ?array
    {
        try {
            if (!config('api-docs.resources.auto_detect', true)) {
                return null;
            }

            // Get model name from controller
            $modelName = str_replace('Controller', '', class_basename($controllerClass));
            $resourceNamespace = config('api-docs.resources.namespace', 'App\\Http\\Resources');
            $resourceClass = "{$resourceNamespace}\\{$modelName}Resource";

            if (!class_exists($resourceClass)) {
                return null;
            }

            // Try to get resource structure by analyzing the toArray method
            $reflection = new \ReflectionClass($resourceClass);
            if (!$reflection->hasMethod('toArray')) {
                return null;
            }

            $toArrayMethod = $reflection->getMethod('toArray');
            $startLine = $toArrayMethod->getStartLine();
            $endLine = $toArrayMethod->getEndLine();
            $filename = $toArrayMethod->getFileName();

            // Read the method source
            $source = implode("\n", array_slice(
                file($filename),
                $startLine - 1,
                $endLine - $startLine + 1
            ));

            // Extract array keys using regex
            preg_match_all("/'([^']+)'\s*=>/", $source, $matches);

            if (empty($matches[1])) {
                return null;
            }

            $example = [];
            foreach ($matches[1] as $key) {
                // Try to infer type from key name
                $example[$key] = $this->inferExampleValueFromKey($key);
            }

            return $example;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Infer example value from key name
     */
    private function inferExampleValueFromKey(string $key)
    {
        if ($key === 'id') return 1;
        if (str_ends_with($key, '_id')) return 1;
        if (str_ends_with($key, '_at')) return '2025-01-15T12:00:00Z';
        if (str_contains($key, 'price') || str_contains($key, 'amount')) return 99.99;
        if (str_contains($key, 'quantity')) return 10.0;
        if (str_contains($key, 'email')) return 'example@example.com';
        if (str_contains($key, 'phone')) return '+1234567890';
        if (str_contains($key, 'date')) return '2025-01-15';
        if (str_contains($key, 'url') || str_contains($key, 'image')) return 'https://example.com/image.jpg';
        if (str_contains($key, 'is_') || str_contains($key, 'has_')) return true;
        if (str_contains($key, 'status')) return 'active';
        if (str_contains($key, 'code')) return 'CODE123';

        return 'string value';
    }

    /**
     * Get example value for column type
     */
    private function getExampleValue(array $column)
    {
        if ($column['nullable'] && rand(0, 1)) {
            return null;
        }

        return match ($column['type']) {
            'integer' => 1,
            'number' => 99.99,
            'boolean' => true,
            'datetime' => '2025-01-15T12:00:00Z',
            'date' => '2025-01-15',
            'time' => '12:00:00',
            'json' => [],
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'email' => 'example@example.com',
            'url' => 'https://example.com',
            default => 'string value',
        };
    }
}
