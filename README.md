# Atom

A lightweight PHP framework that implements the MVC pattern.

Please read more about the [Request Lifecycle](https://www.linkedin.com/pulse/atom-simple-php-framework-implements-mvc-pattern-cuong-dinh-ngo).

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Directory Structure](#directory-structure)
- [Routing](#routing)
- [Middleware](#middleware)
- [Controllers](#controllers)
- [Request](#request)
- [Response](#response)
- [Database / Query Builder](#database--query-builder)
- [Models](#models)
- [Validation](#validation)
- [Authentication](#authentication)
- [Views & Templates](#views--templates)
- [Storage](#storage)
- [Signed URLs](#signed-urls)
- [File Handling](#file-handling)
- [Service Container](#service-container)
- [Helpers](#helpers)
- [Example Project](#example-project)
- [License](#license)

## Features

- MVC architecture with single entry point
- Fluent query builder with MySQL support
- Route-based middleware with priority ordering
- Dependency injection container
- Input validation with 15 built-in rules
- JWT-based and session-based authentication
- View rendering and template composition
- File storage abstraction
- Signed URLs with expiration
- CSV, Image, and Log file handling

## Installation

```
composer require cuongnd88/atom
```

**Requirements:** PHP >= 7.0

## Quick Start

Create your entry point `public/index.php`:

```php
require __DIR__ . '/../vendor/autoload.php';

use Atom\Http\Server;

try {
    $server = new Server(['env']);
    $server->handle();
} catch (Exception $e) {
    echo $e->getMessage();
}
```

The `Server` constructor accepts an array of config file names to load. The `env` file (e.g., `config/env.ini`) is loaded to set environment variables.

### Application Constants

Define these constants in your bootstrap to configure directory paths:

```php
define('CONFIG_PATH', __DIR__ . '/../config/');
define('ROUTE_PATH', __DIR__ . '/../routes/');
define('CONTROLLER_PATH', __DIR__ . '/../app/Controllers/');
define('MIDDLEWARE_PATH', __DIR__ . '/../app/Middlewares/');
define('VIEW_PATH', __DIR__ . '/../resources/views/');
define('STORAGE_PATH', __DIR__ . '/../storage/');
define('RESOURCES_PATH', __DIR__ . '/../resources/');
define('ASSETS_PATH', __DIR__ . '/assets/');
define('DOC_ROOT', __DIR__ . '/');
```

## Directory Structure

```
├── config/
│   ├── env.ini           # Environment variables
│   ├── app.php           # Application config
│   ├── middleware.php     # Middleware config
│   └── templates.php     # Template config
├── routes/
│   ├── web.php           # Web routes
│   └── api.php           # API routes
├── app/
│   ├── Controllers/
│   ├── Middlewares/
│   └── Models/
├── resources/
│   └── views/
├── storage/
└── public/
    └── index.php         # Entry point
```

## Routing

Routes are defined as PHP arrays in `routes/web.php` and `routes/api.php`.

```php
// routes/web.php
return [
    'users' => [
        'get' => 'UserController@index',
        'middleware' => ['auth'],
    ],
    'users/{id}' => [
        'get' => 'UserController@show',
        'post' => 'UserController@update',
    ],
    'login' => [
        'get' => 'AuthController@showLogin',
        'post' => 'AuthController@login',
    ],
];
```

Dynamic parameters use `{param}` syntax and are automatically extracted and injected into the `Request` object.

**Supported HTTP methods:** `GET`, `POST`, `PUT`, `PATCH`, `DELETE`

API routes are automatically detected when the URI contains `api` or the `Content-Type` header is `application/json`.

## Middleware

### Configuration

Define middleware in `config/middleware.php`:

```php
return [
    'routeMiddlewares' => [
        'auth' => 'AuthMiddleware',
        'admin' => 'AdminMiddleware',
    ],
    'priorityMiddlewares' => [
        'auth',
        'admin',
    ],
];
```

### Creating Middleware

Place middleware files in your `MIDDLEWARE_PATH` directory. Each middleware must implement a `handle()` method:

```php
class AuthMiddleware
{
    public function handle()
    {
        // Return false to stop request processing
        if (!isset($_SESSION['user'])) {
            return false;
        }
    }
}
```

### Attaching Middleware to Routes

```php
'dashboard' => [
    'get' => 'DashboardController@index',
    'middleware' => ['auth', 'admin'],
],
```

Middlewares are sorted by priority and executed in order before the controller action.

## Controllers

Controllers are loaded from the `CONTROLLER_PATH` directory. Method parameters are automatically resolved via the service container.

```php
use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Validation\Validator;

class UserController
{
    use Validator;

    public function index(Request $request)
    {
        $users = (new User)->get();
        view('users.index', ['users' => $users]);
    }

    public function show(Request $request)
    {
        $id = $request->id;
        $user = (new User)->find($id);
        view('users.show', ['user' => $user]);
    }

    public function store(Request $request)
    {
        static::execute($request->all(), [
            'email' => 'required|email',
            'name' => 'required|string',
        ]);

        if ($errors = static::errors()) {
            return Response::toJson(['errors' => $errors], 422);
        }

        $user = (new User)->create($request->all());
        return Response::toJson($user->toArray(), 201);
    }
}
```

## Request

The `Request` class collects parameters from URI, query string, POST data, file uploads, and JSON body automatically.

```php
public function update(Request $request)
{
    // Access parameters as properties
    $name = $request->name;

    // Or as array
    $email = $request['email'];

    // Get all parameters
    $all = $request->all();

    // Get HTTP headers
    $headers = $request->headers();
    $contentType = $request->headers('Content-Type');

    // Access HTTP method and URI
    $method = $request->method;  // GET, POST, PUT, PATCH, DELETE
    $uri = $request->uri;
}
```

## Response

```php
use Atom\Http\Response;

// JSON response
Response::toJson(['status' => 'ok']);
Response::toJson(['error' => 'Not Found'], 404);

// Redirect
Response::redirect('/dashboard');
Response::redirect('/users', ['message' => 'Created!']);

// Set HTTP response code
Response::responseCode(204);
```

## Database / Query Builder

Database credentials are loaded from environment variables: `DB_CONNECTION`, `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, `DB_PORT`.

### Basic Queries

```php
use Atom\Db\Database;

$db = new Database();

// Select all
$users = $db->table('users')->get();

// Select specific columns
$users = $db->table('users')->select(['name', 'email'])->get();

// First record
$user = $db->table('users')->where(['id', 1])->first();

// With limit and offset
$users = $db->table('users')->limit(10)->offset(20)->get();
```

### Where Conditions

```php
// Equality
$db->table('users')->where(['status', 'active'])->get();

// With operator
$db->table('users')->where(['age', '>', 18])->get();

// Multiple conditions (AND)
$db->table('users')->where([
    ['status', 'active'],
    ['age', '>', 18],
])->get();

// OR condition
$db->table('users')->where(['status', 'active'])->orWhere(['role', 'admin'])->get();

// WHERE IN
$db->table('users')->whereIn('id', [1, 2, 3])->get();

// WHERE NOT IN
$db->table('users')->whereNotIn('status', ['banned', 'inactive'])->get();

// WHERE BETWEEN
$db->table('users')->whereBetween('age', [18, 65])->get();

// WHERE NOT BETWEEN
$db->table('users')->whereNotBetween('score', [0, 50])->get();

// WHERE NULL / NOT NULL
$db->table('users')->whereNull('deleted_at')->get();
$db->table('users')->whereNotNull('email_verified_at')->get();
```

Use the `#` prefix on a key to reference a raw column name (no quoting):

```php
$db->table('users')->where(['#age', '>', 25])->get();
```

### Joins

```php
$db->table('users')
    ->innerJoin('orders', 'users.id', 'orders.user_id')
    ->get();

$db->table('users')
    ->leftJoin('profiles', 'users.id', 'profiles.user_id')
    ->get();

$db->table('users')
    ->rightJoin('departments', 'users.dept_id', 'departments.id')
    ->get();
```

### Grouping & Ordering

```php
$db->table('orders')
    ->select(['#user_id', '#COUNT(*) as total'])
    ->groupBy('user_id')
    ->having('#total', '>', 5)
    ->orderBy('total', 'DESC')
    ->get();
```

### Insert

```php
// Single insert (returns last insert ID)
$id = $db->table('users')->insert([
    'name' => 'John',
    'email' => 'john@example.com',
]);

// Bulk insert (requires setFillable)
$db->table('users')->setFillable(['name', 'email'])->insertMany([
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
]);

// Insert or update on duplicate key
$db->table('users')->insertDuplicate([
    'id' => 1,
    'name' => 'John Updated',
    'email' => 'john@example.com',
]);
```

### Update & Delete

```php
// Update
$db->table('users')->where(['id', 1])->update(['name' => 'John Doe']);

// Delete
$db->table('users')->where(['id', 1])->delete();

// Truncate
$db->table('logs')->truncate();
```

### Chunking

Process large result sets in chunks:

```php
$db->table('users')->chunk(100, function ($users, $page) {
    foreach ($users as $user) {
        // Process each user
    }
    // Return false to stop chunking
});
```

### Transactions

```php
$db = new Database();
$db->beginTransaction();
try {
    $db->table('accounts')->where(['id', 1])->update(['balance' => 500]);
    $db->table('accounts')->where(['id', 2])->update(['balance' => 1500]);
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
}
```

### Query Logging

```php
$db = new Database();
$db->enableQueryLog();

$db->table('users')->get();
$db->table('orders')->where(['status', 'pending'])->get();

$queries = $db->getQueryLog(); // Array of executed SQL strings
```

### Mass Assignment Protection

```php
$db->table('users')->setFillable(['name', 'email'])->insert($request->all());
```

Only `name` and `email` columns will be inserted; any other keys in the input are ignored.

## Models

Create models by extending the abstract `Model` class:

```php
use Atom\Models\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
}
```

### CRUD Operations

```php
$user = new User;

// Find by ID (returns model instance)
$user = $user->find(1);

// Find multiple by IDs (returns array)
$users = $user->find([1, 2, 3]);

// Create (inserts and returns model with ID)
$user = $user->create([
    'name' => 'John',
    'email' => 'john@example.com',
]);

// Save (insert or update on duplicate key)
$user->save();

// Destroy by ID
$user->destroy(1);

// Destroy multiple
$user->destroy([1, 2, 3]);

// Convert to array
$data = $user->toArray();
```

Models inherit all query builder methods:

```php
$activeUsers = (new User)->where(['status', 'active'])->orderBy('name', 'ASC')->get();
```

## Validation

Use the `Validator` trait in your controllers:

```php
use Atom\Validation\Validator;

class UserController
{
    use Validator;

    public function store(Request $request)
    {
        static::execute($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
            'age' => 'required|integer|between:18,100',
            'role' => 'in_array:admin,editor,viewer',
            'avatar' => 'image',
        ]);

        $errors = static::errors();
        if (!empty($errors)) {
            return Response::toJson(['errors' => $errors], 422);
        }

        // Validation passed
    }
}
```

### Custom Error Messages

```php
static::execute($input, $rules, [
    'required' => 'The %s field is required.',
    'email' => 'The %s must be a valid email.',
    'between' => 'The %s must be between %s and %s.',
    'integer' => 'The %s must be an integer.',
    'string' => 'The %s must be a string.',
    'in_array' => 'The %s has an invalid value.',
    'max' => 'The %s must not exceed %s.',
    'min' => 'The %s must be at least %s.',
    'array' => 'The %s must be an array.',
    'date' => 'The %s must be a valid date.',
    'image' => 'The %s must be an image.',
    'after' => 'The %s must be after %s.',
    'before' => 'The %s must be before %s.',
    'required_if' => 'The %s is required.',
    'date_format' => 'The %s must match the format %s.',
]);
```

### Available Rules

| Rule | Description | Example |
|------|-------------|---------|
| `required` | Must not be empty | `'required'` |
| `string` | Must be a string | `'string'` |
| `integer` | Must be an integer | `'integer'` |
| `email` | Must be a valid email | `'email'` |
| `array` | Must be an array | `'array'` |
| `date` | Must be a valid date | `'date'` |
| `date_format` | Must match date format | `'date_format:Y-m-d'` |
| `image` | Must be an image (jpeg, png, bmp, gif, svg) | `'image'` |
| `between` | Must be between min and max | `'between:1,100'` |
| `min` | Must be >= value | `'min:18'` |
| `max` | Must be <= value | `'max:200'` |
| `in_array` | Must be one of the listed values | `'in_array:a,b,c'` |
| `after` | Date must be after given date | `'after:2024-01-01'` |
| `before` | Date must be before given date | `'before:2025-12-31'` |
| `required_if` | Required if another field is present | `'required_if:role,admin'` |

Rules are combined with `|`: `'required|email|string'`

## Authentication

Use the `Auth` trait for session-based and JWT authentication.

### Configuration

In `config/app.php`:

```php
return [
    'auth' => [
        'guard' => 'email,password',   // Guard fields (comma-separated)
        'table' => 'users',            // Users table
        'response' => [
            'success' => '/dashboard', // Redirect on success (empty for JSON token)
            'fail' => '/login',        // Redirect on failure
        ],
    ],
];
```

Set `APP_KEY` and `SESSION_LIFETIME` (in minutes) in your `env.ini`.

### Usage

```php
use Atom\Guard\Auth;

class AuthController
{
    use Auth;

    public function login(Request $request)
    {
        // Authenticates and redirects or returns JWT token
        static::login([
            'email' => $request->email,
            'password' => $request->password,
        ]);
    }

    public function dashboard()
    {
        // Verify authentication (redirects to fail URL if invalid)
        static::check();

        // Get current user
        $user = static::user();

        view('dashboard', ['user' => $user]);
    }
}
```

### API Authentication

When `app.auth.response.success` is empty, `login()` returns a JSON response with the JWT token:

```json
{"Token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."}
```

The token can be sent in the `Authorization` header for subsequent requests.

## Views & Templates

### Simple Views

Views are PHP files located in `VIEW_PATH`. Use dot notation to reference subdirectories:

```php
// Renders resources/views/users/index.php
view('users.index', ['users' => $users]);
```

Inside view files, the data array is extracted into variables:

```php
<!-- resources/views/users/index.php -->
<?php foreach ($users as $user): ?>
    <p><?= $user['name'] ?></p>
<?php endforeach; ?>
```

### Templates

Templates allow composing multiple view files into a layout. Define templates in `config/templates.php`:

```php
return [
    'admin' => [
        'template' => [
            'layouts.header',
            null,               // Placeholder for dynamic content
            'layouts.footer',
        ],
    ],
];
```

Use the `template()` helper to render:

```php
// Fills the null placeholder with 'users.list' and renders all parts
template('admin', 'users.list', ['users' => $users]);
```

## Storage

### Local Storage

```php
use Atom\Storage\StorageFactory;

$storage = (new StorageFactory('local'))->init();

// Upload file
$storage->upload('avatars', $request->files['photo']);

// Get full path
$path = $storage->getFullUrl('avatars');

// Delete file
$storage->remove('avatars/photo.jpg');
```

Configure storage drivers in `config/app.php`:

```php
'storage' => [
    'local' => [
        'path' => '/path/to/storage/',
    ],
],
```

## Signed URLs

Generate URLs with HMAC signatures and optional expiration:

```php
use Atom\Http\Url;

$url = new Url();

// Permanent signed URL
$signedUrl = $url->signedUrl('/verify-email', ['user' => 42]);

// Temporary signed URL (expires in 3600 seconds)
$tempUrl = $url->temporarySignedUrl('/reset-password', 3600, ['token' => 'abc']);

// Verify signature
$valid = $url->identifySignature(); // Returns true/false
```

## File Handling

### CSV

```php
use Atom\File\CSV;

$csv = new CSV();

// Read CSV to array
$data = $csv->toArray($file, ['name', 'email', 'age']);

// Save and download CSV
$csv->save('export.csv', $data, true); // true = include header row
```

### Image

```php
use Atom\File\Image;

$image = new Image();

// Upload image
$image->upload('avatars', $request->files['photo']);

// Upload and resize
$image->uploadResize('thumbnails', $request->files['photo'], [200, 200]);

// Get EXIF metadata
$metadata = $image->getMetadata($request->files['photo']);

// Delete image
$image->delete('avatars/photo.jpg');
```

### Logging

```php
use Atom\File\Log;

$log = new Log();

$log->error('Something went wrong');
$log->info('User logged in');
$log->debug('Variable value: ' . $value);
```

## Service Container

The container automatically resolves type-hinted dependencies in controller methods:

```php
use Atom\Container\Container;

$container = new Container();

// Register a binding
$container->set('App\Services\MailService');

// Resolve with automatic dependency injection
$service = $container->get('App\Services\MailService');
```

In controller methods, dependencies are auto-injected:

```php
class OrderController
{
    public function index(Request $request, PaymentService $payment)
    {
        // Both $request and $payment are automatically resolved
    }
}
```

## Helpers

| Function | Description |
|----------|-------------|
| `config('app.key')` | Load config value using dot notation |
| `route('web.users')` | Load route definition |
| `env('DB_HOST')` | Get environment variable |
| `view('users.index', $data)` | Render a view file |
| `template('layout', 'page', $data)` | Render a template |
| `url('/path')` | Get full URL |
| `back()` | Redirect to previous page |
| `now()` | Current timestamp (`Y-m-d H:i:s`) |
| `today()` | Current date (`Y-m-d`) |
| `json($data)` | Convert to JSON |
| `isApi()` | Check if request is API |
| `storage_path('uploads')` | Get storage directory path |
| `resources_path('views')` | Get resources directory path |
| `public_path('images')` | Get public directory path |
| `assets('/css/app.css')` | Get asset URL |
| `getHeaders()` | Get all HTTP request headers |
| `imageLocation($file)` | Extract GPS coordinates from image EXIF |

## Example Project

For a full implementation example, see the [EzyCrazy](https://github.com/cuongnd88/ezycrazy) project built with Atom.

## License

MIT
