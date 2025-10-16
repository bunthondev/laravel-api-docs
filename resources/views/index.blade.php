<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $documentation['title'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .header .meta {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .content {
            padding: 2rem 0;
        }

        .controller-section {
            background: white;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .controller-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 2px solid #e9ecef;
        }

        .controller-header h2 {
            color: #495057;
            font-size: 1.5rem;
        }

        .route {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .route:last-child {
            border-bottom: none;
        }

        .route-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
        }

        .route-header:hover {
            opacity: 0.8;
        }

        .method-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .method-get { background: #d1ecf1; color: #0c5460; }
        .method-post { background: #d4edda; color: #155724; }
        .method-put { background: #fff3cd; color: #856404; }
        .method-patch { background: #f8d7da; color: #721c24; }
        .method-delete { background: #f5c6cb; color: #721c24; }

        .route-path {
            font-family: 'Courier New', monospace;
            font-size: 1rem;
            color: #495057;
            flex: 1;
        }

        .toggle-icon {
            font-size: 1.2rem;
            color: #6c757d;
        }

        .route-details {
            margin-left: 2rem;
            display: none;
        }

        .route-details.active {
            display: block;
        }

        .section {
            margin-bottom: 1.5rem;
        }

        .section h4 {
            color: #495057;
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }

        .description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            white-space: pre-line;
        }

        .params-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .params-table th,
        .params-table td {
            padding: 0.75rem;
            text-align: left;
            border: 1px solid #dee2e6;
        }

        .params-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .params-table td {
            background: white;
        }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-required {
            background: #dc3545;
            color: white;
        }

        .badge-optional {
            background: #6c757d;
            color: white;
        }

        .code-block {
            background: #282c34;
            color: #abb2bf;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .empty-state {
            color: #6c757d;
            font-style: italic;
            padding: 0.5rem 0;
        }

        .source-badge {
            font-size: 0.7rem;
            padding: 0.15rem 0.4rem;
            background: #e9ecef;
            color: #495057;
            border-radius: 3px;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>{{ $documentation['title'] }}</h1>
            <p>Version {{ $documentation['version'] }}</p>
            <div class="meta">
                <span>Base URL: <strong>{{ $documentation['base_url'] }}</strong></span>
                <span>Generated: {{ \Carbon\Carbon::parse($documentation['generated_at'])->format('Y-m-d H:i:s') }}</span>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container">
            @foreach($documentation['controllers'] as $controller)
                <div class="controller-section">
                    <div class="controller-header">
                        <h2>{{ $controller['name'] }}</h2>
                    </div>

                    @foreach($controller['routes'] as $route)
                        <div class="route">
                            <div class="route-header" onclick="toggleRoute(this)">
                                <div>
                                    @foreach($route['methods'] as $method)
                                        @if($method !== 'HEAD')
                                            <span class="method-badge method-{{ strtolower($method) }}">{{ $method }}</span>
                                        @endif
                                    @endforeach
                                </div>
                                <div class="route-path">{{ $route['uri'] }}</div>
                                <div class="toggle-icon">▼</div>
                            </div>

                            <div class="route-details">
                                @if($route['docblock'])
                                    <div class="section">
                                        <div class="description">{{ $route['docblock'] }}</div>
                                    </div>
                                @endif

                                @if(isset($route['parameters']) && count($route['parameters']) > 0)
                                    <div class="section">
                                        <h4>Path Parameters</h4>
                                        <table class="params-table">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Required</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($route['parameters'] as $param)
                                                    <tr>
                                                        <td><code>{{ $param['name'] }}</code></td>
                                                        <td>
                                                            @if($param['required'])
                                                                <span class="badge badge-required">Required</span>
                                                            @else
                                                                <span class="badge badge-optional">Optional</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $param['description'] ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if(isset($route['query_params']) && count($route['query_params']) > 0)
                                    <div class="section">
                                        <h4>Query Parameters</h4>
                                        <table class="params-table">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Required</th>
                                                    <th>Default</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($route['query_params'] as $param)
                                                    <tr>
                                                        <td>
                                                            <code>{{ $param['name'] }}</code>
                                                            @if(isset($param['source']))
                                                                <span class="source-badge">{{ $param['source'] }}</span>
                                                            @endif
                                                        </td>
                                                        <td><code>{{ $param['type'] }}</code></td>
                                                        <td>
                                                            @if($param['required'] ?? false)
                                                                <span class="badge badge-required">Required</span>
                                                            @else
                                                                <span class="badge badge-optional">Optional</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ isset($param['default']) ? json_encode($param['default']) : '-' }}</td>
                                                        <td>{{ $param['description'] ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if(isset($route['body_fields']) && count($route['body_fields']) > 0)
                                    <div class="section">
                                        <h4>Request Body</h4>
                                        <table class="params-table">
                                            <thead>
                                                <tr>
                                                    <th>Field</th>
                                                    <th>Type</th>
                                                    <th>Required</th>
                                                    <th>Rules</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($route['body_fields'] as $field)
                                                    <tr>
                                                        <td>
                                                            <code>{{ $field['name'] }}</code>
                                                            @if(isset($field['source']))
                                                                <span class="source-badge">{{ $field['source'] }}</span>
                                                            @endif
                                                        </td>
                                                        <td><code>{{ $field['type'] }}</code></td>
                                                        <td>
                                                            @if($field['required'] ?? false)
                                                                <span class="badge badge-required">Required</span>
                                                            @else
                                                                <span class="badge badge-optional">Optional</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $field['rules'] ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if(isset($route['table_schema']['columns']) && count($route['table_schema']['columns']) > 0)
                                    <div class="section">
                                        <h4>Database Fields ({{ $route['table_schema']['table'] }})</h4>
                                        <table class="params-table">
                                            <thead>
                                                <tr>
                                                    <th>Column</th>
                                                    <th>Type</th>
                                                    <th>Nullable</th>
                                                    <th>Default</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($route['table_schema']['columns'] as $column)
                                                    <tr>
                                                        <td><code>{{ $column['name'] }}</code></td>
                                                        <td><code>{{ $column['type'] }}</code></td>
                                                        <td>{{ $column['nullable'] ? 'Yes' : 'No' }}</td>
                                                        <td>{{ $column['default'] ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if(isset($route['response_example']))
                                    <div class="section">
                                        <h4>Response Example</h4>
                                        <pre class="code-block">{{ json_encode($route['response_example'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <script>
        function toggleRoute(element) {
            const details = element.nextElementSibling;
            const icon = element.querySelector('.toggle-icon');

            if (details.classList.contains('active')) {
                details.classList.remove('active');
                icon.textContent = '▼';
            } else {
                details.classList.add('active');
                icon.textContent = '▲';
            }
        }
    </script>
</body>
</html>
