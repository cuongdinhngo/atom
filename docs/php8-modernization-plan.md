# PHP 8.2 Modernization Plan — Atom Framework

## Overview

The Atom Framework currently requires `php: ^7.0 || ^8.0` and `phpunit/phpunit: ^9.5`. Some partial upgrades have already been applied (PHPUnit 9.5, modern `setUp(): void` signatures), but critical runtime-breaking issues remain and PHP 8 language features are not yet adopted. This document outlines the remaining work to complete the upgrade to **PHP 8.2**.

**Current State**: PHP ^7.0 || ^8.0, PHPUnit ^9.5
**Target**: PHP ^8.2, PHPUnit ^10.0
**Scope**: Full modernization (breaking changes, new features, refactored patterns)
**Versioning**: This constitutes a **major version bump** (e.g., v3.0)

### Already Completed

- `setUp(): void` / `tearDown(): void` return types in all test files
- Most test assertions already use modern methods (`assertStringContainsString`, `assertMatchesRegularExpression`)
- Base `TestCase` class with `$_SERVER` isolation

---

## Table of Contents

1. [Summary of Changes](#1-summary-of-changes)
2. [Phase 1 — Composer + Runtime-Breaking Fixes](#2-phase-1--composer--runtime-breaking-fixes)
3. [Phase 2 — Replace func_get_args() Patterns](#3-phase-2--replace-func_get_args-patterns)
4. [Phase 3 — Fix Type Coercion & Loose Comparisons](#4-phase-3--fix-type-coercion--loose-comparisons)
5. [Phase 4 — Switch to Match Conversions](#5-phase-4--switch-to-match-conversions)
6. [Phase 5 — Constructor Promotion](#6-phase-5--constructor-promotion)
7. [Phase 6 — JWT Legacy Cleanup](#7-phase-6--jwt-legacy-cleanup)
8. [Phase 7 — Syntax Modernization & Return Types](#8-phase-7--syntax-modernization--return-types)
9. [Phase 8 — Typed Class Properties](#9-phase-8--typed-class-properties)
10. [Phase 9 — Optional PHP 8.2 Features](#10-phase-9--optional-php-82-features)
11. [Verification](#11-verification)
12. [Drawbacks & Risks](#12-drawbacks--risks)
13. [Benefits](#13-benefits)
14. [References](#14-references)

---

## 1. Summary of Changes

| Priority | Area | Files Affected | Effort |
|----------|------|---------------|--------|
| CRITICAL | Composer dependencies | `composer.json` | Small |
| CRITICAL | Reflection API removal | `src/Container/Container.php` | Small |
| CRITICAL | Validator `getRule()` bug | `src/Validation/Validator.php` | Small |
| CRITICAL | PHPUnit 10 config | `phpunit.xml` | Small |
| HIGH | Replace `func_get_args()` patterns | `src/Validation/Validator.php`, `src/Db/Database.php` | Large |
| HIGH | Loose comparisons & type coercion | `src/Helpers/helpers.php`, `src/Http/Url.php`, `src/Validation/Validator.php` | Medium |
| HIGH | Security: timing-safe signature comparison | `src/Http/Url.php` | Small |
| HIGH | Reference in foreach | `src/Db/PHPDataObjects.php` | Small |
| MEDIUM | Switch → match conversions | `src/Db/Driver.php`, `src/Http/Request.php`, `src/Storage/StorageFactory.php`, `src/Libs/Image/GD.php` | Medium |
| MEDIUM | Constructor promotion | `src/Db/Driver.php`, `src/Template/Template.php`, `src/Storage/` | Medium |
| MEDIUM | JWT legacy PHP 5 cleanup | `src/Libs/JWT/JWT.php` | Small |
| LOW | `list()` → `[]` destructuring | Multiple files (~35 occurrences) | Medium |
| LOW | Return type declarations | All `src/` files (~150+ methods) | Large |
| LOW | Typed class properties | All `src/` files (~15 classes) | Medium |

---

## 2. Phase 1 — Composer + Runtime-Breaking Fixes

> **These changes must land together.** Updating the PHP constraint without fixing `getClass()` and the `getRule()` bug will crash the framework at runtime.

### 2.1 Update Composer Dependencies

**File**: `composer.json`

```diff
  "require": {
-     "php": "^7.0 || ^8.0"
+     "php": "^8.2"
  },
  "require-dev": {
-     "phpunit/phpunit": "^9.5"
+     "phpunit/phpunit": "^10.0"
  }
```

**Actions**: Update constraints, run `composer update`, resolve any dependency conflicts.

### 2.2 Fix `ReflectionParameter::getClass()` Removal (PHP 8.1)

**File**: `src/Container/Container.php` (Line ~93)

`ReflectionParameter::getClass()` was deprecated in PHP 8.0 and **removed in PHP 8.1**. The DI container will crash without this fix.

```php
// BEFORE (broken on PHP 8.1+)
$dependency = $parameter->getClass();
if ($dependency === NULL) {
    // handle non-class dependency
}

// AFTER
$type = $parameter->getType();
if ($type === null || !$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
    // handle non-class dependency
} else {
    $dependencies[] = $this->get($type->getName());
}
```

**Note**: Guard against `ReflectionUnionType` by checking `$type instanceof ReflectionNamedType` before calling `getName()`.

### 2.3 Fix `Validator::getRule()` Return Value Bug

**File**: `src/Validation/Validator.php` (Line ~160)

`getRule()` returns a 1-element array for rules without params, but callers do `list($rule, $params) = getRule(...)` which triggers "Undefined array key 1" on PHP 8.

```php
// BEFORE
return $output && $output[1] ? [$output[1], $output[2]] : [$rule];

// AFTER
return $output && $output[1] ? [$output[1], $output[2]] : [$rule, null];
```

### 2.4 Update `phpunit.xml` for PHPUnit 10

**File**: `phpunit.xml`

Remove `verbose="true"` attribute (removed in PHPUnit 10, causes deprecation warning).

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Atom Test Suite">
            <directory suffix="Test.php">test</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

**Verify**: `composer update && ./vendor/bin/phpunit` — all tests pass.

---

## 3. Phase 2 — Replace `func_get_args()` Patterns

This is the **largest change** in the migration. ~21 methods across two files use `func_get_args()` / `func_num_args()` instead of declared parameters.

### 3.1 Validator Rule Methods (`src/Validation/Validator.php`)

15 methods follow this pattern:

```php
// BEFORE
public static function required()
{
    list($value, $attribute, $message, $params) = func_get_args();
    // validation logic
}

// AFTER
public static function required(
    mixed $value,
    string $attribute,
    string $message,
    mixed $params = null
): string {
    // validation logic
}
```

**Methods to refactor**: `required`, `required_if`, `email`, `integer`, `string`, `min`, `max`, `between`, `in_array`, `date`, `date_format`, `after`, `before`, `image`, `array`

The call site at `checkValidation()` (line ~85) already passes 4 arguments via `call_user_func_array` — no change needed there.

### 3.2 Database Query Methods (`src/Db/Database.php`)

6 methods use `func_num_args()` for argument validation:

```php
// BEFORE
public function innerJoin()
{
    if (func_num_args() != 3) {
        throw new DatabaseException("...");
    }
    list($joinTable, $tableCond, $joinCond) = func_get_args();
}

// AFTER
public function innerJoin(
    string $joinTable,
    string $tableCond,
    string $joinCond
): static {
    // join logic (PHP enforces parameter count natively)
}
```

**Methods to refactor**: `innerJoin`, `leftJoin`, `rightJoin`, `having`, `orderBy`, `orWhere`

**Verify**: `./vendor/bin/phpunit`

---

## 4. Phase 3 — Fix Type Coercion & Loose Comparisons

### 4.1 `strpos()` Truthy Checks

**File**: `src/Helpers/helpers.php`

| Line | Current Code | Issue | Fix |
|------|-------------|-------|-----|
| ~21 | `if (strpos($searchFile, '.ini'))` | Fails when match is at position `0` | `str_contains($searchFile, '.ini')` |
| ~91 | `substr($name, 0, 5) == 'HTTP_'` | Loose comparison | `str_starts_with($name, 'HTTP_')` |
| ~105 | `strpos($_SERVER['REQUEST_URI'], 'api')` | Truthy check | `str_contains($_SERVER['REQUEST_URI'], 'api')` |

### 4.2 Signature Verification (Security Fix)

**File**: `src/Http/Url.php` (Line ~169)

```php
// BEFORE (timing attack vulnerable)
return $signature == $this->generateSignature($this->extractUri(), $params);

// AFTER (constant-time comparison)
return hash_equals($this->generateSignature($this->extractUri(), $params), $signature);
```

### 4.3 Session Status Comparison

**File**: `src/Http/Globals.php` (Line ~109)

```php
// BEFORE
if (session_status() == PHP_SESSION_NONE) {

// AFTER
if (session_status() === PHP_SESSION_NONE) {
```

### 4.4 Validation Numeric Comparisons

**File**: `src/Validation/Validator.php`

| Line | Code | Fix |
|------|------|-----|
| ~284 | `return $params < $value` | Cast: `return (float)$value > (float)$params` |
| ~294 | `return $params > $value` | Cast: `return (float)$value < (float)$params` |
| ~315 | `return $value >= $min && $value <= $max` | Cast all operands to `(float)` |

### 4.5 Fix Reference in foreach

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

**Verify**: `./vendor/bin/phpunit`

---

## 5. Phase 4 — Switch to Match Conversions

Convert eligible `switch` statements (return-value switches without side effects) to `match` expressions.

| File | Method | Line |
|------|--------|------|
| `src/Db/Driver.php` | `createConnection()` | ~73 |
| `src/Http/Request.php` | `getParametersByMethod()` | ~128 |
| `src/Storage/StorageFactory.php` | `init()` | ~26 |
| `src/Libs/Image/GD.php` | `imageCreateFromType()` | ~81 |
| `src/Libs/Image/GD.php` | `outputImageByType()` | ~108 |

Example:

```php
// BEFORE
switch ($this->driver) {
    case 'mysql':
        return new MySQL(...);
    default:
        throw new DatabaseException(...);
    break;
}

// AFTER
return match ($this->driver) {
    'mysql' => new MySQL(...),
    default => throw new DatabaseException(...),
};
```

**Skip**: `Database.php::buildQuery()` and `Database.php::parseConditions()` — multi-statement cases with side effects, not suitable for `match`.

**Verify**: `./vendor/bin/phpunit`

---

## 6. Phase 5 — Constructor Promotion

Simplify constructors using PHP 8.0 promoted properties where parameters are directly assigned.

**Best candidates** (simple assign-from-parameter):

| File | Properties |
|------|-----------|
| `src/Db/Driver.php` | `$driver`, `$host`, `$user`, `$password`, `$database`, `$port` |
| `src/Template/Template.php` | `$templates`, `$data` |
| `src/Storage/StorageFactory.php` | `$storageConfig` |
| `src/Storage/LocalService.php` | `$path` |
| `src/File/File.php` | `$type` (partial — `$storage` is computed) |

**Skip**: `Database.php`, `Server.php`, `MySQL.php` — constructors have computed/fallback logic (`?? env(...)` patterns) that prevent clean promotion.

Example:

```php
// BEFORE (Driver.php)
class Driver {
    protected $driver;
    protected $host;
    // ... 4 more properties

    public function __construct($driver, $host, ...) {
        $this->driver = $driver;
        $this->host = $host;
        // ...
    }
}

// AFTER
class Driver {
    public function __construct(
        protected ?string $driver = null,
        protected ?string $host = null,
        protected ?string $user = null,
        protected ?string $password = null,
        protected ?string $database = null,
        protected ?string $port = null,
    ) {}
}
```

**Verify**: `./vendor/bin/phpunit`

---

## 7. Phase 6 — JWT Legacy Cleanup

**File**: `src/Libs/JWT/JWT.php`

Remove PHP 5.x compatibility code that is dead on PHP 8.2:

| Location | Current | Fix |
|----------|---------|-----|
| `jsonDecode()` | `version_compare(PHP_VERSION, '5.4.0', '>=')` branch | Remove check, always use `JSON_BIGINT_AS_STRING` |
| `verify()` | `if (function_exists('hash_equals'))` check | Remove check, `hash_equals` exists since PHP 5.6 |
| `safeStrlen()` | `if (function_exists('mb_strlen'))` check | Remove check, `mb_strlen` is standard on PHP 8.2 |
| Class comment | "PHP version 5" reference (line ~15) | Update to PHP 8.2 |

**Verify**: `./vendor/bin/phpunit`

---

## 8. Phase 7 — Syntax Modernization & Return Types

### 8.1 Convert `list()` to Array Destructuring

~35 occurrences across these files:

| File | Approx Count |
|------|-------------|
| `src/Db/Database.php` | ~12 |
| `src/Db/PHPDataObjects.php` | ~3 |
| `src/Libs/JWT/JWT.php` | ~3 |
| `src/Libs/Image/GD.php` | ~4 |
| `src/File/File.php` | ~3 |
| `src/File/Image.php` | ~2 |
| `src/Controllers/Controller.php` | ~3 |
| `src/Guard/Auth.php` | ~1 |

Each `list($a, $b) = ...` becomes `[$a, $b] = ...`

### 8.2 Add Return Type Declarations

Priority files (~150+ methods across the codebase):

| File | Key Methods |
|------|------------|
| `src/Container/Container.php` | `set(): void`, `get(): mixed`, `resolve(): object`, `getDependencies(): array` |
| `src/Db/Database.php` | Query builder methods → `static` (chaining), result methods → `array` |
| `src/Http/Globals.php` | `path(): string`, `uri(): string`, `method(): string`, etc. |
| `src/Http/Request.php` | `all(): array`, `headers(): array`, ArrayAccess methods |
| `src/Http/Url.php` | `protocol(): string`, `signedUrl(): string`, `hasCorrectSignature(): bool` |
| `src/Validation/Validator.php` | All rule methods → `string` |
| `src/File/File.php` | `name(): string`, `size(): string`, `metadata(): array` |
| `src/Libs/JWT/JWT.php` | `encode(): string`, `decode(): object`, `sign(): string`, `verify(): bool` |

**Verify**: `./vendor/bin/phpunit`

---

## 9. Phase 8 — Typed Class Properties

Add type declarations to class properties:

| File | Key Properties |
|------|---------------|
| `src/Container/Container.php` | `protected array $instances = []` |
| `src/Db/Database.php` | `protected ?string $driver`, `protected string $conditions = ""`, `protected array $where = []` |
| `src/Db/MySQL.php` | `protected \mysqli $mysqli` |
| `src/Db/PHPDataObjects.php` | `protected array $params = []`, `protected array $where = []` |
| `src/Http/Request.php` | `public array $request`, `public string $uri`, `public string $method` |
| `src/Validation/Validator.php` | `static array $errors = []`, `static array $inputRules = []` |
| `src/Template/Template.php` | `protected array $templates`, `protected array $data` |
| `src/File/File.php` | `public ?array $file`, `public ?string $type` |

**Verify**: `./vendor/bin/phpunit`

---

## 10. Phase 9 — Optional PHP 8.2 Features

Low priority, can be done later:

- **Readonly properties** for immutable data (e.g., `Url::$key`)
- **Named arguments** in long parameter lists
- **`#[Test]` attributes** in test files (PHPUnit 10 still supports `test` prefix)
- **Enums** for HTTP methods, database drivers, etc.
- **Nullsafe operator** where null-check chains exist

---

## 11. Verification

### After Each Phase

1. `./vendor/bin/phpunit` — all tests pass
2. `php -l` syntax check on changed files

### Final Verification

1. `composer install` from clean state — all dependencies resolve
2. `./vendor/bin/phpunit` — full test suite green
3. Audit for remaining:
   - `func_get_args` / `func_num_args` usage
   - `ReflectionParameter::getClass()` calls
   - Loose `==` comparisons on mixed types
   - `strpos` truthy checks

### Manual Smoke Tests

- [ ] Container: dependency injection resolves classes correctly
- [ ] Routing: GET/POST/PUT/DELETE routes resolve correctly
- [ ] Validation: all rule types validate correctly
- [ ] Database: CRUD operations, joins, transactions
- [ ] JWT: token generation and verification
- [ ] CSV: file export and download
- [ ] Middleware: request pipeline functions

---

## 12. Drawbacks & Risks

| Risk | Impact | Severity | Mitigation |
|------|--------|----------|------------|
| **Drops PHP 7.x / 8.0 / 8.1 support** | Users must upgrade to PHP 8.2+ | Medium | PHP 8.2 is current stable; document requirement |
| **Breaking API changes** | Method signatures change for Validator and Database classes | High | Tag as major version (v3.0); provide migration guide |
| **Large refactor scope** | `func_get_args()` removal touches ~21 methods | Medium | Refactor incrementally; test after each phase |
| **PHPUnit 10 migration** | Minor config and assertion changes | Low | Most already done; only `verbose` removal and minor fixes remain |
| **ReflectionUnionType edge case** | Container may fail on union-typed constructor params | Low | Guard with `instanceof ReflectionNamedType` check |
| **Third-party compatibility** | Projects depending on Atom may break | Medium | Provide changelog and upgrade guide |

---

## 13. Benefits

| Benefit | Details |
|---------|---------|
| **Performance** | PHP 8.2 is ~20-30% faster than PHP 7 (JIT compiler, optimizations) |
| **Type Safety** | Union types, intersection types, `null`, `false`, `true` standalone types catch bugs early |
| **Security** | Active security support until Dec 2026 (PHP 7 is EOL with no patches); timing-safe signature comparison |
| **Modern Syntax** | Cleaner, more readable code with match, nullsafe, readonly, constructor promotion |
| **IDE Support** | Better autocompletion, refactoring, and static analysis with typed code |
| **Ecosystem** | Access to latest packages that require PHP 8+ |

---

## 14. References

- [PHP 8.0 Migration Guide](https://www.php.net/manual/en/migration80.php)
- [PHP 8.1 Migration Guide](https://www.php.net/manual/en/migration81.php)
- [PHP 8.2 Migration Guide](https://www.php.net/manual/en/migration82.php)
- [PHPUnit 10 Migration Guide](https://docs.phpunit.de/en/10.5/migration.html)
- [PHP RFC: Deprecate ReflectionParameter::getClass()](https://wiki.php.net/rfc/deprecations_php_8_0)
- [PHP Supported Versions](https://www.php.net/supported-versions.php)
