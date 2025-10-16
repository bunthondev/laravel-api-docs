# Changelog

All notable changes to `laravel-api-docs` will be documented in this file.

## [1.0.0] - 2025-01-15

### Added
- Initial release
- Automatic API route scanning
- Database schema integration
- Smart parameter detection from FormRequests
- Method signature parameter detection
- Automatic pagination detection
- Laravel Resource support for response examples
- HTML documentation interface
- JSON format endpoint
- OpenAPI/Swagger format endpoint
- Configurable route filters
- Environment variable configuration
- Beautiful responsive UI with collapsible sections
- Color-coded HTTP method badges
- Syntax-highlighted JSON examples
- DocBlock comment extraction
- PostgreSQL database schema support
- Support for Laravel 10.x, 11.x, and 12.x
- Zero configuration installation
- Auto-discovery service provider
- Publishable config file
- Publishable views
- Comprehensive documentation

### Features
- **Route Detection**
  - Scans all registered Laravel routes
  - Filters by configurable prefixes
  - Excludes routes by pattern matching
  - Extracts HTTP methods
  - Detects route parameters
  - Shows middleware information

- **Parameter Detection**
  - FormRequest validation rules
  - Method signature parameters
  - Pagination parameters
  - Type inference
  - Required/optional detection

- **Database Integration**
  - Table schema reading
  - Column types and constraints
  - Nullable fields detection
  - Default values
  - Maximum lengths
  - Indexes and foreign keys

- **Response Examples**
  - Laravel Resource detection
  - Automatic field mapping
  - Smart value inference
  - Pagination meta data
  - Proper response structure

- **Multiple Formats**
  - Interactive HTML documentation
  - JSON format for programmatic access
  - OpenAPI 3.0/Swagger format

- **Customization**
  - Configurable title and version
  - Custom base URL
  - Route prefix customization
  - Middleware configuration
  - Enable/disable toggle
  - Route filtering options

## [Unreleased]

### Planned
- MySQL database support
- SQLite database support
- Authentication examples
- Rate limiting documentation
- API versioning support
- Custom response format examples
- Markdown documentation export
- Postman collection export
- Request examples with curl
- Multiple language support
- Dark mode theme
- Search functionality
- API testing interface

---

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
