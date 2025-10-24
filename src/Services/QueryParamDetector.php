<?php

namespace Puchan\LaravelApiDocs\Services;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class QueryParamDetector
{
    /**
     * Detect query parameters from controller method
     */
    public function detectQueryParams(string $controllerClass, string $method): array
    {
        $params = [];

        // Get parameters from method signature
        $params = array_merge($params, $this->getMethodParameters($controllerClass, $method));

        // Get parameters from Request validation
        $params = array_merge($params, $this->getRequestValidationParams($controllerClass, $method));

        // Get parameters from method body (Request usage)
        $params = array_merge($params, $this->getRequestUsageParams($controllerClass, $method));

        // Get common pagination/filter params if method uses pagination
        if ($this->usesPagination($controllerClass, $method)) {
            $params = array_merge($params, $this->getPaginationParams());
        }

        // Remove duplicates
        return $this->deduplicateParams($params);
    }

    /**
     * Get parameters from method signature
     */
    private function getMethodParameters(string $controllerClass, string $method): array
    {
        $params = [];

        try {
            if (!class_exists($controllerClass)) {
                return [];
            }

            $reflection = new ReflectionClass($controllerClass);
            if (!$reflection->hasMethod($method)) {
                return [];
            }

            $reflectionMethod = $reflection->getMethod($method);
            $methodParams = $reflectionMethod->getParameters();

            foreach ($methodParams as $param) {
                if ($param->getName() === 'request' || $param->getName() === 'id') {
                    continue;
                }

                $type = $this->getParameterType($param);

                $params[] = [
                    'name' => $param->getName(),
                    'type' => $type,
                    'required' => !$param->isOptional(),
                    'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                    'source' => 'method_signature',
                ];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return $params;
    }

    /**
     * Get parameter type
     */
    private function getParameterType(ReflectionParameter $param): string
    {
        $type = $param->getType();

        if ($type === null) {
            return 'mixed';
        }

        $typeName = $type->getName();

        return match ($typeName) {
            'int' => 'integer',
            'bool' => 'boolean',
            'float' => 'number',
            'array' => 'array',
            default => $typeName,
        };
    }

    /**
     * Get parameters from Request validation rules
     */
    private function getRequestValidationParams(string $controllerClass, string $method): array
    {
        $params = [];

        try {
            if (!class_exists($controllerClass)) {
                return [];
            }

            $reflection = new ReflectionClass($controllerClass);
            if (!$reflection->hasMethod($method)) {
                return [];
            }

            $reflectionMethod = $reflection->getMethod($method);
            $methodParams = $reflectionMethod->getParameters();

            foreach ($methodParams as $param) {
                $paramType = $param->getType();
                if (!$paramType) {
                    continue;
                }

                $className = $paramType->getName();

                // Check if it's a FormRequest
                if (class_exists($className) && is_subclass_of($className, \Illuminate\Foundation\Http\FormRequest::class)) {
                    $requestInstance = new $className();
                    $rules = $requestInstance->rules();

                    foreach ($rules as $field => $rule) {
                        $params[] = [
                            'name' => $field,
                            'type' => $this->getRuleType($rule),
                            'required' => $this->isRequired($rule),
                            'rules' => is_array($rule) ? implode('|', $rule) : $rule,
                            'source' => 'form_request',
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return $params;
    }

    /**
     * Get type from validation rule
     */
    private function getRuleType($rules): string
    {
        $ruleString = is_array($rules) ? implode('|', $rules) : $rules;

        if (str_contains($ruleString, 'integer')) return 'integer';
        if (str_contains($ruleString, 'numeric')) return 'number';
        if (str_contains($ruleString, 'boolean')) return 'boolean';
        if (str_contains($ruleString, 'array')) return 'array';
        if (str_contains($ruleString, 'date')) return 'date';
        if (str_contains($ruleString, 'email')) return 'email';
        if (str_contains($ruleString, 'url')) return 'url';

        return 'string';
    }

    /**
     * Check if field is required
     */
    private function isRequired($rules): bool
    {
        $ruleString = is_array($rules) ? implode('|', $rules) : $rules;
        return str_contains($ruleString, 'required');
    }

    /**
     * Check if method uses pagination
     */
    private function usesPagination(string $controllerClass, string $method): bool
    {
        try {
            if (!class_exists($controllerClass)) {
                return false;
            }

            $reflection = new ReflectionClass($controllerClass);
            if (!$reflection->hasMethod($method)) {
                return false;
            }

            $reflectionMethod = $reflection->getMethod($method);
            $startLine = $reflectionMethod->getStartLine();
            $endLine = $reflectionMethod->getEndLine();
            $filename = $reflectionMethod->getFileName();

            // Read only the method's source code
            $source = implode("\n", array_slice(
                file($filename),
                $startLine - 1,
                $endLine - $startLine + 1
            ));

            // Check if method contains pagination
            return str_contains($source, '->paginate(') ||
                   str_contains($source, '->simplePaginate(') ||
                   str_contains($source, 'sendPaginatorResponse');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get parameters from Request usage in method body
     */
    private function getRequestUsageParams(string $controllerClass, string $method): array
    {
        $params = [];

        try {
            if (!class_exists($controllerClass)) {
                return [];
            }

            $reflection = new ReflectionClass($controllerClass);
            if (!$reflection->hasMethod($method)) {
                return [];
            }

            $reflectionMethod = $reflection->getMethod($method);
            $startLine = $reflectionMethod->getStartLine();
            $endLine = $reflectionMethod->getEndLine();
            $filename = $reflectionMethod->getFileName();

            // Read only the method's source code
            $source = implode("\n", array_slice(
                file($filename),
                $startLine - 1,
                $endLine - $startLine + 1
            ));

            // Pattern to match various request parameter access patterns
            $patterns = [
                // $request->has('param')
                '/\$request->has\([\'"]([a-zA-Z0-9_]+)[\'"]\)/',
                // $request->input('param')
                '/\$request->input\([\'"]([a-zA-Z0-9_]+)[\'"]/',
                // $request->get('param')
                '/\$request->get\([\'"]([a-zA-Z0-9_]+)[\'"]/',
                // $request->param or $request->query('param')
                '/\$request->query\([\'"]([a-zA-Z0-9_]+)[\'"]/',
                // $request->param (property access)
                '/\$request->([a-zA-Z0-9_]+)(?![a-zA-Z0-9_\(])/',
            ];

            $foundParams = [];
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $source, $matches)) {
                    foreach ($matches[1] as $paramName) {
                        // Skip common non-parameter names
                        if (in_array($paramName, ['user', 'all', 'validated', 'authorize', 'rules', 'route', 'method', 'is', 'has', 'filled', 'missing', 'only', 'except'])) {
                            continue;
                        }

                        $foundParams[$paramName] = true;
                    }
                }
            }

            // Convert found params to parameter array
            foreach (array_keys($foundParams) as $paramName) {
                $type = $this->guessParameterType($paramName, $source);
                $required = $this->isParamRequired($paramName, $source);
                $description = $this->generateParamDescription($paramName);

                // Add note if both singular and plural versions exist
                if (str_ends_with($paramName, '_ids')) {
                    $singularName = substr($paramName, 0, -1); // Remove 's' from '_ids'
                    if (isset($foundParams[$singularName])) {
                        $description .= ". Note: Use either this (for multiple) OR '{$singularName}' (for single), not both.";
                    }
                }

                $params[] = [
                    'name' => $paramName,
                    'type' => $type,
                    'required' => $required,
                    'description' => $description,
                    'source' => 'request_usage',
                ];
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return $params;
    }

    /**
     * Guess parameter type based on name and usage context
     */
    private function guessParameterType(string $paramName, string $source): string
    {
        // Check for type hints in the source
        if (preg_match('/\(int\)\s*\$request->' . preg_quote($paramName) . '/', $source)) {
            return 'integer';
        }
        if (preg_match('/\(bool\)\s*\$request->' . preg_quote($paramName) . '/', $source)) {
            return 'boolean';
        }
        if (preg_match('/\(float\)\s*\$request->' . preg_quote($paramName) . '/', $source)) {
            return 'number';
        }

        // Check if it's used with is_array() - indicates array type
        if (preg_match('/is_array\s*\(\s*\$.*?' . preg_quote($paramName) . '/', $source)) {
            return 'array';
        }

        // Guess from common naming patterns
        // Array types (plural IDs)
        if (str_ends_with($paramName, '_ids') || str_ends_with($paramName, 's[]')) {
            return 'array';
        }

        // Single ID
        if (str_ends_with($paramName, '_id') || $paramName === 'id') {
            return 'integer';
        }

        // Boolean flags
        if (str_starts_with($paramName, 'is_') || str_starts_with($paramName, 'has_')) {
            return 'boolean';
        }

        // Numbers
        if (in_array($paramName, ['page', 'per_page', 'limit', 'offset', 'count'])) {
            return 'integer';
        }

        // Strings
        if (in_array($paramName, ['search', 'query', 'keyword', 'term', 'name'])) {
            return 'string';
        }

        return 'string';
    }

    /**
     * Check if parameter is required based on usage
     */
    private function isParamRequired(string $paramName, string $source): bool
    {
        // If we see $request->has() or null coalescing, it's likely optional
        if (preg_match('/\$request->has\([\'"]' . preg_quote($paramName) . '[\'"]\)/', $source)) {
            return false;
        }
        if (preg_match('/\$request->' . preg_quote($paramName) . '\s*\?\?/', $source)) {
            return false;
        }
        if (preg_match('/\$request->input\([\'"]' . preg_quote($paramName) . '[\'"],\s*.+\)/', $source)) {
            return false; // Has default value
        }

        return false; // Default to optional for request usage params
    }

    /**
     * Generate a human-readable description for the parameter
     */
    private function generateParamDescription(string $paramName): string
    {
        // Convert snake_case to Title Case
        $words = explode('_', $paramName);
        $title = implode(' ', array_map('ucfirst', $words));

        // Handle array/multiple IDs (branch_ids, category_ids, etc.)
        if (str_ends_with($paramName, '_ids')) {
            $entity = str_replace(' Ids', '', $title);
            return "Array of {$entity} IDs (for multiple selection)";
        }

        // Handle single ID
        if (str_ends_with($paramName, '_id')) {
            $entity = str_replace(' Id', '', $title);
            return "Single {$entity} ID";
        }

        // Common parameter descriptions
        $descriptions = [
            'search' => 'Search keyword for filtering results',
            'query' => 'Search query string',
            'keyword' => 'Keyword for searching',
            'status' => 'Filter by status',
            'type' => 'Filter by type',
            'category' => 'Filter by category',
            'sort_by' => 'Field name to sort by',
            'sort_order' => 'Sort order (asc or desc)',
            'order_by' => 'Field name to order by',
            'limit' => 'Maximum number of results',
            'offset' => 'Number of results to skip',
            'from_date' => 'Start date for filtering',
            'to_date' => 'End date for filtering',
            'start_date' => 'Start date',
            'end_date' => 'End date',
        ];

        if (isset($descriptions[$paramName])) {
            return $descriptions[$paramName];
        }

        return "Filter by {$title}";
    }

    /**
     * Get common pagination parameters
     */
    private function getPaginationParams(): array
    {
        return [
            [
                'name' => 'page',
                'type' => 'integer',
                'required' => false,
                'default' => 1,
                'description' => 'Page number for pagination',
                'source' => 'pagination',
            ],
            [
                'name' => 'per_page',
                'type' => 'integer',
                'required' => false,
                'default' => 20,
                'description' => 'Number of items per page',
                'source' => 'pagination',
            ],
        ];
    }

    /**
     * Remove duplicate parameters and merge information
     */
    private function deduplicateParams(array $params): array
    {
        $merged = [];

        foreach ($params as $param) {
            $name = $param['name'];

            if (!isset($merged[$name])) {
                $merged[$name] = $param;
            } else {
                // Merge parameter info, prioritizing form_request > request_usage > others
                $existing = $merged[$name];
                $newParam = $param;

                // Prefer form_request source for type and validation rules
                if ($newParam['source'] === 'form_request') {
                    $merged[$name] = array_merge($existing, $newParam);
                } elseif ($existing['source'] === 'form_request') {
                    // Keep existing, but add description if available
                    if (!empty($newParam['description']) && empty($existing['description'])) {
                        $merged[$name]['description'] = $newParam['description'];
                    }
                } else {
                    // For other sources, merge intelligently
                    if (!empty($newParam['description'])) {
                        $merged[$name]['description'] = $newParam['description'];
                    }
                    if (isset($newParam['rules'])) {
                        $merged[$name]['rules'] = $newParam['rules'];
                    }
                }
            }
        }

        return array_values($merged);
    }
}
