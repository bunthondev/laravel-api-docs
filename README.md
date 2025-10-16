# Laravel API Docs

[![Latest Version on Packagist](https://img.shields.io/packagist/v/puchan/laravel-api-docs.svg?style=flat-square)](https://packagist.org/packages/puchan/laravel-api-docs)
[![Total Downloads](https://img.shields.io/packagist/dt/puchan/laravel-api-docs.svg?style=flat-square)](https://packagist.org/packages/puchan/laravel-api-docs)
[![License](https://img.shields.io/packagist/l/puchan/laravel-api-docs.svg?style=flat-square)](https://packagist.org/packages/puchan/laravel-api-docs)

Automatic API documentation generator for Laravel applications. Generate beautiful, interactive API documentation by simply installing the package - **zero configuration required**!

## ✨ Features

- ✅ **Zero Configuration** - Works immediately after installation
- ✅ **Auto Route Scanning** - Automatically discovers all your API routes
- ✅ **Database Schema Integration** - Shows available fields from database tables
- ✅ **Smart Parameter Detection** - Auto-detects query params from FormRequests, method signatures, and pagination
- ✅ **Laravel Resource Support** - Uses your Resource classes for accurate response examples
- ✅ **Multiple Formats** - HTML, JSON, and OpenAPI/Swagger formats
- ✅ **Beautiful UI** - Modern, responsive documentation interface with collapsible sections
- ✅ **Pagination Detection** - Automatically identifies paginated endpoints
- ✅ **Highly Configurable** - Customize everything via config file and environment variables

## 📋 Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- PostgreSQL (for database schema reading)

## 📦 Installation

Install the package via Composer:

```bash
composer require puchan/laravel-api-docs
```

The package will be auto-discovered by Laravel. **No additional setup required!**

## 🚀 Quick Start

After installation, your API documentation is immediately available at:

```
http://your-app.test/api-docs
```

That's it! The package automatically:
- Scans all your API routes
- Reads database schemas
- Detects query parameters
- Generates response examples
- Creates beautiful documentation

## 📖 Available Endpoints

### 1. HTML Documentation (Interactive UI)
```
GET /api-docs
```
Beautiful, responsive web interface with:
- Collapsible route sections
- Color-coded HTTP methods
- Parameter tables
- Database field information
- Syntax-highlighted JSON examples

### 2. JSON Format (Programmatic Access)
```
GET /api-docs/json
```
Complete API documentation in JSON format for:
- Custom documentation tools
- CI/CD integration
- API testing tools

### 3. OpenAPI/Swagger Format
```
GET /api-docs/swagger
```
OpenAPI 3.0 compliant format for:
- Swagger UI
- Postman import
- API clients generation

## ⚙️ Configuration

### Publishing Config (Optional)

While the package works without configuration, you can customize it:

```bash
php artisan vendor:publish --tag=api-docs-config
```

This creates `config/api-docs.php`:

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

    // Route prefix for documentation
    'route_prefix' => env('API_DOCS_ROUTE_PREFIX', 'api-docs'),

    // Middleware to apply to documentation routes
    'middleware' => ['web'],

    // Route Filters
    'route_filters' => [
        // Only include routes starting with these prefixes
        'include_prefixes' => ['api/'],

        // Exclude routes matching these patterns
        'exclude_patterns' => [
            'sanctum/*',
            'telescope/*',
            'horizon/*',
        ],
    ],

    // Resource Detection
    'resources' => [
        // Automatically detect and use Laravel Resources
        'auto_detect' => true,

        // Namespace where resources are located
        'namespace' => 'App\\Http\\Resources',
    ],

    // Database Schema
    'database' => [
        // Schema name for PostgreSQL
        'schema' => 'public',
    ],
];
```

### Environment Variables

Configure via `.env` file:

```env
API_DOCS_ENABLED=true
API_DOCS_TITLE="My Awesome API"
API_DOCS_VERSION="2.0.0"
API_DOCS_BASE_URL="https://api.example.com"
API_DOCS_ROUTE_PREFIX="docs"
```

### Publishing Views (Optional)

Customize the HTML documentation:

```bash
php artisan vendor:publish --tag=api-docs-views
```

Views will be published to `resources/views/vendor/api-docs/`.

## 📚 How It Works

### 1. Route Detection

The package automatically scans all registered routes:

```php
// Your routes/api.php
Route::get('/api/products', [ProductController::class, 'index']);
Route::post('/api/products', [ProductController::class, 'store']);
Route::get('/api/products/{id}', [ProductController::class, 'show']);
```

**Automatically detected:**
- HTTP methods (GET, POST, PUT, PATCH, DELETE)
- Route parameters (`{id}`, `{slug}`, etc.)
- Middleware applied to routes
- PHPDoc comments from controller methods

### 2. Parameter Detection

**From FormRequests:**
```php
class StoreProductRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'category_id' => 'required|exists:categories,id',
        ];
    }
}
```

**From Method Signatures:**
```php
public function show(Request $request, int $id)
{
    // $id automatically detected as path parameter
}
```

**Pagination Auto-Detection:**
```php
public function index()
{
    return Product::paginate(20);
    // Automatically adds 'page' and 'per_page' parameters
}
```

### 3. Database Schema

Automatically reads table schemas:

```php
class ProductController extends Controller
{
    public function index()
    {
        return Product::all();
        // Automatically shows 'products' table schema
    }
}
```

**Displays:**
- Column names and types
- Nullable fields
- Default values
- Maximum lengths
- Indexes and foreign keys

### 4. Response Examples

Uses your Laravel Resources for accurate examples:

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
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

**Generated Response:**
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
      "category": "string value",
      "created_at": "2025-01-15T12:00:00Z",
      "updated_at": "2025-01-15T12:00:00Z"
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

## 💡 Usage Examples

### Adding DocBlocks for Better Documentation

```php
/**
 * Get all products with filtering and pagination
 *
 * Returns a paginated list of products. You can filter by category,
 * status, and search by name.
 */
public function index(ProductIndexRequest $request)
{
    return ProductResource::collection(
        Product::with('category')
            ->paginate($request->input('per_page', 20))
    );
}
```

### Filtering Routes

**Include only specific API versions:**
```php
// config/api-docs.php
'route_filters' => [
    'include_prefixes' => ['api/v1/', 'api/v2/'],
],
```

**Exclude internal or admin routes:**
```php
'route_filters' => [
    'exclude_patterns' => [
        'api/internal/*',
        'api/admin/*',
        'telescope/*',
    ],
],
```

### Custom Route Prefix

Change the documentation URL:

```env
API_DOCS_ROUTE_PREFIX=documentation
```

Access documentation at: `http://your-app.test/documentation`

## 🔒 Security

### Protect Documentation in Production

**Using Middleware:**
```php
// config/api-docs.php
'middleware' => ['web', 'auth', 'admin'],
```

**Disable in Production:**
```env
# .env.production
API_DOCS_ENABLED=false
```

**Conditional Enabling:**
```php
// config/api-docs.php
'enabled' => env('API_DOCS_ENABLED', !app()->isProduction()),
```

### IP Whitelist Example

```php
// config/api-docs.php
'middleware' => ['web', 'ip.whitelist'],
```

## 🔄 Integration with Swagger UI

Use the OpenAPI endpoint with Swagger UI:

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

## 🛠️ Troubleshooting

### Documentation Not Appearing

1. **Clear Laravel cache:**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan cache:clear
   ```

2. **Verify routes are registered:**
   ```bash
   php artisan route:list | grep api-docs
   ```

3. **Check if package is discovered:**
   ```bash
   php artisan package:discover
   ```

### Database Schema Not Loading

**PostgreSQL Configuration:**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_SCHEMA=public
```

Update config:
```php
// config/api-docs.php
'database' => [
    'schema' => env('DB_SCHEMA', 'public'),
],
```

### Resources Not Detected

Ensure your Resource classes:

1. Extend `Illuminate\Http\Resources\Json\JsonResource`
2. Are in the configured namespace (default: `App\Http\Resources`)
3. Follow naming convention: `{Model}Resource`

Example:
```php
// app/Http/Resources/ProductResource.php
class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
```

### No Routes Showing

**Check route filters:**
```php
// config/api-docs.php
'route_filters' => [
    'include_prefixes' => ['api/'], // Make sure your routes match
],
```

**Verify routes exist:**
```bash
php artisan route:list --path=api
```

## 🎯 Best Practices

### 1. Use FormRequests for Validation

```php
class StoreProductRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ];
    }
}
```

Benefits:
- Automatic parameter documentation
- Type detection
- Validation rules displayed

### 2. Use Laravel Resources

```php
class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float) $this->price,
        ];
    }
}
```

Benefits:
- Accurate response examples
- Proper data transformation
- Consistent API responses

### 3. Add Meaningful DocBlocks

```php
/**
 * Update product details
 *
 * Updates an existing product with the provided data.
 * Requires authentication and product ownership.
 *
 * @param UpdateProductRequest $request
 * @param int $id Product ID
 * @return JsonResponse
 */
public function update(UpdateProductRequest $request, int $id)
{
    // ...
}
```

### 4. Use Resource Controllers

```php
Route::apiResource('products', ProductController::class);
```

Benefits:
- Standard RESTful routes
- Consistent naming
- Better documentation organization

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 🔗 Links

- **GitHub Repository**: https://github.com/bunthondev/laravel-api-docs
- **Packagist**: https://packagist.org/packages/puchan/laravel-api-docs
- **Issues**: https://github.com/bunthondev/laravel-api-docs/issues

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## 💖 Support

If you find this package helpful, please consider:

- ⭐ Starring the repository on GitHub
- 🐛 Reporting bugs and issues
- 💡 Suggesting new features
- 🔀 Contributing code improvements

## 🙏 Credits

- [Puchan](https://github.com/bunthondev)
- [All Contributors](../../contributors)

---

Built with ❤️ for the Laravel community
