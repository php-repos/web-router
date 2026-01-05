# Contributing to Web Router Package

Thank you for considering contributing to Web Router Package! This document provides guidelines and principles to help you contribute effectively.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Architecture Principles](#architecture-principles)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Pull Request Process](#pull-request-process)

## Code of Conduct

- Be respectful and constructive in all interactions
- Focus on what is best for the community
- Show empathy towards other community members
- Accept constructive criticism gracefully

## Getting Started

### Prerequisites

- PHP 8.3 or higher
- `phpkg` package manager
- Git

### Setting Up Development Environment

1. Fork and clone the repository:
```bash
git clone https://github.com/YOUR_USERNAME/web-router.git
cd web-router
```

2. Install dependencies:
```bash
phpkg install
phpkg build
```

3. Run tests:
```bash
cd ~/phpkg/web-router && phpkg build && cd build && phpkg run test-runner run
```

## Architecture Principles

The Web Router Package follows **Natural Architecture**, which provides clear separation of concerns across three layers. Understanding these principles is crucial for contributing.

### The Three Layers

#### 1. Business Layer (`Source/Business/`)

**Purpose:** Define *what* the system does, not *how*.

**Principles:**
- Contains only specifications and contracts
- No implementation details (no reflection, no I/O, no parsing)
- Returns `Outcome` objects with predictable structure
- Depends only on Solution and Infrastructure layers
- Functions should be pure specifications
- Emits signals for observability

**Example:**
```php
// GOOD: Business function delegates to Solution
function respond(array $routes, string $url, string $method, array $variables = []): Outcome
{
    $outcome = find($routes, $url);
    if (!$outcome->success) {
        send(RouteNotFoundForUrl::to($url, $method));
        return new Outcome(false, $outcome->message, ['url' => $url, 'method' => $method]);
    }
    // ... more delegation
}

// BAD: Business function contains implementation
function respond(array $routes, string $url, string $method): Outcome
{
    foreach ($routes as $route) {
        $pattern = '/^' . str_replace('{id}', '(\d+)', $route['pattern']) . '$/';
        if (preg_match($pattern, $url)) {
            // ... direct implementation
        }
    }
}
```

**Key Components:**
- `Router.php` - Request handling operations (respond, find)
- `Finder.php` - Route discovery from filesystem
- `Outcome.php` - Return value wrapper
- `Attributes/` - HTTP method restriction attributes
- `Signals/` - Event objects for observer pattern

#### 2. Solution Layer (`Source/Solution/`)

**Purpose:** Implement *how* things work.

**Principles:**
- Contains actual implementation logic
- Uses reflection, parsing, data transformation
- Called by Business layer, never calls Business layer
- Can depend on Infrastructure layer
- Pure functions without side effects where possible
- Includes debug logging for observability

**Example:**
```php
// GOOD: Solution function contains implementation
function match_pattern(string $pattern, string $url_path): ?array
{
    debug('Matching URL path against route pattern', ['pattern' => $pattern, 'url_path' => $url_path]);
    
    $pattern_parts = explode('/', trim($pattern, '/'));
    $url_parts = explode('/', trim($url_path, '/'));
    
    // ... matching algorithm
    return $parameters;
}

// GOOD: Solution function uses complex logic
function url_pattern(string $root, string $route, string $suffix): string
{
    $relative = Strings\replace_first_occurrence($route, $root, '');
    
    if (Strings\ends_with($relative, 'index' . $suffix)) {
        $relative = Strings\before_last_occurrence($relative, 'index' . $suffix);
    } else {
        $relative = Strings\before_last_occurrence($relative, $suffix);
    }
    
    return $relative === '' ? '/' : $relative;
}
```

**Key Components:**
- `Routes.php` - Route operations (sort, match, validate)
- `URLs.php` - URL parsing and processing
- `Paths.php` - Filesystem path operations
- `Exceptions/` - Domain-specific exceptions

#### 3. Infrastructure Layer (`Source/Infra/`)

**Purpose:** Handle external concerns and system utilities.

**Principles:**
- System-level operations and utilities
- No business logic
- Can be used by other layers
- Pure functions without side effects where possible

**Example:**
```php
// GOOD: Infrastructure function handles system operations
function list_files(string $directory): array
{
    if (!is_dir($directory)) {
        return [];
    }
    
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}
```

**Key Components:**
- `Arrays.php` - Array manipulation utilities
- `Strings.php` - String processing utilities
- `Filesystem.php` - File operations
- `Reflections.php` - PHP reflection utilities

### Layer Dependencies

```
Business → Solution → Infrastructure
   ↓          ↓
   └──────────┴────────→ Infrastructure
```

**Rules:**
- Business can call Solution and Infrastructure
- Solution can call Infrastructure
- Infrastructure has no dependencies on other layers
- **Never reverse these dependencies**

### The Outcome Pattern

All Business layer functions return `Outcome` objects for predictable, consistent results:

```php
class Outcome
{
    public bool $success;
    public string $message;
    public array $data;
}
```

**Usage:**
```php
$outcome = Router\find($routes, $url);

if ($outcome->success) {
    // Use $outcome->data['route'], $outcome->data['parameters']
} else {
    // Handle $outcome->message
}
```

### Signal System

The router uses an observer pattern for monitoring and extensibility:

**Signal Types:**
- **Plans**: Emitted before operations start
- **Events**: Emitted after operations complete

**Example:**
```php
// Plan (before operation)
class FindingRoute extends Plan
{
    public static function for_url(string $url, int $route_count): static
    {
        return static::create('Finding route for URL.', [
            'url' => $url,
            'route_count' => $route_count,
        ]);
    }
}

// Event (after operation)
class RouteDetected extends Event
{
    public static function route(string $pattern): static
    {
        return static::create('Web route detected', ['route' => $pattern]);
    }
}
```

## Development Workflow

### Branch Naming

- Feature: `feature/description`
- Bug fix: `fix/description`
- Architecture: `arch/description`
- Documentation: `docs/description`
- Tests: `test/description`

### Commit Messages

Follow conventional commit format:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `refactor`: Code refactoring
- `docs`: Documentation changes
- `test`: Test additions/changes
- `arch`: Architecture changes

**Examples:**
```
feat(business): add route caching mechanism

Add caching for discovered routes to improve performance.
Routes are cached for 5 minutes and invalidated on filesystem changes.

Closes #45
```

```
fix(solution): handle encoded forward slashes in URL parameters

Fix URL decoding to properly handle %2F encoded slashes.
Previously encoded slashes were being decoded too early, causing
incorrect route matching.

Fixes #67
```

```
refactor(business): extract parameter validation to separate function

Move parameter validation logic from respond() function to dedicated
validate_handler_parameters() function for better testability and
separation of concerns.
```

## Coding Standards

### General Principles

1. **Separation of Concerns**: Each function should do one thing well
2. **No Premature Abstraction**: Keep it simple until complexity is needed
3. **Explicit Over Implicit**: Make intentions clear
4. **Functional Style**: Prefer pure functions without side effects
5. **Observable**: Add signals for important operations
6. **Debuggable**: Add debug logs for solution functions used by business

### PHP Standards

- Use PHP 8.2+ features (attributes, named arguments, etc.)
- Follow PSR-12 coding standard
- Use type hints for all parameters and return types
- Use readonly properties where applicable
- Use attributes for metadata (like method restrictions)

### Naming Conventions

**Functions:**
- Use verb phrases: `find()`, `match_pattern()`, `validate_method()`
- Keep names descriptive but concise
- Use snake_case for function names

**Variables:**
- Use descriptive names: `$route_handlers`, `$url_path`, `$prepared_params`
- Avoid abbreviations unless widely understood
- Use snake_case for variables

**Classes/Attributes:**
- Use PascalCase: `Outcome`, `Method`, `RouteDetected`
- Name should describe what it is, not what it does

**Files:**
- Business layer: One concept per file (Router.php, Finder.php)
- Solution layer: Related functions grouped (Routes.php, URLs.php)

### Documentation

All functions must have PHPDoc comments:

```php
/**
 * Find a matching route for the given URL.
 *
 * Searches through routes to find one that matches the URL pattern.
 * Emits RouteDetected signal when a match is found.
 *
 * @param array $routes Array of routes to search
 * @param string $url The URL to match
 * @return Outcome Success with matched route and parameters, or failure if no match
 */
function find(array $routes, string $url): Outcome
{
    // Implementation
}
```

**Requirements:**
- Summary line (what function does)
- Detailed description (how it works, edge cases, signals emitted)
- `@param` for each parameter with type and description
- `@return` with type and description
- `@throws` for any exceptions

### Debug Logging

Add debug logs to all Solution functions used by Business layer:

```php
use function PhpRepos\Logger\API\Logs\debug;

function sort(array $routes): array
{
    debug('Sorting routes by specificity', ['route_count' => count($routes)]);
    
    return Arrays\sort($routes, function ($a, $b) {
        return substr_count($a['pattern'], '{') <=> substr_count($b['pattern'], '{');
    });
}

function match_pattern(string $pattern, string $url_path): ?array
{
    debug('Matching URL path against route pattern', ['pattern' => $pattern, 'url_path' => $url_path]);
    
    // ... implementation
    
    debug('Pattern matching result', ['matched' => $parameters !== null, 'parameters' => $parameters]);
    
    return $parameters;
}
```

**Guidelines:**
- Log function entry with relevant parameters
- Log key decisions or transformations
- Log function results or important state changes
- Use descriptive message and include relevant context data
- Don't log sensitive information (passwords, tokens, etc.)

### File Organization

**Business Layer:**
```
Source/Business/
├── Router.php          # All Router\* functions
├── Finder.php          # All Finder\* functions
├── Outcome.php         # Outcome class
├── Attributes/         # Attribute classes
└── Signals/            # Signal/event classes
```

**Solution Layer:**
```
Source/Solution/
├── Routes.php          # Route operations
├── URLs.php            # URL processing
├── Paths.php           # Path operations
└── Exceptions/         # Domain exceptions
```

**Infrastructure Layer:**
```
Source/Infra/
├── Arrays.php          # Array utilities
├── Strings.php         # String utilities
├── Filesystem.php      # File operations
└── Reflections.php    # Reflection utilities
```

## Testing Guidelines

### Test Structure

Tests are located in `Tests/` directory:

- `WebTest.php` - Integration tests for router functionality
- `Routes/` - Sample route files for testing

### Writing Tests

```php
use function PhpRepos\TestRunner\Runner\test;

test(
    title: 'it should handle dynamic parameters in route matching',
    case: function () {
        // Arrange
        $routes = [
            ['pattern' => '/users/{id}', 'handler' => fn($id) => "User $id"]
        ];
        
        // Act
        $outcome = Router\find($routes, '/users/123');
        
        // Assert
        assert_true($outcome->success);
        assert_equal(['id' => '123'], $outcome->data['parameters']);
        assert_equal('/users/{id}', $outcome->data['route']['pattern']);
    }
);

test(
    title: 'it should emit expected signals during successful routing',
    case: function () {
        $signals = [];
        
        subscribe(function ($signal) use (&$signals) {
            $signals[] = $signal;
        });
        
        $routes = [
            ['pattern' => '/', 'handler' => fn() => 'hello']
        ];
        
        $outcome = Router\respond($routes, '/', 'GET');
        
        // Verify signal sequence
        assert_true($signals[0] instanceof RequestReceived);
        assert_true($signals[1] instanceof FindingRoute);
        assert_true($signals[2] instanceof RouteDetected);
        assert_true($signals[3] instanceof ExecutingHandler);
        assert_true($signals[4] instanceof HandlerExecuted);
    }
);
```

### Test Coverage

- Write tests for new Business layer functions
- Test both success and failure cases
- Test edge cases and boundary conditions
- Ensure Solution layer functions work correctly
- Test signal emission and order
- Integration tests for full request lifecycle
- Test parameter validation and type casting
- Test HTTP method restrictions

### Running Tests

```bash
cd ~/phpkg/web-router
phpkg build
cd build
phpkg run test-runner run
```

Run specific test file:
```bash
phpkg run test-runner run --filter=WebTest
```

### Route Testing

Create test routes in `Tests/Routes/` directory following the same patterns as real routes:

```
Tests/Routes/
├── index.php                  # Home route
├── users/
│   ├── {id}/
│   │   ├── show.php          # Dynamic parameter
│   │   └── delete.php        # Method restriction
│   └── create.php            # Method-specific file
└── posts/
    ├── {category}/
    │   └── {id?}.php         # Optional parameters
```

## Pull Request Process

### Before Submitting

1. **Run all tests** and ensure they pass
2. **Run build** and verify no errors
3. **Update documentation** if adding features
4. **Add tests** for new functionality
5. **Follow architecture principles** strictly
6. **Add docblocks** to all new functions
7. **Add debug logs** to solution functions
8. **Add signals** for observable operations
9. **Update README.md** if behavior changes

### PR Requirements

1. **Clear title** describing change
2. **Description** explaining:
   - What changed
   - Why it changed
   - How it was tested
   - Any breaking changes
3. **Reference issues** if applicable
4. **Small, focused changes** - one feature per PR
5. **No breaking changes** unless discussed and documented

### PR Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Refactoring
- [ ] Documentation
- [ ] Architecture change
- [ ] Performance improvement
- [ ] Security fix

## Changes Made
- Change 1
- Change 2

## Testing
- [ ] All tests pass
- [ ] Added new tests for Business layer
- [ ] Added integration tests
- [ ] Tested signal emission
- [ ] Manual testing completed

## Architecture Compliance
- [ ] Follows Natural Architecture layers
- [ ] Business layer only contains specifications
- [ ] Solution layer contains implementation
- [ ] No circular dependencies
- [ ] Added debug logs to Solution functions
- [ ] Added signals for observable operations

## Documentation
- [ ] Updated README if needed
- [ ] Added/updated docblocks
- [ ] Updated CONTRIBUTING.md if needed
- [ ] Created/updated UPGRADE.md if breaking change

## Performance
- [ ] No performance regression
- [ ] Added performance improvements
- [ ] Benchmarks run (if applicable)

## Security
- [ ] No security vulnerabilities introduced
- [ ] Input validation added
- [ ] Reviewed for XSS/CSRF issues
- [ ] Tested with malformed input

## Related Issues
Closes #123, Fixes #456
```

### Review Process

1. Maintainers review code for:
   - Architecture compliance
   - Code quality and standards
   - Test coverage
   - Documentation
   - Signal implementation
   - Debug logging
   - Security considerations
   - Performance implications
2. Address review feedback promptly
3. Once approved, PR will be merged

### After Merge

- Delete your feature branch
- Monitor for any issues
- Respond to follow-up discussions
- Update any dependent documentation

## Questions?

- Open an issue for questions
- Tag it with `question` label
- Be specific about what you need help with

## License

By contributing, you agree that your contributions will be licensed under the same license as the project (MIT License).