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
                // Get model table schema (only if enabled in config)
                if (config('api-docs.database.show_in_docs', true)) {
                    $tableName = $this->schemaReader->getModelTableFromController($controller['full_name']);
                    if ($tableName) {
                        $route['table_schema'] = $this->schemaReader->getTableSchema($tableName);
                    }
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

        // Try to detect response structure from controller method
        $customResponse = $this->detectCustomResponse($route['controller_full'], $route['method']);
        if ($customResponse) {
            return $customResponse;
        }

        // Try to get actual data from database first
        $dataExample = $this->getActualDataExample($route);

        // Fallback to resource example
        if (!$dataExample) {
            $dataExample = $this->getResourceExample($route['controller_full'], $route['method']);
        }

        // Fallback to table schema
        if (!$dataExample && isset($route['table_schema']['columns'])) {
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
     * Detect custom response structure from controller method
     */
    private function detectCustomResponse(string $controllerClass, string $method): ?array
    {
        try {
            if (!class_exists($controllerClass)) {
                return null;
            }

            $reflection = new \ReflectionClass($controllerClass);
            if (!$reflection->hasMethod($method)) {
                return null;
            }

            $reflectionMethod = $reflection->getMethod($method);
            $startLine = $reflectionMethod->getStartLine();
            $endLine = $reflectionMethod->getEndLine();
            $filename = $reflectionMethod->getFileName();

            // Read the method source
            $source = implode("\n", array_slice(
                file($filename),
                $startLine - 1,
                $endLine - $startLine + 1
            ));

            // Look for sendResponse with array: sendResponse([...], 'message', status)
            if (preg_match('/sendResponse\s*\(\s*\[(.*?)\]\s*,\s*[\'"](.+?)[\'"]\s*(?:,\s*(\d+))?\s*\)/s', $source, $matches)) {
                $arrayContent = $matches[1];
                $message = $matches[2];
                $status = isset($matches[3]) ? (int)$matches[3] : 200;

                // Parse the array structure
                $data = $this->parseArrayStructure($arrayContent, $source);

                return [
                    'success' => true,
                    'status' => $status,
                    'message' => $message,
                    'data' => $data,
                ];
            }

            // Look for sendResponse with Resource::collection: sendResponse(Resource::collection(...), 'message', status)
            if (preg_match('/sendResponse\s*\(\s*(\w+Resource)::collection\s*\([^)]*\)\s*,\s*[\'"](.+?)[\'"]\s*(?:,\s*(\d+))?\s*\)/s', $source, $matches)) {
                $resourceClass = $matches[1];
                $message = $matches[2];
                $status = isset($matches[3]) ? (int)$matches[3] : 200;

                // Get resource structure and wrap in array for collection
                $data = [$this->getResourceStructureExample($resourceClass)];

                return [
                    'success' => true,
                    'status' => $status,
                    'message' => $message,
                    'data' => $data,
                ];
            }

            // Look for sendResponse with direct Resource: sendResponse(new Resource(...), 'message', status)
            if (preg_match('/sendResponse\s*\(\s*new\s+(\w+Resource)\s*\([^)]*\)\s*,\s*[\'"](.+?)[\'"]\s*(?:,\s*(\d+))?\s*\)/s', $source, $matches)) {
                $resourceClass = $matches[1];
                $message = $matches[2];
                $status = isset($matches[3]) ? (int)$matches[3] : 200;

                // Get resource structure (single item, no array wrapping)
                $data = $this->getResourceStructureExample($resourceClass);

                return [
                    'success' => true,
                    'status' => $status,
                    'message' => $message,
                    'data' => $data,
                ];
            }

            // Look for sendResponse with variable: sendResponse($variable, 'message', status)
            if (preg_match('/sendResponse\s*\(\s*\$(\w+)\s*,\s*[\'"](.+?)[\'"]\s*(?:,\s*(\d+))?\s*\)/s', $source, $matches)) {
                $variableName = $matches[1];
                $message = $matches[2];
                $status = isset($matches[3]) ? (int)$matches[3] : 200;

                // Try to infer data type from variable name or context
                $data = $this->guessValueFromKey($variableName);

                return [
                    'success' => true,
                    'status' => $status,
                    'message' => $message,
                    'data' => $data,
                ];
            }

            // Look for response()->json pattern
            if (preg_match('/response\s*\(\s*\)\s*->\s*json\s*\(\s*\[(.*?)\]\s*(?:,\s*(\d+))?\s*\)/s', $source, $matches)) {
                $arrayContent = $matches[1];
                $status = isset($matches[2]) ? (int)$matches[2] : 200;

                // Parse the array structure
                $data = $this->parseArrayStructure($arrayContent, $source);

                return $data;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse array structure from source code
     */
    private function parseArrayStructure(string $arrayContent, string $fullSource): array
    {
        $result = [];

        // Match key-value pairs: 'key' => value
        preg_match_all('/[\'"](\w+)[\'"]\s*=>\s*(.+?)(?:,|$)/s', $arrayContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key = $match[1];
            $value = trim($match[2]);

            // Check if it's a Resource
            if (preg_match('/new\s+(\w+Resource)\s*\(/', $value, $resourceMatch)) {
                $resourceClass = $resourceMatch[1];
                $result[$key] = $this->getResourceStructureExample($resourceClass);
            }
            // Check if it's a variable or method call
            elseif (preg_match('/^\$\w+/', $value)) {
                // Variable - use placeholder
                $result[$key] = $this->guessValueFromKey($key);
            }
            // String literal
            elseif (preg_match('/^[\'"](.+?)[\'"]$/', $value, $strMatch)) {
                $result[$key] = $strMatch[1];
            }
            // Number
            elseif (is_numeric($value)) {
                $result[$key] = $value;
            }
            // Boolean
            elseif (in_array($value, ['true', 'false'])) {
                $result[$key] = $value === 'true';
            }
            else {
                $result[$key] = $this->guessValueFromKey($key);
            }
        }

        return $result;
    }

    /**
     * Get example structure from Resource class
     */
    private function getResourceStructureExample(string $resourceClass): array
    {
        try {
            $fullResourceClass = "App\\Http\\Resources\\{$resourceClass}";

            if (!class_exists($fullResourceClass)) {
                return ['id' => 1, 'example' => 'value'];
            }

            $reflection = new \ReflectionClass($fullResourceClass);
            if (!$reflection->hasMethod('toArray')) {
                return ['id' => 1, 'example' => 'value'];
            }

            $toArrayMethod = $reflection->getMethod('toArray');
            $startLine = $toArrayMethod->getStartLine();
            $endLine = $toArrayMethod->getEndLine();
            $filename = $toArrayMethod->getFileName();

            $source = implode("\n", array_slice(
                file($filename),
                $startLine - 1,
                $endLine - $startLine + 1
            ));

            // Extract array keys
            preg_match_all("/'([^']+)'\s*=>/", $source, $matches);

            if (empty($matches[1])) {
                return ['id' => 1, 'example' => 'value'];
            }

            // Try to get model name from Resource class name
            $modelName = str_replace('Resource', '', $resourceClass);
            $modelClass = "App\\Models\\{$modelName}";

            $actualData = null;
            if (class_exists($modelClass) && config('api-docs.database.use_actual_data', true)) {
                try {
                    $model = new $modelClass();
                    $tableName = $model->getTable();
                    $record = \DB::table($tableName)->first();
                    if ($record) {
                        $actualData = (array) $record;
                    }
                } catch (\Exception $e) {
                    // Ignore and use generated data
                }
            }

            // Build example from Resource keys
            $example = [];
            foreach ($matches[1] as $key) {
                // Use actual data if available, otherwise guess from key name
                if ($actualData && isset($actualData[$key])) {
                    $example[$key] = $this->sanitizeValue($key, $actualData[$key]);
                } else {
                    $example[$key] = $this->guessValueFromKey($key);
                }
            }

            return $example ?: ['id' => 1, 'example' => 'value'];
        } catch (\Exception $e) {
            return ['id' => 1, 'example' => 'value'];
        }
    }

    /**
     * Guess example value from key name
     */
    private function guessValueFromKey(string $key)
    {
        // IDs
        if ($key === 'id') return 1;
        if (str_ends_with($key, '_id')) return 1;

        // Dates/Times
        if (str_ends_with($key, '_at')) return '2025-01-15T12:00:00Z';
        if (str_contains($key, 'date')) return '2025-01-15';
        if (str_contains($key, 'time')) return '12:00:00';

        // Tokens & Auth
        if (str_contains($key, 'token')) return 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...';
        if (str_contains($key, 'password')) return '***HIDDEN***';

        // Contact Info
        if (str_contains($key, 'email')) return 'user@example.com';
        if (str_contains($key, 'phone')) return '+1234567890';
        if (str_contains($key, 'address')) return '123 Main Street';
        if (str_contains($key, 'city')) return 'New York';
        if (str_contains($key, 'country')) return 'United States';
        if (str_contains($key, 'zip') || str_contains($key, 'postal')) return '10001';

        // Financial
        if (str_contains($key, 'price') || str_contains($key, 'amount') || str_contains($key, 'cost')) return 99.99;
        if (str_contains($key, 'quantity') || str_contains($key, 'count')) return 10;
        if (str_contains($key, 'discount')) return 15.5;

        // URLs & Files
        if (str_contains($key, 'url')) return 'https://example.com';
        if (str_contains($key, 'image') || str_contains($key, 'photo') || str_contains($key, 'avatar')) return 'https://example.com/image.jpg';
        if (str_contains($key, 'file')) return 'document.pdf';

        // Booleans
        if (str_starts_with($key, 'is_') || str_starts_with($key, 'has_')) return true;
        if (str_contains($key, 'active') || str_contains($key, 'enabled')) return true;

        // Status & Type
        if (str_contains($key, 'status')) return 'active';
        if (str_contains($key, 'type') && !str_contains($key, 'token')) return 'Bearer';
        if (str_contains($key, 'code')) return 'ABC123';

        // Names
        if ($key === 'name' || str_contains($key, 'name')) return 'Example Name';
        if ($key === 'title') return 'Example Title';
        if ($key === 'description') return 'This is an example description';

        // Objects (common nested structures)
        if ($key === 'user') return [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'user@example.com'
        ];
        if ($key === 'role') return [
            'id' => 1,
            'name' => 'Admin'
        ];
        if ($key === 'permissions') return [
            ['id' => 1, 'name' => 'read'],
            ['id' => 2, 'name' => 'write']
        ];
        if ($key === 'branches') return [
            ['id' => 1, 'name' => 'Main Branch']
        ];

        return 'example value';
    }

    /**
     * Get actual data example from database
     */
    private function getActualDataExample(array $route): ?array
    {
        // Check if using actual data is enabled
        if (!config('api-docs.database.use_actual_data', true)) {
            return null;
        }

        try {
            if (!isset($route['table_schema']['table'])) {
                return null;
            }

            $tableName = $route['table_schema']['table'];

            // Fetch the first record from the table
            $record = \DB::table($tableName)->first();

            if (!$record) {
                return null;
            }

            // Convert to array and sanitize sensitive fields
            $data = (array) $record;
            $sanitizedData = [];

            foreach ($data as $key => $value) {
                $sanitizedData[$key] = $this->sanitizeValue($key, $value);
            }

            return $sanitizedData;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sanitize sensitive field values
     */
    private function sanitizeValue(string $fieldName, $value)
    {
        // List of sensitive field patterns
        $sensitivePatterns = [
            'password',
            'secret',
            'token',
            'api_key',
            'private_key',
            'access_token',
            'refresh_token',
            'credit_card',
            'ssn',
            'salt',
        ];

        $lowerFieldName = strtolower($fieldName);

        // Check if field name matches any sensitive pattern
        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($lowerFieldName, $pattern)) {
                return '***HIDDEN***';
            }
        }

        // Return actual value if not sensitive
        return $value;
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
