# Laravel API Docs

Automatic API documentation generator for Laravel applications. This package automatically generates beautiful, interactive API documentation by scanning your routes, database schema, and Laravel Resources.

## Features

- ✅ **Auto-scans API routes** - Automatically discovers all your API routes
- ✅ **Database schema integration** - Shows available fields from your database tables
- ✅ **Query parameter detection** - Automatically detects query params from FormRequests and method signatures
- ✅ **Laravel Resource support** - Uses your Resource classes for accurate response examples
- ✅ **Pagination detection** - Automatically detects paginated endpoints
- ✅ **Multiple formats** - Supports HTML, JSON, and OpenAPI/Swagger formats
- ✅ **Beautiful UI** - Modern, responsive documentation interface
- ✅ **Zero configuration** - Works out of the box with sensible defaults

## Installation

### 1. Install via Composer

```bash
composer require puchan/laravel-api-docs
```

### 2. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=api-docs-config
```

### 3. Publish Views (Optional)

```bash
php artisan vendor:publish --tag=api-docs-views
```

## Usage

Once installed, the package automatically registers routes for viewing your documentation:

### View Documentation

- **HTML Interface**: `http://your-app.test/api-docs`
- **JSON Format**: `http://your-app.test/api-docs/json`
- **OpenAPI/Swagger**: `http://your-app.test/api-docs/swagger`

## Configuration

The package works with zero configuration, but you can customize it by publishing the config file:

```php
<?php

return [
    // API Documentation Title
    'title' => env('API_DOCS_TITLE', config('app.name') . ' API Documentation'),

    // API Version
    'version' => env('API_DOCS_VERSION', '1.0.0'),

    // API Base URL
    'base_url' => env('API_DOCS_BASE_URL', config('app.url') . '/api'),

    // Enable/disable documentation routes
    'enabled' => env('API_DOCS_ENABLED', true),

    // Route prefix
    'route_prefix' => env('API_DOCS_ROUTE_PREFIX', 'api-docs'),

    // Middleware
    'middleware' => ['web'],

    // Route Filters
    'route_filters' => [
        'include_prefixes' => ['api/'],
        'exclude_patterns' => [
            'sanctum/*',
            'telescope/*',
            'horizon/*',
        ],
    ],

    // Resource Detection
    'resources' => [
        'auto_detect' => true,
        'namespace' => 'App\\Http\\Resources',
    ],

    // Database Schema
    'database' => [
        'schema' => 'public', // PostgreSQL schema
    ],
];
```

## Environment Variables

You can configure the package using environment variables:

```env
API_DOCS_ENABLED=true
API_DOCS_TITLE="My API Documentation"
API_DOCS_VERSION="1.0.0"
API_DOCS_BASE_URL="https://api.example.com"
API_DOCS_ROUTE_PREFIX="api-docs"
```

## How It Works

### 1. Route Scanning

The package scans all your registered routes and filters API routes based on configured prefixes:

```php
// Automatically detected
Route::get('/api/products', [ProductController::class, 'index']);
Route::post('/api/products', [ProductController::class, 'store']);
```

### 2. Database Schema Reading

For each controller's model, the package reads the database schema to show available fields:

```php
class ProductController extends Controller
{
    public function index() {
        // Package automatically detects the 'products' table
        // and shows all columns with their types
    }
}
```

### 3. Query Parameter Detection

The package automatically detects query parameters from multiple sources:

#### From FormRequests

```php
class StoreProductRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ];
    }
}
```

#### From Method Signatures

```php
public function show(Request $request, int $id)
{
    // $id is automatically detected as a path parameter
}
```

#### From Pagination

```php
public function index(Request $request)
{
    $products = Product::paginate(20);
    // Automatically adds 'page' and 'per_page' query params
}
```

### 4. Laravel Resource Integration

The package uses your Laravel Resources to generate accurate response examples:

```php
class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
```

Response example automatically generated:

```json
{
  "success": true,
  "status": 200,
  "message": "Success",
  "data": [
    {
      "id": 1,
      "name": "string value",
      "price": 99.99,
      "status": "active",
      "created_at": "2025-01-15T12:00:00Z"
    }
  ],
  "meta": {
    "size": 20,
    "page": 1,
    "total_pages": 1,
    "total_items": 1
  }
}
```

## Security

For production environments, you should protect the documentation routes:

```php
'middleware' => ['web', 'auth', 'admin'],
```

Or disable it completely:

```env
API_DOCS_ENABLED=false
```

## Customization

### Custom View

Publish the views and customize the HTML:

```bash
php artisan vendor:publish --tag=api-docs-views
```

Views will be published to `resources/views/vendor/api-docs/`.

### Custom Routes

If you want to customize the routes, disable the package routes and register your own:

```php
// config/api-docs.php
'enabled' => false,

// routes/web.php
use Puchan\LaravelApiDocs\Http\Controllers\ApiDocController;

Route::get('/custom-docs', [ApiDocController::class, 'index']);
```

## Integration with Swagger UI

You can use the OpenAPI/Swagger endpoint with Swagger UI:

```html
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            url: '/api-docs/swagger',
            dom_id: '#swagger-ui',
        });
    </script>
</body>
</html>
```

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x
- PostgreSQL (for database schema reading)

## License

MIT License

## Support

For issues, questions, or contributions, please visit the GitHub repository.

## Credits

Created by Puchan
