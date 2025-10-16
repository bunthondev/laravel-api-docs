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
     * Remove duplicate parameters
     */
    private function deduplicateParams(array $params): array
    {
        $seen = [];
        $result = [];

        foreach ($params as $param) {
            $name = $param['name'];

            if (!isset($seen[$name])) {
                $seen[$name] = true;
                $result[] = $param;
            }
        }

        return $result;
    }
}
