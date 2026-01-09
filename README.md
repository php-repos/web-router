# Web Router Package

A lightweight, flexible routing package designed to map URLs to handler files or closures, supporting dynamic and optional parameters, HTTP method restrictions, and a signal-based event system for web applications.

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [File-based Routing](#file-based-routing)
- [Manual Route Definition](#manual-route-definition)
- [Handling Parameters](#handling-parameters)
- [HTTP Method Restrictions](#http-method-restrictions)
- [Signal System](#signal-system)
- [Error Handling](#error-handling)
- [Advanced Features](#advanced-features)
- [Use Cases](#use-cases)
- [For Developers](#for-developers)

## Installation

Install the package using `phpkg`:

```bash
phpkg add web-router
```

After installation, build your project:

```bash
phpkg build
```

## Requirements

- PHP >= 8.3

## Quick Start

Create a simple web application in minutes:

### Step 1: Create Routes Directory

```bash
mkdir Routes
```

### Step 2: Create Home Route

**File:** `Routes/index.php`
```php
<?php

/**
 * Welcome page handler.
 */
return function () {
    return "Welcome to my web application!";
};
```

### Step 3: Create Entry Point

Create a simple entry point that uses the package's `http.php`:

**File:** `index.php`
```php
<?php

$handle = require 
__DIR__ . '/Packages/php-repos/web-router/http.php';
$handle(__DIR__ . '/Routes');
```

Then run with:
```bash
php -S localhost:8000 index.php
```

Visit `http://localhost:8000` to see your welcome message!

That's it! The `http.php` closure automatically:
- Discovers routes from your `Routes/` directory
- Handles all request routing
- Returns appropriate HTTP status codes (404, 405, 422, 500)

### Step 4: Custom Error Handling (Optional)

For advanced customization, provide custom handlers:

**File:** `index.php`
```php
<?php

use PhpRepos\WebRouter\Business\Outcome;

$handle = require __DIR__ . '/Packages/php-repos/web-router/http.php';

$handle(__DIR__ . '/Routes', [
    'on_success' => function (Outcome $outcome) {
        echo is_array($outcome->data['response'])
            ? json_encode($outcome->data['response'])
            : $outcome->data['response'];
    },
    'on_not_found' => function (Outcome $outcome) {
        http_response_code(404);
        include __DIR__ . '/views/404.php';
    },
    'on_method_not_allowed' => function (Outcome $outcome) {
        http_response_code(405);
        header('Allow: ' . implode(', ', $outcome->data['allowed_methods']));
        echo "Method {$outcome->data['method']} not allowed";
    },
    'on_validation_error' => function (Outcome $outcome) {
        http_response_code(422);
        echo json_encode(['error' => $outcome->message]);
    },
    'on_internal_error' => function (Outcome $outcome) {
        http_response_code(500);
        error_log($outcome->message);
        include __DIR__ . '/views/500.php';
    },
]);
```

## File-based Routing

The router automatically discovers routes from your directory structure. Each PHP file represents a route, and the file path determines the URL pattern.

### Basic Structure

```
Routes/
├── index.php              # Handles "/"
├── users/
│   ├── index.php          # Handles "/users"
│   ├── create.php         # Handles "/users/create"
│   ├── {id}/
│   │   ├── show.php       # Handles "/users/{id}/show"
│   │   └── delete.php     # Handles "/users/{id}"
│   └── {email}/
│       └── [{id}].php     # Handles "/users/{email}/[{id}]"
├── api/
│   └── v1/
│       ├── posts/
│       │   ├── get.php    # Handles GET "/api/v1/posts"
│       │   └── {id}.php  # Handles "/api/v1/posts/{id}"
```

### Dynamic Parameters

Use curly braces in filenames to create dynamic routes:

**File:** `Routes/users/{id}/show.php`
```php
<?php

return function (int $id) {
    return "Showing user profile for ID: $id";
};
```

### Optional Parameters

Use `[{parameter}]` for optional URL segments:

**File:** `Routes/posts/{category}/[{id}].php`
```php
<?php

return function (string $category, ?int $id = null) {
    if ($id) {
        return "Showing post $id from category $category";
    }
    return "All posts in category $category";
};
```

### Method-Specific Files

Create files with HTTP method names to handle specific methods:

**File:** `Routes/users/{id}/delete.php`
```php
<?php

use PhpRepos\WebRouter\Business\Attributes\Method;

return
#[Method('DELETE'), Method('POST')]
function (int $id) {
    return "User $id deleted successfully";
};
```

## Manual Route Definition

For more control, define routes programmatically as arrays:

```php
<?php

use PhpRepos\WebRouter\Business\Attributes\Method;

// Define routes array
$routes = [
    // Simple route
    [
        'pattern' => '/about',
        'handler' => function () {
            return 'About us page';
        }
    ],
    
    // Route with parameters
    [
        'pattern' => '/products/{id}',
        'handler' => function (int $id) {
            return "Product $id details";
        }
    ],
    
    // Route with method restrictions
    [
        'pattern' => '/api/users',
        'handler' => 
            #[Method('POST')]
            function (string $name, string $email) {
                return ['status' => 'created', 'user' => compact('name', 'email')];
            }
    ]
];

// Use routes with respond function
$outcome = respond(
    routes: $routes,
    url: $_SERVER['REQUEST_URI'],
    method: $_SERVER['REQUEST_METHOD'],
    variables: array_replace($_GET, $_POST, $_FILES)
);
```

## Handling Parameters

### Dynamic Parameters

Define parameters in URLs and specify types in handlers:

```php
// Routes/posts/{id}.php
return function (int $id) {
    return "Showing post $id";
};
```

### Optional Parameters

Use optional parameters with default values:

```php
// Routes/users/{email}/[{id}].php
return function (string $email, ?int $id = null) {
    return $id ? "User $email with ID $id" : "User $email";
};
```

### Type Validation

Parameters are automatically validated against their types:

```php
// Routes/posts/{id}/{force}.php
return
#[Method('DELETE')]
function (int $id, bool $force) {
    return "Delete post ID $id with force: " . ($force ? 'true' : 'false');
};
```

### Accessing Request Variables

Access `$_GET`, `$_POST`, and `$_FILES` through handler parameters:

```php
// Routes/contact.php
return
#[Method('POST')]
function (string $name, string $message, array $files = []) {
    return "Received message from $name with " . count($files) . " file(s)";
};
```

## HTTP Method Restrictions

Use the `#[Method]` attribute to limit handlers to specific HTTP methods:

```php
<?php

use PhpRepos\WebRouter\Business\Attributes\Method;

return
#[Method('GET')]
function () {
    return 'GET request only';
};
```

### Multiple Methods

The attribute is repeatable, allowing multiple methods:

```php
<?php

use PhpRepos\WebRouter\Business\Attributes\Method;

return
#[Method('PUT'), Method('PATCH')]
function (int $id) {
    return "Updating user $id";
};
```

### Method-Specific Files

Alternative approach using filename convention:

```
Routes/
├── users/{id}/
│   ├── get.php          # GET /users/{id}
│   ├── post.php         # POST /users/{id}
│   ├── put.php          # PUT /users/{id}
│   └── delete.php      # DELETE /users/{id}
```

## Signal System

Monitor the request lifecycle by subscribing to signals:

```php
<?php

use PhpRepos\Observer\API\Bus\subscribe;
use PhpRepos\WebRouter\Business\Signals\RequestReceived;
use PhpRepos\WebRouter\Business\Signals\RouteDetected;
use PhpRepos\WebRouter\Business\Signals\HandlerExecuted;

// Log all requests
subscribe(function ($signal) {
    if ($signal instanceof RequestReceived) {
        error_log("Request: {$signal->details['url']} [{$signal->details['method']}]");
    }
});

// Log successful routing
subscribe(function ($signal) {
    if ($signal instanceof RouteDetected) {
        error_log("Route matched: {$signal->details['route']}");
    }
});

// Log response times
subscribe(function ($signal) {
    if ($signal instanceof HandlerExecuted) {
        error_log("Response generated for: {$signal->details['route']}");
    }
});
```

### Available Signals

- **`RequestReceived`**: Emitted on request receipt
- **`DetectingRoutes`**: Emitted when starting route discovery
- **`RoutesDetectionCompleted`**: Emitted when routes are successfully loaded
- **`RoutesDetectionFailed`**: Emitted when route discovery fails
- **`FindingRoute`**: Emitted when starting to find matching route
- **`RouteDetected`**: Emitted when a route is matched
- **`RouteFindingFailed`**: Emitted when no route matches
- **`ExecutingHandler`**: Emitted before handler execution
- **`HandlerExecuted`**: Emitted after handler execution
- **`DisallowedMethodDetected`**: Emitted when HTTP method is not allowed

## Error Handling

The router uses the Outcome pattern for consistent error handling. All Business layer functions return an `Outcome` object with `success`, `message`, and `data` properties.

### Outcome Data Schema

The `respond` function always returns an Outcome with a consistent data schema:

```php
$outcome->data = [
    'route_not_found' => bool,      // true if no route matched the URL
    'method_not_allowed' => bool,   // true if HTTP method not allowed
    'validation_error' => bool,     // true if parameter validation failed
    'internal_error' => bool,       // true if an unexpected error occurred
    'response' => mixed,            // the handler response (on success)
    'url' => string,                // the requested URL (on error)
    'method' => string,             // the HTTP method (on some errors)
    'allowed_methods' => array,     // allowed methods (on method_not_allowed)
    'exception' => Throwable,       // the exception (on internal_error)
];
```

### Using http.php Handlers

The recommended approach is to use the `http.php` closure with custom handlers:

```php
<?php

use PhpRepos\WebRouter\Business\Outcome;

$handle = require __DIR__ . '/Packages/php-repos/web-router/http.php';

$handle(__DIR__ . '/Routes', [
    'on_success' => function (Outcome $outcome) {
        // Handle successful response
    },
    'on_not_found' => function (Outcome $outcome) {
        // Handle 404 - $outcome->data['url'] available
    },
    'on_method_not_allowed' => function (Outcome $outcome) {
        // Handle 405 - $outcome->data['allowed_methods'] available
    },
    'on_validation_error' => function (Outcome $outcome) {
        // Handle 422 - $outcome->message contains details
    },
    'on_internal_error' => function (Outcome $outcome) {
        // Handle 500 - $outcome->data['exception'] available
    },
]);

### Manual Error Handling

For direct use of the Router functions:

```php
<?php

use PhpRepos\WebRouter\Business\Router;

$outcome = Router\respond(
    routes: $routes,
    url: $_SERVER['REQUEST_URI'],
    method: $_SERVER['REQUEST_METHOD'],
    variables: array_replace($_GET, $_POST, $_FILES)
);

if ($outcome->data['route_not_found']) {
    http_response_code(404);
    echo json_encode(['error' => 'Page not found']);
} elseif ($outcome->data['method_not_allowed']) {
    http_response_code(405);
    header('Allow: ' . implode(', ', $outcome->data['allowed_methods']));
    echo json_encode(['error' => 'Method not allowed']);
} elseif ($outcome->data['validation_error']) {
    http_response_code(422);
    echo json_encode(['error' => $outcome->message]);
} elseif ($outcome->data['internal_error']) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} else {
    echo is_array($outcome->data['response'])
        ? json_encode($outcome->data['response'])
        : $outcome->data['response'];
}
```

## Advanced Features

### Encoded URL Handling

The router automatically handles encoded characters:

```php
// URL: /search/hello%20world
// Routes/search/{query}.php
return function (string $query) {
    return "Searching for: $query"; // "Searching for: hello world"
};
```

### Case Insensitive Matching

Routes match regardless of URL case:

```
/USERS/123  →  Routes/users/{id}/show.php
/Products/456  →  Routes/products/{id}.php
```

### Query String Ignorance

Query strings are ignored for routing:

```
/users/123?format=json&sort=name  →  Routes/users/{id}.php
```

### Flexible Responses

Handlers can return any data type:

```php
// Return string
return "Hello World";

// Return array (auto-converts to JSON)
return ['status' => 'success', 'data' => $result];

// Return object
return new JsonResponse(['message' => 'OK']);
```

## Use Cases

### RESTful APIs

Create clean REST APIs with method-specific routing:

```
Routes/api/v1/
├── posts/
│   ├── index.php           # GET /api/v1/posts
│   ├── create.php          # POST /api/v1/posts
│   ├── {id}/
│   │   ├── show.php       # GET /api/v1/posts/{id}
│   │   ├── update.php     # PUT /api/v1/posts/{id}
│   │   └── delete.php     # DELETE /api/v1/posts/{id}
```

### Web Applications

Build traditional web applications with page routing:

```
Routes/
├── index.php              # Home page
├── about.php              # About page
├── contact.php            # Contact form
├── users/
│   ├── index.php          # User list
│   ├── create.php         # Registration
│   ├── {id}/
│   │   ├── profile.php    # User profile
│   │   └── edit.php      # Edit profile
```

### Microservices

Create lightweight, focused services:

```php
// Single file microservice
$routes = [
    [
        'pattern' => '/health',
        'handler' => function () {
            return ['status' => 'healthy'];
        }
    ],
    [
        'pattern' => '/process',
        'handler' => 
            #[Method('POST')]
            function (array $data) {
                // Process data
                return ['processed' => true, 'count' => count($data)];
            }
    ]
];

respond($routes, $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
```

### Event-driven Systems

Use signals for logging, monitoring, and analytics:

```php
// Request logging
subscribe(function (RequestReceived $signal) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'url' => $signal->details['url'],
        'method' => $signal->details['method'],
        'ip' => $_SERVER['REMOTE_ADDR']
    ];
    file_put_contents('requests.log', json_encode($log) . "\n", FILE_APPEND);
});

// Performance monitoring
$start_time = microtime(true);
subscribe(function (HandlerExecuted $signal) use (&$start_time) {
    $duration = (microtime(true) - $start_time) * 1000;
    if ($duration > 1000) { // Log slow requests
        error_log("Slow request: {$signal->details['route']} took {$duration}ms");
    }
});
```

## For Developers

This section is for developers who want to understand internal architecture and create custom integrations.

### Architecture Overview

The Web Router Package follows **Natural Architecture** with three distinct layers:

#### 1. Business Layer (`Source/Business/`)

Defines **what** the system does through pure specifications. Functions return `Outcome` objects and delegate implementation to Solution layer.

**Key Components:**
- `Router\respond()` - Main request handling function
- `Router\find()` - Find matching route for URL
- `Finder\path()` - Discover routes from filesystem
- `Outcome.php` - Return value wrapper
- `Attributes/` - HTTP method restriction attributes
- `Signals/` - Event objects for observer pattern

#### 2. Solution Layer (`Source/Solution/`)

Contains **how** things are implemented - actual logic for routing, parsing, and validation.

**Key Components:**
- `Routes\sort()` - Sort routes by specificity
- `Routes\match_pattern()` - Match URL against route pattern
- `Routes\validate_method()` - Validate HTTP method
- `Routes\validate_parameters()` - Validate and prepare parameters
- `URLs\path()` - Extract path from URL
- `Paths\all_routes()` - Find all route files
- `Exceptions/` - Domain-specific exceptions

#### 3. Infrastructure Layer (`Source/Infra/`)

Handles system-level utilities and external concerns.

**Key Components:**
- `Arrays.php` - Array manipulation utilities
- `Strings.php` - String processing utilities
- `Filesystem.php` - File operations
- `Reflections.php` - PHP reflection utilities

### API Reference

#### Business Functions

**`Finder\path(string $routes_path, string $suffix = '.php'): Outcome`**

Discovers routes from a directory structure. The `$routes_path` must be an absolute path to the routes directory.

Returns:
```php
[
    'success' => true,
    'message' => 'Routes loaded successfully',
    'data' => [
        'routes' => [
            ['pattern' => '/users/{id}', 'handler' => callable],
            // ...
        ]
    ]
]
```

**`Router\find(array $routes, string $url): Outcome`**

Find a matching route for the given URL.

Returns:
```php
[
    'success' => true,
    'message' => 'Route found',
    'data' => [
        'route' => ['pattern' => '/users/{id}', 'handler' => callable],
        'parameters' => ['id' => 123]
    ]
]
```

**`Router\respond(array $routes, string $url, string $method, array $variables = []): Outcome`**

Process an HTTP request and return response.

Returns:
```php
[
    'success' => true,
    'message' => 'Request handled successfully',
    'data' => ['response' => mixed]
]
```

#### Solution Functions

**`Routes\match_pattern(string $pattern, string $url_path): ?array`**

Match URL path against route pattern.

Returns array of parameters or null if no match.

**`Routes\validate_method(callable $handler, string $method, string $url): void`**

Validate HTTP method against handler's method restrictions.

Throws `MethodNotAllowedException` if method not allowed.

**`Routes\validate_parameters(callable $handler, array $url_parameters, array $variables): array`**

Validate and prepare parameters for handler.

Returns prepared parameters array.

### Creating Custom Integrations

#### Example: Database-Backed Routes

```php
class DatabaseRouteFinder
{
    public static function from_table(string $table): Outcome
    {
        try {
            $routes = [];
            $results = DB::query("SELECT pattern, handler FROM $table");
            
            foreach ($results as $row) {
                $routes[] = [
                    'pattern' => $row['pattern'],
                    'handler' => eval('return ' . $row['handler'] . ';')
                ];
            }
            
            return new Outcome(true, 'Routes loaded from database', [
                'routes' => $routes
            ]);
            
        } catch (Exception $e) {
            return new Outcome(false, "Database error: {$e->getMessage()}", []);
        }
    }
}

// Use in your application
$routes = DatabaseRouteFinder::from_table('routes');
if ($routes->success) {
    $outcome = respond($routes->data['routes'], $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
}
```

#### Example: Custom Parameter Validation

```php
class CustomValidator
{
    public static function validate_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validate_phone(string $phone): bool
    {
        return preg_match('/^\+?[\d\s-()]+$/', $phone) !== false;
    }
}

// In route handler
return function (string $email, string $phone) {
    if (!CustomValidator::validate_email($email)) {
        throw new ParameterValidationException('email', 'valid email', $email);
    }
    
    if (!CustomValidator::validate_phone($phone)) {
        throw new ParameterValidationException('phone', 'valid phone number', $phone);
    }
    
    return ['email' => $email, 'phone' => $phone];
};
```

#### Example: Custom Signal Handlers

```php
use PhpRepos\Observer\API\Bus\subscribe;

// Analytics tracking
subscribe(function (RouteDetected $signal) {
    // Track route usage in analytics
    Analytics::track('route_used', [
        'route' => $signal->details['route'],
        'timestamp' => time()
    ]);
});

// Security monitoring
subscribe(function (RouteFindingFailed $signal) {
    // Log potential security issues
    if (str_contains($signal->details['url'], '../')) {
        Security::log_suspicious_request($signal->details['url']);
    }
});

// Custom metrics
subscribe(function (HandlerExecuted $signal) {
    // Record custom metrics
    Metrics::increment('requests_handled');
    Metrics::histogram('response_size', strlen(json_encode($signal->details['response'])));
});
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development guidelines.

## License

MIT License. See [LICENSE](LICENSE) for details.