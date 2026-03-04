# PHP 8.2 Modernization Plan — Atom Framework

## Overview

The Atom Framework currently requires `php: ^7.0` and `phpunit/phpunit: ^6.1`. PHP 7.x reached end-of-life in November 2022 and no longer receives security patches. This document outlines a comprehensive plan to upgrade to **PHP 8.2** with full code modernization.

**Target**: PHP 8.2+
**Scope**: Full modernization (breaking changes, new features, refactored patterns)
**Versioning**: This constitutes a **major version bump** (e.g., v3.0)

---

## Table of Contents

1. [Summary of Changes](#1-summary-of-changes)
2. [Step 1 — Update Composer Dependencies](#2-step-1--update-composer-dependencies)
3. [Step 2 — Fix Breaking Changes](#3-step-2--fix-breaking-changes)
4. [Step 3 — Fix Type Coercion & Loose Comparisons](#4-step-3--fix-type-coercion--loose-comparisons)
5. [Step 4 — Refactor Legacy Patterns](#5-step-4--refactor-legacy-patterns)
6. [Step 5 — Adopt PHP 8 Features](#6-step-5--adopt-php-8-features)
7. [Step 6 — Migrate Tests to PHPUnit 10](#7-step-6--migrate-tests-to-phpunit-10)
8. [Step 7 — Verification](#8-step-7--verification)
9. [Drawbacks & Risks](#9-drawbacks--risks)
10. [Benefits](#10-benefits)
11. [References](#11-references)

---

## 1. Summary of Changes

| Priority | Area | Files Affected | Effort |
|----------|------|---------------|--------|
| CRITICAL | Composer dependencies | `composer.json` | Small |
| CRITICAL | Reflection API removal | `src/Container/Container.php` | Small |
| HIGH | Loose comparisons & type coercion | `src/Helpers/helpers.php`, `src/Url.php`, `src/Validation/Validator.php` | Medium |
| HIGH | Refactor `func_get_args()` patterns | `src/Validation/Validator.php`, `src/Db/Database.php` | Large |
| MEDIUM | PHPUnit 10 test migration | `test/` directory | Medium |
| LOW | PHP 8 feature adoption | All `src/` files | Medium |

---

## 2. Step 1 — Update Composer Dependencies

**File**: `composer.json`

### Changes

```diff
  "require": {
-     "php": "^7.0"
+     "php": "^8.2"
  },
  "require-dev": {
-     "phpunit/phpunit": "^6.1"
+     "phpunit/phpunit": "^10.0"
  }
```

### Actions

1. Update version constraints as shown above
2. Run `composer update`
3. Resolve any dependency conflicts

---

## 3. Step 2 — Fix Breaking Changes

### 3.1 `ReflectionParameter::getClass()` Removed (PHP 8.1)

**File**: `src/Container/Container.php` (Line ~93)

`ReflectionParameter::getClass()` was deprecated in PHP 8.0 and **removed in PHP 8.1**. The dependency injection container will crash without this fix.

```php
// BEFORE (broken on PHP 8.1+)
$dependency = $parameter->getClass();
if ($dependency === NULL) {
    // handle non-class dependency
}

// AFTER
$type = $parameter->getType();
if ($type === null || $type->isBuiltin()) {
    // handle non-class dependency
} else {
    $dependency = new ReflectionClass($type->getName());
    // resolve class dependency
}
```

### 3.2 Stricter Type Juggling (PHP 8.0)

PHP 8 changed how `0 == "string"` evaluates (now `false` instead of `true`). Audit all `==` comparisons between mixed types.

**Affected areas**:
- `src/Validation/Validator.php` — numeric comparisons in validation rules
- `src/Db/Database.php` — condition checks

---

## 4. Step 3 — Fix Type Coercion & Loose Comparisons

### 4.1 `strpos()` Truthy Checks

**File**: `src/Helpers/helpers.php`

| Line | Current Code | Issue | Fix |
|------|-------------|-------|-----|
| ~21 | `if (strpos($searchFile, '.ini'))` | Fails when match is at position `0` | `str_contains($searchFile, '.ini')` |
| ~91 | `substr($name, 0, 5) == 'HTTP_'` | Loose comparison | `str_starts_with($name, 'HTTP_')` |
| ~105 | `strpos($_SERVER['REQUEST_URI'], 'api')` | Truthy check | `str_contains($_SERVER['REQUEST_URI'], 'api')` |

### 4.2 Signature Verification (Security Fix)

**File**: `src/Url.php` (Line ~169)

```php
// BEFORE (timing attack vulnerable)
return $signature == $this->generateSignature(...);

// AFTER (constant-time comparison)
return hash_equals($this->generateSignature(...), $signature);
```

### 4.3 Validation Numeric Comparisons

**File**: `src/Validation/Validator.php`

| Line | Code | Fix |
|------|------|-----|
| ~284 | `return $params < $value` | Cast to numeric: `return (float)$value > (float)$params` |
| ~294 | `return $params > $value` | Cast to numeric: `return (float)$value < (float)$params` |
| ~315 | `return $value >= $min && $value <= $max` | Cast to numeric types explicitly |

---

## 5. Step 4 — Refactor Legacy Patterns

### 5.1 Replace `func_get_args()` with Typed Parameters

This is the **largest change** in the migration. ~30 methods across two files use `func_get_args()` instead of declared parameters.

#### Validation Rules (`src/Validation/Validator.php`)

~18 methods follow this pattern:

```php
// BEFORE
public function ruleRequired()
{
    list($value, $attribute, $message, $params) = func_get_args();
    // validation logic
}

// AFTER
public function ruleRequired(
    mixed $value,
    string $attribute,
    string $message,
    mixed $params = null
): bool {
    // validation logic
}
```

**Full list of methods to refactor**:
- `ruleRequired`, `ruleEmail`, `ruleNumber`, `ruleUrl`
- `ruleMin`, `ruleMax`, `ruleBetween`
- `ruleIn`, `ruleNotIn`
- `ruleDate`, `ruleDateFormat`
- `ruleRegex`, `ruleAlpha`, `ruleAlphaNum`
- `ruleConfirmed`, `ruleSame`, `ruleDifferent`
- All other `rule*` methods

#### Database Join Methods (`src/Db/Database.php`)

~12 methods use `func_num_args()` for argument validation:

```php
// BEFORE
public function join()
{
    if (func_num_args() != 3) {
        throw new DatabaseException("...");
    }
    list($joinTable, $tableCond, $joinCond) = func_get_args();
}

// AFTER
public function join(
    string $joinTable,
    string $tableCond,
    string $joinCond
): static {
    // join logic
}
```

**Methods to refactor**: `join`, `leftJoin`, `rightJoin`, `crossJoin`, `where`, `orWhere`, `having`, `orderBy`, and related query builder methods.

### 5.2 Fix Reference in foreach

**File**: `src/Db/PHPDataObjects.php` (Line ~71)

```php
// BEFORE
foreach ($this->params as $key => &$value) {
    $this->sth->bindParam(':' . $key, $value);
}

// AFTER
foreach ($this->params as $key => $value) {
    $this->sth->bindValue(':' . $key, $value);
}
```

---

## 6. Step 5 — Adopt PHP 8 Features

### 6.1 String Functions (PHP 8.0)

Replace `strpos`/`substr` patterns with native PHP 8 functions:

| Old Pattern | New Function |
|-------------|-------------|
| `strpos($haystack, $needle) !== false` | `str_contains($haystack, $needle)` |
| `strpos($haystack, $needle) === 0` | `str_starts_with($haystack, $needle)` |
| `substr($str, -strlen($suffix)) === $suffix` | `str_ends_with($str, $suffix)` |

### 6.2 Constructor Promotion (PHP 8.0)

Simplify constructors across all classes:

```php
// BEFORE
class Router {
    protected string $prefix;
    protected array $routes;

    public function __construct(string $prefix, array $routes = []) {
        $this->prefix = $prefix;
        $this->routes = $routes;
    }
}

// AFTER
class Router {
    public function __construct(
        protected string $prefix,
        protected array $routes = [],
    ) {}
}
```

### 6.3 Readonly Properties (PHP 8.1) / Readonly Classes (PHP 8.2)

Use `readonly` for immutable data:

```php
// Value objects, configuration, request data
readonly class DatabaseConfig {
    public function __construct(
        public string $host,
        public int $port,
        public string $database,
    ) {}
}
```

### 6.4 Match Expressions (PHP 8.0)

Replace simple switch statements:

```php
// BEFORE
switch ($method) {
    case 'GET':    return $this->handleGet(); break;
    case 'POST':   return $this->handlePost(); break;
    default:       throw new Exception('...');
}

// AFTER
return match ($method) {
    'GET'  => $this->handleGet(),
    'POST' => $this->handlePost(),
    default => throw new Exception('...'),
};
```

### 6.5 Nullsafe Operator (PHP 8.0)

```php
// BEFORE
$value = $request->getSession() ? $request->getSession()->get('key') : null;

// AFTER
$value = $request->getSession()?->get('key');
```

### 6.6 Union Types & Return Types (PHP 8.0)

Add type declarations to all public methods:

```php
// BEFORE
public function find($id) { ... }

// AFTER
public function find(int|string $id): ?Model { ... }
```

### 6.7 Enums (PHP 8.1)

Consider converting constant groups to enums where appropriate:

```php
enum HttpMethod: string {
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
}
```

### 6.8 Fibers (PHP 8.1)

Not immediately applicable but worth noting for future async capabilities.

---

## 7. Step 6 — Migrate Tests to PHPUnit 10

**Directory**: `test/`

### Key PHPUnit 10 Changes

| PHPUnit 6 | PHPUnit 10 |
|-----------|-----------|
| `protected function setUp()` | `protected function setUp(): void` |
| `protected function tearDown()` | `protected function tearDown(): void` |
| `@test` annotation | `#[Test]` attribute |
| `@dataProvider methodName` | `#[DataProvider('methodName')]` |
| `assertContains($needle, $string)` | `assertStringContainsString($needle, $string)` |
| `assertRegExp($pattern, $string)` | `assertMatchesRegularExpression($pattern, $string)` |
| `expectException` before action | Same (no change) |

### phpunit.xml Update

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Atom Test Suite">
            <directory>test</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

---

## 8. Step 7 — Verification

### Automated

1. `composer install` — verify all dependencies resolve
2. `./vendor/bin/phpunit` — all tests pass
3. `php -l src/**/*.php` — syntax check all source files

### Manual Smoke Tests

- [ ] Routing: GET/POST/PUT/DELETE routes resolve correctly
- [ ] Validation: all rule types validate correctly
- [ ] Database: CRUD operations, joins, transactions
- [ ] JWT: token generation and verification
- [ ] CSV: file export and download
- [ ] Container: dependency injection resolves classes
- [ ] Middleware: request pipeline functions

---

## 9. Drawbacks & Risks

| Risk | Impact | Severity | Mitigation |
|------|--------|----------|------------|
| **Drops PHP 7.x / 8.0 / 8.1 support** | Users must upgrade to PHP 8.2+ | Medium | PHP 8.2 is current stable; document requirement |
| **Breaking API changes** | Method signatures change for Validator and Database classes | High | Tag as major version (v3.0); provide migration guide |
| **Large refactor scope** | `func_get_args()` removal touches ~30 methods | Medium | Refactor incrementally; test each method after change |
| **PHPUnit 10 migration** | Test code needs rewriting | Low | Well-documented migration; mostly mechanical changes |
| **Third-party compatibility** | Projects depending on Atom may break | Medium | Provide changelog and upgrade guide |

---

## 10. Benefits

| Benefit | Details |
|---------|---------|
| **Performance** | PHP 8.2 is ~20-30% faster than PHP 7 (JIT compiler, optimizations) |
| **Type Safety** | Union types, intersection types, `null`, `false`, `true` standalone types catch bugs early |
| **Security** | Active security support until Dec 2026 (PHP 7 is EOL with no patches) |
| **Modern Syntax** | Cleaner, more readable code with match, nullsafe, readonly, enums |
| **IDE Support** | Better autocompletion, refactoring, and static analysis with typed code |
| **Ecosystem** | Access to latest packages that require PHP 8+ |

---

## 11. References

- [PHP 8.0 Migration Guide](https://www.php.net/manual/en/migration80.php)
- [PHP 8.1 Migration Guide](https://www.php.net/manual/en/migration81.php)
- [PHP 8.2 Migration Guide](https://www.php.net/manual/en/migration82.php)
- [PHPUnit 10 Migration Guide](https://docs.phpunit.de/en/10.5/migration.html)
- [PHP RFC: Deprecate ReflectionParameter::getClass()](https://wiki.php.net/rfc/deprecations_php_8_0)
- [PHP Supported Versions](https://www.php.net/supported-versions.php)
