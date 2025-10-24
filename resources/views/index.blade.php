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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background: #ecf0f1;
            font-size: 14px;
            height: 100vh;
            overflow: hidden;
        }

        .app-container {
            display: flex;
            height: 100vh;
            position: relative;
        }

        /* Left Sidebar */
        .sidebar {
            width: 280px;
            background: #34495e;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            background: #2c3e50;
            color: white;
        }

        .sidebar-header h1 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .sidebar-header .meta {
            font-size: 0.75rem;
            opacity: 0.8;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .sidebar-search {
            padding: 1rem 1.5rem;
            background: #2c3e50;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            font-size: 0.875rem;
            background: #34495e;
            color: white;
        }

        .search-input::placeholder {
            color: #95a5a6;
        }

        .search-input:focus {
            outline: 2px solid #3498db;
            background: #2c3e50;
        }

        .controllers-list {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem;
        }

        .controller-item {
            margin-bottom: 0.25rem;
            cursor: pointer;
            background: #2c3e50;
            border-left: 4px solid transparent;
            transition: all 0.2s;
        }

        .controller-item:hover {
            background: #34495e;
            border-left-color: #3498db;
        }

        .controller-item.active {
            background: #3498db;
            border-left-color: #2980b9;
        }

        .controller-item-inner {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .controller-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: #ecf0f1;
        }

        .controller-routes-count {
            font-size: 0.75rem;
            background: #34495e;
            color: #bdc3c7;
            padding: 0.25rem 0.625rem;
            border-radius: 3px;
            font-weight: 600;
        }

        .controller-item.active .controller-routes-count {
            background: #2980b9;
            color: white;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #ecf0f1;
            position: relative;
            overflow: hidden;
        }

        .content-header {
            background: white;
            padding: 2rem 3rem;
            border-bottom: 3px solid #3498db;
        }

        .content-header h2 {
            font-size: 1.75rem;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .content-header .subtitle {
            color: #7f8c8d;
            font-size: 0.875rem;
        }

        .content-header-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid;
            background: white;
            transition: all 0.2s;
            font-family: inherit;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-export {
            border-color: #27ae60;
            color: #27ae60;
        }

        .btn-export:hover {
            background: #27ae60;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }

        .btn-copy {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid #3498db;
            background: white;
            color: #3498db;
            transition: all 0.2s;
            font-family: inherit;
        }

        .btn-copy:hover {
            background: #3498db;
            color: white;
        }

        .btn-copy.copied {
            background: #27ae60;
            border-color: #27ae60;
            color: white;
        }

        .routes-container {
            flex: 1;
            overflow-y: auto;
            padding: 2rem 3rem;
        }

        /* Route Card */
        .route-card {
            background: white;
            margin-bottom: 1rem;
            border-left: 4px solid #3498db;
        }

        .route-header {
            padding: 1.25rem 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: background 0.2s;
        }

        .route-header:hover {
            background: #f8f9fa;
        }

        .route-header.expanded {
            background: #3498db;
            color: white;
        }

        .route-methods {
            display: flex;
            gap: 0.5rem;
        }

        .method-badge {
            padding: 0.375rem 0.75rem;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white;
        }

        .method-get {
            background: #27ae60;
        }

        .method-post {
            background: #3498db;
        }

        .method-put {
            background: #f39c12;
        }

        .method-patch {
            background: #9b59b6;
        }

        .method-delete {
            background: #e74c3c;
        }

        .route-path {
            flex: 1;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            font-size: 0.9375rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .route-header.expanded .route-path {
            color: white;
        }

        .toggle-icon {
            font-size: 1rem;
            color: #95a5a6;
            transition: transform 0.3s;
        }

        .route-header.expanded .toggle-icon {
            transform: rotate(180deg);
            color: white;
        }

        .route-details {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.4s ease-in-out, padding 0.4s ease-in-out;
            background: #f8f9fa;
            overflow: hidden;
        }

        .route-details.active {
            grid-template-rows: 1fr;
            padding: 2rem 0;
            border-top: 2px solid #bdc3c7;
        }

        .route-details-inner {
            overflow: hidden;
            padding: 0 2rem;
        }

        .description {
            color: #2c3e50;
            font-size: 0.9375rem;
            padding: 1rem 1.25rem;
            background: white;
            border-left: 4px solid #3498db;
            margin-bottom: 1.5rem;
        }

        /* Tabs */
        .tab-group {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .tab {
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            background: white;
            border: 2px solid #bdc3c7;
            color: #7f8c8d;
            transition: all 0.2s;
        }

        .tab:hover {
            border-color: #3498db;
            color: #3498db;
        }

        .tab.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Params Grid */
        .params-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .param-item {
            background: white;
            padding: 1rem 1.25rem;
            border-left: 3px solid #3498db;
        }

        .param-name {
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            color: #2c3e50;
            font-weight: 700;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .param-meta {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .param-type {
            font-family: 'SF Mono', Monaco, monospace;
            color: #9b59b6;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white;
        }

        .badge-required {
            background: #e74c3c;
        }

        .badge-optional {
            background: #95a5a6;
        }

        .source-badge {
            font-size: 0.6875rem;
            padding: 0.25rem 0.5rem;
            background: #ecf0f1;
            color: #7f8c8d;
            font-weight: 600;
        }

        /* Code Block */
        .code-block {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 1.5rem;
            overflow-x: auto;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            font-size: 0.8125rem;
            line-height: 1.6;
            max-height: 500px;
        }

        /* Database Table */
        .db-table {
            background: white;
            overflow: hidden;
        }

        .db-table-header {
            background: #34495e;
            padding: 0.875rem 1rem;
            display: grid;
            grid-template-columns: 2fr 1.5fr 1fr 1.5fr;
            gap: 1rem;
            font-weight: 700;
            font-size: 0.75rem;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .db-table-row {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid #ecf0f1;
            display: grid;
            grid-template-columns: 2fr 1.5fr 1fr 1.5fr;
            gap: 1rem;
            font-size: 0.875rem;
            transition: background 0.2s;
        }

        .db-table-row:last-child {
            border-bottom: none;
        }

        .db-table-row:hover {
            background: #f8f9fa;
        }

        .db-col-name {
            font-family: 'SF Mono', Monaco, monospace;
            color: #2c3e50;
            font-weight: 700;
        }

        .db-col-type {
            font-family: 'SF Mono', Monaco, monospace;
            color: #9b59b6;
            font-weight: 600;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #7f8c8d;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        ::-webkit-scrollbar-track {
            background: #ecf0f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #95a5a6;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #7f8c8d;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                position: absolute;
                left: -280px;
                height: 100%;
                transition: left 0.3s;
                z-index: 100;
            }

            .sidebar.open {
                left: 0;
            }

            .params-grid {
                grid-template-columns: 1fr;
            }

            .db-table-header,
            .db-table-row {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- Left Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h1>{{ $documentation['title'] }}</h1>
                <div class="meta">
                    <span>v{{ $documentation['version'] }}</span>
                    <span>{{ \Carbon\Carbon::parse($documentation['generated_at'])->format('M d, Y') }}</span>
                </div>
            </div>

            <div class="sidebar-search">
                <input type="text" class="search-input" placeholder="Search controllers..." id="searchInput" oninput="filterControllers()">
            </div>

            <div class="controllers-list" id="controllersList">
                @foreach ($documentation['controllers'] as $index => $controller)
                    <div class="controller-item {{ $index === 0 ? 'active' : '' }}"
                         data-controller-index="{{ $index }}"
                         onclick="selectController({{ $index }})">
                        <div class="controller-item-inner">
                            <span class="controller-name">{{ $controller['name'] }}</span>
                            <span class="controller-routes-count">{{ count($controller['routes']) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            @foreach ($documentation['controllers'] as $index => $controller)
                <div class="controller-content" id="controller-{{ $index }}" style="display: {{ $index === 0 ? 'flex' : 'none' }}; flex-direction: column; height: 100%;">
                    <div class="content-header">
                        <h2>{{ $controller['name'] }}</h2>
                        <div class="subtitle">{{ count($controller['routes']) }} endpoints available</div>
                        <div class="content-header-actions">
                            <button class="btn btn-export" onclick="exportPostmanCollection({{ $index }})">Export Postman Collection</button>
                        </div>
                    </div>

                    <div class="routes-container">
                        @if (count($controller['routes']) > 0)
                            @foreach ($controller['routes'] as $route)
                                <div class="route-card" data-route='@json($route)'>
                                    <div class="route-header">
                                        <div class="route-methods">
                                            @foreach ($route['methods'] as $method)
                                                @if ($method !== 'HEAD')
                                                    <span class="method-badge method-{{ strtolower($method) }}">{{ $method }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                        <div class="route-path" onclick="toggleRoute(this.parentElement)">{{ $route['uri'] }}</div>
                                        <div class="action-buttons">
                                            <button class="btn-copy" onclick="event.stopPropagation(); copyCurl(this)">Copy cURL</button>
                                        </div>
                                        <div class="toggle-icon" onclick="toggleRoute(this.parentElement)">▼</div>
                                    </div>

                                    <div class="route-details">
                                        <div class="route-details-inner">
                                        @if ($route['docblock'])
                                            <div class="description">{{ $route['docblock'] }}</div>
                                        @endif

                                        <div class="tab-group">
                                            @if (isset($route['parameters']) && count($route['parameters']) > 0)
                                                <div class="tab active" onclick="switchTab(this, 'params')">Path Parameters</div>
                                            @endif
                                            @if (isset($route['query_params']) && count($route['query_params']) > 0)
                                                <div class="tab {{ !isset($route['parameters']) || count($route['parameters']) === 0 ? 'active' : '' }}"
                                                     onclick="switchTab(this, 'query')">Query Parameters</div>
                                            @endif
                                            @if (isset($route['body_fields']) && count($route['body_fields']) > 0)
                                                <div class="tab" onclick="switchTab(this, 'body')">Request Body</div>
                                            @endif
                                            @if (isset($route['table_schema']['columns']) && count($route['table_schema']['columns']) > 0)
                                                <div class="tab" onclick="switchTab(this, 'db')">Database Schema</div>
                                            @endif
                                            @if (isset($route['response_example']))
                                                <div class="tab" onclick="switchTab(this, 'response')">Response Example</div>
                                            @endif
                                        </div>

                                        @if (isset($route['parameters']) && count($route['parameters']) > 0)
                                            <div class="tab-content active" data-tab="params">
                                                <div class="params-grid">
                                                    @foreach ($route['parameters'] as $param)
                                                        <div class="param-item">
                                                            <div class="param-name">{{ $param['name'] }}</div>
                                                            <div class="param-meta">
                                                                @if ($param['required'])
                                                                    <span class="badge badge-required">Required</span>
                                                                @else
                                                                    <span class="badge badge-optional">Optional</span>
                                                                @endif
                                                                <span class="param-type">path parameter</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if (isset($route['query_params']) && count($route['query_params']) > 0)
                                            <div class="tab-content {{ !isset($route['parameters']) || count($route['parameters']) === 0 ? 'active' : '' }}"
                                                 data-tab="query">
                                                <div class="db-table">
                                                    <div class="db-table-header" style="grid-template-columns: 2fr 1fr 1fr 3fr;">
                                                        <div>Parameter</div>
                                                        <div>Type</div>
                                                        <div>Required</div>
                                                        <div>Description</div>
                                                    </div>
                                                    @foreach ($route['query_params'] as $param)
                                                        <div class="db-table-row" style="grid-template-columns: 2fr 1fr 1fr 3fr;">
                                                            <div class="db-col-name">{{ $param['name'] }}</div>
                                                            <div class="db-col-type">{{ $param['type'] }}</div>
                                                            <div>
                                                                @if ($param['required'] ?? false)
                                                                    <span class="badge badge-required">Yes</span>
                                                                @else
                                                                    <span class="badge badge-optional">No</span>
                                                                @endif
                                                            </div>
                                                            <div style="font-size: 0.8125rem; color: #7f8c8d;">
                                                                {{ $param['description'] ?? ($param['rules'] ?? '-') }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if (isset($route['body_fields']) && count($route['body_fields']) > 0)
                                            <div class="tab-content" data-tab="body">
                                                <div class="db-table">
                                                    <div class="db-table-header" style="grid-template-columns: 2fr 1fr 1fr 3fr;">
                                                        <div>Field</div>
                                                        <div>Type</div>
                                                        <div>Required</div>
                                                        <div>Validation Rules</div>
                                                    </div>
                                                    @foreach ($route['body_fields'] as $field)
                                                        <div class="db-table-row" style="grid-template-columns: 2fr 1fr 1fr 3fr;">
                                                            <div class="db-col-name">{{ $field['name'] }}</div>
                                                            <div class="db-col-type">{{ $field['type'] }}</div>
                                                            <div>
                                                                @if ($field['required'] ?? false)
                                                                    <span class="badge badge-required">Yes</span>
                                                                @else
                                                                    <span class="badge badge-optional">No</span>
                                                                @endif
                                                            </div>
                                                            <div style="font-size: 0.8125rem; color: #7f8c8d;">
                                                                {{ $field['rules'] ?? '-' }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if (isset($route['table_schema']['columns']) && count($route['table_schema']['columns']) > 0)
                                            <div class="tab-content" data-tab="db">
                                                <div class="db-table">
                                                    <div class="db-table-header">
                                                        <div>Column</div>
                                                        <div>Type</div>
                                                        <div>Nullable</div>
                                                        <div>Default</div>
                                                    </div>
                                                    @foreach ($route['table_schema']['columns'] as $column)
                                                        <div class="db-table-row">
                                                            <div class="db-col-name">{{ $column['name'] }}</div>
                                                            <div class="db-col-type">{{ $column['type'] }}</div>
                                                            <div>{{ $column['nullable'] ? 'Yes' : 'No' }}</div>
                                                            <div>{{ $column['default'] ?? '-' }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if (isset($route['response_example']))
                                            <div class="tab-content" data-tab="response">
                                                <pre class="code-block">{{ json_encode($route['response_example'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <div class="empty-state-title">No Routes Found</div>
                                <p>This controller doesn't have any routes yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        // Select Controller
        function selectController(index) {
            // Update sidebar
            document.querySelectorAll('.controller-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-controller-index="${index}"]`).classList.add('active');

            // Update content
            document.querySelectorAll('.controller-content').forEach(content => {
                content.style.display = 'none';
            });
            document.getElementById(`controller-${index}`).style.display = 'flex';
        }

        // Toggle Route Details
        function toggleRoute(element) {
            const details = element.nextElementSibling;
            const isExpanded = element.classList.contains('expanded');

            if (isExpanded) {
                details.classList.remove('active');
                element.classList.remove('expanded');
            } else {
                details.classList.add('active');
                element.classList.add('expanded');
            }
        }

        // Switch Tab
        function switchTab(tabElement, tabName) {
            const routeDetails = tabElement.closest('.route-details');

            // Update tabs
            routeDetails.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            tabElement.classList.add('active');

            // Update content
            routeDetails.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            routeDetails.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
        }

        // Filter Controllers
        function filterControllers() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const controllers = document.querySelectorAll('.controller-item');

            controllers.forEach(controller => {
                const name = controller.querySelector('.controller-name').textContent.toLowerCase();
                if (name.includes(searchValue)) {
                    controller.style.display = 'block';
                } else {
                    controller.style.display = 'none';
                }
            });
        }

        // Copy cURL command
        function copyCurl(button) {
            const routeCard = button.closest('.route-card');
            const route = JSON.parse(routeCard.dataset.route);
            const baseUrl = '{{ $documentation["base_url"] }}';

            // Get primary method (first non-HEAD method)
            const method = route.methods.find(m => m !== 'HEAD') || 'GET';

            // Build URL
            let url = baseUrl + '/' + route.uri;

            // Replace path parameters with placeholders
            if (route.parameters && route.parameters.length > 0) {
                route.parameters.forEach(param => {
                    url = url.replace(`{${param.name}}`, `<${param.name}>`);
                });
            }

            // Build cURL command
            let curl = `curl -X ${method} "${url}"`;

            // Add headers
            curl += ' \\\n  -H "Accept: application/json"';
            curl += ' \\\n  -H "Content-Type: application/json"';

            // Add body for POST, PUT, PATCH
            if (['POST', 'PUT', 'PATCH'].includes(method) && route.body_fields && route.body_fields.length > 0) {
                const bodyExample = {};
                route.body_fields.forEach(field => {
                    if (field.type === 'string') {
                        bodyExample[field.name] = `<${field.name}>`;
                    } else if (field.type === 'integer' || field.type === 'number') {
                        bodyExample[field.name] = 0;
                    } else if (field.type === 'boolean') {
                        bodyExample[field.name] = true;
                    } else {
                        bodyExample[field.name] = `<${field.name}>`;
                    }
                });
                curl += ` \\\n  -d '${JSON.stringify(bodyExample, null, 2)}'`;
            }

            // Copy to clipboard
            navigator.clipboard.writeText(curl).then(() => {
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.classList.add('copied');
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('copied');
                }, 2000);
            });
        }

        // Export Postman Collection
        function exportPostmanCollection(controllerIndex) {
            const documentation = @json($documentation);
            const controller = documentation.controllers[controllerIndex];

            const collection = {
                info: {
                    name: controller.name,
                    description: `API Collection for ${controller.name}`,
                    schema: "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
                },
                item: []
            };

            controller.routes.forEach(route => {
                const method = route.methods.find(m => m !== 'HEAD') || 'GET';
                let url = documentation.base_url + '/' + route.uri;

                // Parse URL variables
                const urlVariables = [];
                if (route.parameters && route.parameters.length > 0) {
                    route.parameters.forEach(param => {
                        url = url.replace(`{${param.name}}`, `:${param.name}`);
                        urlVariables.push({
                            key: param.name,
                            value: `<${param.name}>`,
                            description: param.required ? 'Required' : 'Optional'
                        });
                    });
                }

                const item = {
                    name: route.uri,
                    request: {
                        method: method,
                        header: [
                            {
                                key: "Accept",
                                value: "application/json",
                                type: "text"
                            },
                            {
                                key: "Content-Type",
                                value: "application/json",
                                type: "text"
                            }
                        ],
                        url: {
                            raw: url,
                            protocol: url.split('://')[0],
                            host: url.split('://')[1].split('/'),
                            path: route.uri.split('/'),
                            variable: urlVariables
                        },
                        description: route.docblock || ''
                    }
                };

                // Add body for POST, PUT, PATCH
                if (['POST', 'PUT', 'PATCH'].includes(method) && route.body_fields && route.body_fields.length > 0) {
                    const bodyExample = {};
                    route.body_fields.forEach(field => {
                        if (field.type === 'string') {
                            bodyExample[field.name] = `<${field.name}>`;
                        } else if (field.type === 'integer' || field.type === 'number') {
                            bodyExample[field.name] = 0;
                        } else if (field.type === 'boolean') {
                            bodyExample[field.name] = true;
                        } else {
                            bodyExample[field.name] = `<${field.name}>`;
                        }
                    });

                    item.request.body = {
                        mode: "raw",
                        raw: JSON.stringify(bodyExample, null, 2),
                        options: {
                            raw: {
                                language: "json"
                            }
                        }
                    };
                }

                // Add query parameters
                if (route.query_params && route.query_params.length > 0) {
                    item.request.url.query = route.query_params.map(param => ({
                        key: param.name,
                        value: param.default || `<${param.name}>`,
                        description: param.description || '',
                        disabled: !param.required
                    }));
                }

                collection.item.push(item);
            });

            // Download as JSON file
            const blob = new Blob([JSON.stringify(collection, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${controller.name.replace(/[^a-z0-9]/gi, '_')}_postman_collection.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>

</html>
