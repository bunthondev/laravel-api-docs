<?php

namespace Puchan\LaravelApiDocs\Http\Controllers;

use Puchan\LaravelApiDocs\Services\ApiDocGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ApiDocController extends Controller
{
    private ApiDocGenerator $generator;

    public function __construct(ApiDocGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Display API documentation page
     */
    public function index(): View
    {
        $documentation = $this->generator->generate();

        return view('api-docs::index', compact('documentation'));
    }

    /**
     * Get API documentation as JSON
     */
    public function json(): JsonResponse
    {
        $documentation = $this->generator->generate();

        return response()->json($documentation);
    }

    /**
     * Export documentation as OpenAPI/Swagger format
     */
    public function swagger(): JsonResponse
    {
        $documentation = $this->generator->generate();

        // Convert to OpenAPI 3.0 format
        $swagger = $this->convertToOpenApi($documentation);

        return response()->json($swagger);
    }

    /**
     * Convert documentation to OpenAPI format
     */
    private function convertToOpenApi(array $doc): array
    {
        $paths = [];

        foreach ($doc['controllers'] as $controller) {
            foreach ($controller['routes'] as $route) {
                $path = '/' . $route['uri'];

                foreach ($route['methods'] as $method) {
                    if ($method === 'HEAD') continue;

                    $method = strtolower($method);

                    if (!isset($paths[$path])) {
                        $paths[$path] = [];
                    }

                    $paths[$path][$method] = [
                        'summary' => $route['name'] ?? $route['method'],
                        'description' => $route['docblock'] ?? '',
                        'tags' => [$controller['name']],
                        'parameters' => $this->formatParameters($route),
                        'responses' => [
                            '200' => [
                                'description' => 'Successful response',
                                'content' => [
                                    'application/json' => [
                                        'example' => $route['response_example'],
                                    ],
                                ],
                            ],
                        ],
                    ];

                    // Add request body for POST/PUT/PATCH
                    if (in_array($method, ['post', 'put', 'patch']) && isset($route['body_fields'])) {
                        $paths[$path][$method]['requestBody'] = [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => $this->formatProperties($route['body_fields']),
                                    ],
                                ],
                            ],
                        ];
                    }
                }
            }
        }

        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $doc['title'],
                'version' => $doc['version'],
                'description' => 'Auto-generated API documentation',
            ],
            'servers' => [
                ['url' => $doc['base_url']],
            ],
            'paths' => $paths,
        ];
    }

    /**
     * Format parameters for OpenAPI
     */
    private function formatParameters(array $route): array
    {
        $parameters = [];

        // Add path parameters
        foreach ($route['parameters'] ?? [] as $param) {
            $parameters[] = [
                'name' => $param['name'],
                'in' => 'path',
                'required' => $param['required'],
                'schema' => ['type' => 'string'],
            ];
        }

        // Add query parameters
        foreach ($route['query_params'] ?? [] as $param) {
            $parameters[] = [
                'name' => $param['name'],
                'in' => 'query',
                'required' => $param['required'] ?? false,
                'schema' => ['type' => $param['type']],
                'description' => $param['description'] ?? '',
            ];
        }

        return $parameters;
    }

    /**
     * Format properties for OpenAPI schema
     */
    private function formatProperties(array $fields): array
    {
        $properties = [];

        foreach ($fields as $field) {
            $properties[$field['name']] = [
                'type' => $field['type'],
            ];

            if (isset($field['max_length'])) {
                $properties[$field['name']]['maxLength'] = $field['max_length'];
            }

            if (isset($field['description'])) {
                $properties[$field['name']]['description'] = $field['description'];
            }
        }

        return $properties;
    }
}
