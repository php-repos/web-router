# Web Router Package

A lightweight, flexible routing package designed to map URLs to handler files or closures, supporting dynamic and optional parameters, HTTP method restrictions, and a signal-based event system for web applications.

## Features

- **File-based Routing**: Automatically detect routes from a directory structure using `detect_routes`.
- **Manual Route Definition**: Define routes programmatically using the `Router` class.
- **Dynamic Parameters**: Support URL parameters (e.g., `/users/{id}`) with type validation (`int`, `bool`, `string`).
- **Optional Parameters**: Handle optional URL segments (e.g., `/posts/{id}/{title?}`) with default values or `null`.
- **HTTP Method Restrictions**: Restrict handlers to specific HTTP methods (GET, POST, PUT, DELETE, PATCH) using repeatable `#[Method]` attributes or method-specific files (e.g., `get.php`).
- **Encoded URL Handling**: Process encoded characters (e.g., `%20` for spaces, `%2F` for slashes).
- **Signal System**: Emit signals for request lifecycle events (e.g., `RequestReceived`, `RouteDetected`, `HandlerExecuted`) for observability.
- **Exception Handling**: Throw meaningful exceptions:
    - `RouteNotFoundException` for unmatched URLs.
    - `MethodNotAllowedException` for disallowed HTTP methods.
    - `ParameterValidationException` for invalid parameter types.
- **Case Insensitivity**: Match routes regardless of URL case (e.g., `/USERS` matches `/users`).
- **Query String Ignorance**: Ignore query strings for cleaner routing.
- **Flexible Responses**: Handlers can return any data type (strings, arrays, etc.) as the response.
- **Variable Injection**: Pass `$_GET`, `$_POST`, and `$_FILES` variables to handlers via the `respond` function.
- **Repeatable Method Attributes**: Restrict a handler to one or more HTTP methods using repeatable `#[Method]` attributes in handler files or closures, allowing precise control over accepted methods.
- **Parameter Respect**: Honor required and optional parameters as defined in handler signatures (e.g., `function (string $email, ?int $id)`).

## Installation

Install the package using `phpkg`:

```bash
phpkg add https://github.com/php-repos/web-router.git
```

## Requirements

- PHP >= 8.2

## Usage

### Option 1: File-based Routing
1. **Create a Routes Directory**: Structure your routes in a `Routes` directory. For example:
    - `Routes/index.php`: Handles `/`.
    - `Routes/users/index.php`: Handles `/users`.
    - `Routes/users/{id}/show.php`: Handles `/users/123/show`.
    - `Routes/users/{id}/delete.php`: Handles DELETE and/or other specified methods for `/users/123`.

2. **Detect Routes**: Use `detect_routes` to load routes from the directory:

   ```php
   use PhpRepos\FileManager\Path;
   use function PhpRepos\Web\Routes\detect_routes;

   $routes = detect_routes(Path::from(__DIR__)->sub('Routes'));
   ```

3. **Handle Requests**: Use the `respond` function to process incoming requests, passing server variables:

   ```php
   use function PhpRepos\Web\Web\respond;

   return respond(
       routes: $routes,
       url: $_SERVER['REQUEST_URI'],
       method: $_SERVER['REQUEST_METHOD'],
       variables: array_replace($_GET, $_POST, $_FILES)
   );
   ```

**Example Handler File with Method Restrictions** (`Routes/users/{id}/delete.php`):

```php
<?php
use PhpRepos\Web\Attributes\Method;

return
#[Method('DELETE'), Method('POST')]
function (int $user_id) {
    return "Delete user $user_id";
};
```

**Example Handler with Required and Optional Parameters** (`Routes/users/{email}/{id?}.php`):

```php
<?php
use PhpRepos\Web\Attributes\Method;

return
#[Method('GET')]
function (string $email, ?int $id = null) {
    return $id ? "User $email with ID $id" : "User $email";
};
```

**Directory Structure Example**:

```
Routes/
├── index.php              # Handles "/"
├── users/
│   ├── index.php          # Handles "/users"
│   ├── {id}/
│   │   ├── show.php       # Handles "/users/{id}/show"
│   │   ├── delete.php     # Handles DELETE, POST "/users/{id}"
│   │   ├── posts/
│   │   │   ├── index.php  # Handles "/users/{id}/posts"
│   │   │   ├── {post_id}/ # Handles "/users/{id}/posts/{post_id}"
├── v1/
│   ├── posts/
│   │   ├── get.php        # Handles GET "/v1/posts"
│   │   ├── {id}.php       # Handles "/v1/posts/{id}"
│   │   ├── search%20by.php # Handles "/v1/posts/search by"
```

Prefer to use the `http.php` file that takes care of all of that. 

### Option 2: Manual Route Definition

Define routes programmatically using the `Router` class with method restrictions:

```php
use PhpRepos\Web\Router;
use PhpRepos\Web\Attributes\Method;

$router = new Router();
$router->handle('/users/{id}', #[Method('GET'), Method('POST')] function (int $id) {
    return "Showing user $id";
});
$router->handle('/posts/create', #[Method('POST')] function (string $title, ?int $id = null) {
    return $id ? "Created post $title with ID $id" : "Created post $title";
});

return respond(
    routes: $router,
    url: $_SERVER['REQUEST_URI'],
    method: $_SERVER['REQUEST_METHOD'],
    variables: array_replace($_GET, $_POST, $_FILES)
);
```

### Handling Parameters

- **Dynamic Parameters**: Define parameters in URLs (e.g., `{id}`) and specify types in handlers:

```php
// Routes/posts/{id}.php
return function (int $id) {
    return "Showing post $id";
};
```

- **Optional Parameters**: Use `{?param}` in URLs or `?type $param` in handler signatures:

```php
// Routes/users/{email}/{id?}.php
return function (string $email, ?int $id = null) {
    return $id ? "User $email with ID $id" : "User $email";
};
```

- **Type Validation**: Parameters are validated against their types (`int`, `bool`, `string`, etc.):

```php
// Routes/posts/{id}/{force}.php
return
#[Method('DELETE')]
function (int $id, bool $force) {
    return "Delete post ID $id with force as " . ($force ? 'true' : 'false');
};
```

- **Required vs. Optional**: The router respects parameter signatures:
    - Required parameters (e.g., `string $email`) must be provided in the URL or variables.
    - Optional parameters (e.g., `?int $id`) can be omitted, defaulting to `null` or the specified default value.

### HTTP Method Restrictions

Use the `#[Method]` attribute to limit handlers to specific HTTP methods. The attribute is repeatable, allowing multiple methods for a single handler:

```php
// Routes/users/{id}/edit.php
use PhpRepos\Web\Attributes\Method;

return
#[Method('PUT'), Method('PATCH')]
function (int $id) {
    return "Edit user $id";
};
```

If a request uses an unallowed method, a `MethodNotAllowedException` is thrown.

### Signal Handling

Monitor the request lifecycle by subscribing to signals:

```php
use PhpRepos\Observer\Observer\subscribe;
use PhpRepos\Web\Signals\RequestReceived;

subscribe(function ($signal) {
    if ($signal instanceof RequestReceived) {
        error_log("Request received: {$signal->details['url']}");
    }
});
```

**Available Signals**:
- `RequestReceived`: Emitted on request receipt.
- `RouteDetected`: Emitted when a route is matched.
- `ExecutingHandler`: Emitted before handler execution.
- `HandlerExecuted`: Emitted after handler execution with the response.
- `RouteNotFoundForUrl`: Emitted when no route matches.
- `DisallowedMethodDetected`: Emitted when the method is not allowed.

### Error Handling

Handle exceptions for robust routing:

```php
use PhpRepos\Web\Exceptions\RouteNotFoundException;
use PhpRepos\Web\Exceptions\MethodNotAllowedException;
use PhpRepos\Web\Exceptions\ParameterValidationException;

try {
    $response = respond($routes, $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
    echo is_array($response) ? json_encode($response) : $response;
} catch (RouteNotFoundException $e) {
    http_response_code(404);
    echo $e->getMessage(); // e.g., Route not found for URL: /not-found
} catch (MethodNotAllowedException $e) {
    http_response_code(405);
    echo $e->getMessage(); // e.g., Method is not allowed for URL: /users
} catch (ParameterValidationException $e) {
    http_response_code(400);
    echo $e->getMessage(); // e.g., Expected type int for user_id but string provided
}
```

## Use Cases

- **RESTful APIs**: Map endpoints like `/v1/posts/{id}` to handlers with specific methods (e.g., GET, DELETE).
- **Web Applications**: Serve dynamic content based on URL patterns and parameters.
- **Microservices**: Route requests in lightweight PHP services with minimal overhead.
- **Event-driven Systems**: Use signals to log, monitor, or trigger actions during the request lifecycle.

## Example Application

**index.php**:

```php
use PhpRepos\FileManager\Path;
use function PhpRepos\Web\Routes\detect_routes;
use function PhpRepos\Web\Web\respond;

$routes = detect_routes(Path::from(__DIR__)->sub('Routes'));

try {
    $response = respond(
        routes: $routes,
        url: $_SERVER['REQUEST_URI'],
        method: $_SERVER['REQUEST_METHOD'],
        variables: array_replace($_GET, $_POST, $_FILES)
    );
    echo is_array($response) ? json_encode($response) : $response;
} catch (Exception $e) {
    http_response_code($e instanceof RouteNotFoundException ? 404 : 400);
    echo $e->getMessage();
}
```

**Routes/users/{id}/delete.php**:

```php
<?php
use PhpRepos\Web\Attributes\Method;

return
#[Method('DELETE'), Method('POST')]
function (int $id) {
    return "Delete user $id";
};
```

**Routes/posts/create.php**:

```php
<?php
use PhpRepos\Web\Attributes\Method;

return
#[Method('POST')]
function (string $title, ?int $id = null) {
    return $id ? "Created post $title with ID $id" : "Created post $title";
};
```

## Contributing

Contributions are welcome! Please submit a pull request or open an issue on [GitHub](https://github.com/php-repos/web-router).

## License

MIT License. See [LICENSE](LICENSE) for details.