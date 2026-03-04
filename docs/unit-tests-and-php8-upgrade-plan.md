# Unit Tests & PHP 8.2 Upgrade Plan — Atom Framework

## Context

The Atom Framework currently has **zero unit tests** and requires `php: ^7.0` with `phpunit/phpunit: ^6.1`. PHP 7.x is end-of-life and no longer receives security patches.

**Strategy**: Create a comprehensive test suite first (using PHPUnit 9.5), verify all tests pass, then upgrade to PHP 8.2 with tests as a safety net to catch regressions.

---

## Table of Contents

1. [Phase 1 — Setup Test Infrastructure](#phase-1--setup-test-infrastructure)
2. [Phase 2 — Create Unit Tests](#phase-2--create-unit-tests-14-test-files)
3. [Phase 3 — Verify Baseline](#phase-3--verify-baseline)
4. [Phase 4 — PHP 8.2 Upgrade](#phase-4--php-82-upgrade)
5. [File Structure](#file-structure)
6. [Verification Checklist](#verification-checklist)

---

## Phase 1 — Setup Test Infrastructure

### Step 1.1: Update Composer Dependencies

**File**: `composer.json`

```diff
  "require-dev": {
-     "phpunit/phpunit": "^6.1"
+     "phpunit/phpunit": "^9.5"
  }
```

Then run:
```bash
composer update
```

> **Why PHPUnit 9.5?** PHPUnit 6 does not support PHP 8.x. PHPUnit 9.5 supports PHP 7.3–8.x, giving us a bridge version before upgrading to PHPUnit 10 in Phase 4.

### Step 1.2: Create PHPUnit Configuration

**File**: `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="Atom Test Suite">
            <directory suffix="Test.php">test</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Step 1.3: Create Base Test Case

**File**: `test/TestCase.php`

A shared base class providing:
- Common setUp/tearDown for $_SERVER mocking
- Helper methods for creating temp files (CSV, log tests)
- Database connection mocking utilities

---

## Phase 2 — Create Unit Tests (14 Test Files)

Tests are ordered by **testability** (easiest first), **criticality**, and **coverage of PHP 8 breaking areas**.

---

### 2.1 Helper Functions

**File**: `test/Helpers/HelpersTest.php`
**Source**: `src/Helpers/helpers.php`

| Test | Function | What It Verifies |
|------|----------|-----------------|
| `testStripSpace` | `stripSpace()` | Removes all whitespace from strings |
| `testStripSpaceNull` | `stripSpace(null)` | Handles null input |
| `testJson` | `json()` | Encodes data to JSON with unicode support |
| `testJsonInvalidInput` | `json()` | Throws exception on encoding failure |
| `testGps2Num` | `gps2Num()` | Converts GPS coordinate fractions to decimal |
| `testGps2NumZeroDenominator` | `gps2Num()` | Handles division by zero |
| `testNow` | `now()` | Returns current datetime in `Y-m-d H:i:s` format |
| `testToday` | `today()` | Returns current date in `Y-m-d` format |
| `testGetHeaders` | `getHeaders()` | Extracts HTTP_ headers from $_SERVER |
| `testGetHeadersEmpty` | `getHeaders()` | Returns empty array when no HTTP headers |
| `testIsApiWithJsonHeader` | `isApi()` | Detects API request by Content-Type header |
| `testIsApiWithUriPath` | `isApi()` | Detects API request by URI containing 'api' |
| `testIsApiReturnsFalse` | `isApi()` | Returns false for non-API requests |
| `testUrl` | `url()` | Constructs full URL from $_SERVER |
| `testEnv` | `env()` | Retrieves environment variables |
| `testEnvThrowsOnInvalid` | `env()` | Throws exception for missing env var |

**PHP 8 relevance**: Tests `strpos()` truthy checks that break when match is at position 0.

---

### 2.2 Validation

**File**: `test/Validation/ValidatorTest.php`
**Source**: `src/Validation/Validator.php` (Trait)

| Test Group | Rules Tested | What It Verifies |
|-----------|-------------|-----------------|
| Required | `required` | Empty/null/whitespace detection |
| Type rules | `string`, `integer`, `email`, `url`, `date`, `array` | Type validation |
| Numeric rules | `min`, `max`, `between` | Numeric comparisons (PHP 8 type coercion risk) |
| String rules | `alpha`, `alpha_num`, `regex` | Pattern matching |
| Inclusion rules | `in_array` | Value-in-set checking |
| Comparison rules | `confirmed`, `same`, `different` | Cross-field validation |
| Conditional rules | `required_if` | Conditional requirement |
| Date rules | `date_format`, `after`, `before` | Date comparison |
| Image rule | `image` | File type validation |
| Full pipeline | `execute()` | Complete validation with multiple rules |
| Rule parsing | `splitRules()`, `getRule()` | Rule string parsing |
| Error messages | `errors()`, `messages()` | Custom and default error messages |

**PHP 8 relevance**: All rule methods use `list(...) = func_get_args()` — tests ensure behavior is preserved after refactoring to typed parameters.

**Example test**:
```php
public function testRuleRequired()
{
    $result = Validator::execute(
        ['name' => 'John'],
        ['name' => 'required']
    );
    $this->assertEmpty(Validator::errors());
}

public function testRuleRequiredFails()
{
    $result = Validator::execute(
        ['name' => ''],
        ['name' => 'required']
    );
    $this->assertNotEmpty(Validator::errors());
}
```

---

### 2.3 Container (Dependency Injection)

**File**: `test/Container/ContainerTest.php`
**Source**: `src/Container/Container.php`

| Test | What It Verifies |
|------|-----------------|
| `testSetAndGet` | Register and resolve a binding |
| `testGetWithClosure` | Resolve a closure binding |
| `testResolveSimpleClass` | Instantiate a class with no dependencies |
| `testResolveWithDependencies` | Instantiate a class with constructor dependencies |
| `testResolveWithParameters` | Pass custom parameters during resolution |
| `testGetDependencies` | Resolve nested dependencies via reflection |
| `testGetNonExistentThrows` | Throws ContainerException for unknown bindings |
| `testResolveUnresolvableThrows` | Throws when dependency can't be resolved |

**PHP 8 relevance**: Uses `ReflectionParameter::getClass()` which is **removed in PHP 8.1**. Tests ensure DI still works after migration to `getType()`.

**Example test**:
```php
public function testResolveWithDependencies()
{
    $container = new Container();
    // Register a class that depends on another class
    $instance = $container->resolve(ServiceWithDependency::class);
    $this->assertInstanceOf(ServiceWithDependency::class, $instance);
}
```

---

### 2.4 Database Query Builder

**File**: `test/Db/DatabaseTest.php`
**Source**: `src/Db/Database.php`

| Test Group | Methods Tested | What It Verifies |
|-----------|---------------|-----------------|
| Table setup | `table()`, `checkTable()` | Table name assignment |
| Select | `select()` | Column selection, default `*` |
| Conditions | `where()`, `orWhere()` | WHERE clause building |
| Ranges | `whereBetween()`, `whereNotBetween()` | BETWEEN clauses |
| Null checks | `whereNull()`, `whereNotNull()` | NULL condition clauses |
| Set checks | `whereIn()`, `whereNotIn()` | IN clause building |
| Joins | `innerJoin()`, `leftJoin()`, `rightJoin()` | JOIN clause building |
| Sorting | `orderBy()`, `groupBy()`, `having()` | ORDER/GROUP/HAVING |
| Pagination | `limit()`, `offset()` | LIMIT/OFFSET clauses |
| CRUD | `insert()`, `update()`, `delete()`, `truncate()` | DML query building |
| Fillable | `setFillable()`, `hasFillable()` | Mass-assignment protection |
| Query log | `enableQueryLog()`, `getQueryLog()` | Query logging |
| Escaping | `escape()` | SQL injection prevention |

**PHP 8 relevance**: Join methods use `func_num_args()` / `func_get_args()` for argument handling. Tests ensure query output is identical after refactoring.

> **Note**: Database tests should mock the MySQL connection to test query building in isolation without requiring a real database.

---

### 2.5 JWT Library

**File**: `test/Libs/JWT/JWTTest.php`
**Source**: `src/Libs/JWT/JWT.php`

| Test | What It Verifies |
|------|-----------------|
| `testEncodeAndDecode` | Round-trip: encode payload → decode → same payload |
| `testDecodeWithInvalidSignature` | Throws `SignatureInvalidException` |
| `testDecodeExpiredToken` | Throws `ExpiredException` |
| `testDecodeBeforeValid` | Throws `BeforeValidException` (nbf claim) |
| `testDecodeWithLeeway` | Respects `$leeway` for clock skew |
| `testEncodeWithDifferentAlgorithms` | HS256, HS384, HS512 all work |
| `testDecodeWithWrongAlgorithm` | Rejects token signed with disallowed algorithm |
| `testUrlsafeB64EncodeDecode` | Round-trip base64 encoding |
| `testJsonEncodeAndDecode` | JSON encode/decode with large integers |
| `testSignAndVerify` | Low-level sign/verify |
| `testDecodeWithCustomTimestamp` | Uses `JWT::$timestamp` override for testing |

**Example test**:
```php
public function testEncodeAndDecode()
{
    $key = 'secret-key';
    $payload = ['sub' => '1234', 'name' => 'John', 'iat' => time()];

    $token = JWT::encode($payload, $key);
    $decoded = JWT::decode($token, $key, ['HS256']);

    $this->assertEquals('1234', $decoded->sub);
    $this->assertEquals('John', $decoded->name);
}
```

---

### 2.6 CSV File Handler

**File**: `test/File/CSVTest.php`
**Source**: `src/File/CSV.php`

| Test | What It Verifies |
|------|-----------------|
| `testReadCsv` | Parse a CSV file into structured data |
| `testToArray` | CSV to flat array conversion |
| `testSetHeader` | Custom header assignment |
| `testReadWithStandardHeader` | Map CSV columns to custom header names |
| `testSkipEmptyRows` | Empty row filtering (skipEmpty=true) |
| `testKeepEmptyRows` | Keep empty rows (skipEmpty=false) |
| `testNullableValues` | Null replacement for empty values |
| `testCheckEmpty` | Detect empty data |
| `testSaveCsv` | Write data to CSV file |
| `testReadInvalidFileThrows` | Throws CsvException for invalid files |

**Test setup**: Create temporary CSV files in `setUp()`, clean up in `tearDown()`.

---

### 2.7 HTTP Components

#### Request (`test/Http/RequestTest.php`)
**Source**: `src/Http/Request.php`

| Test | What It Verifies |
|------|-----------------|
| `testAll` | Returns all request data |
| `testHeaders` | Extracts request headers |
| `testGetRawData` | Parses JSON/form body data |
| `testMagicGetSet` | `__get` / `__set` work correctly |
| `testArrayAccess` | offsetGet/Set/Exists/Unset work |
| `testIsset` | `__isset` returns correct boolean |

#### Response (`test/Http/ResponseTest.php`)
**Source**: `src/Http/Response.php`

| Test | What It Verifies |
|------|-----------------|
| `testToJson` | Converts data to JSON response |
| `testToJsonWithCode` | Sets HTTP response code |
| `testResponseCode` | Sets and returns response code |

#### Globals (`test/Http/GlobalsTest.php`)
**Source**: `src/Http/Globals.php`

| Test | What It Verifies |
|------|-----------------|
| `testPath` | Returns current route path |
| `testUri` | Returns request URI |
| `testMethod` | Returns HTTP method |
| `testServer` | Returns $_SERVER values |
| `testGetAndPost` | Returns GET/POST data |

---

### 2.8 URL & Signature Verification

**File**: `test/Http/UrlTest.php`
**Source**: `src/Url.php`

| Test | What It Verifies |
|------|-----------------|
| `testGenerateSignature` | Produces consistent HMAC signature |
| `testHasCorrectSignature` | Validates matching signature |
| `testHasIncorrectSignature` | Rejects tampered signature |
| `testIsExpiredSignature` | Detects expired timestamp |
| `testIsNotExpiredSignature` | Accepts valid timestamp |
| `testSignedUrl` | Generates URL with signature param |
| `testTemporarySignedUrl` | Generates URL with expiration |

**PHP 8 relevance**: `hasCorrectSignature()` uses `==` (loose comparison) — test ensures behavior is preserved when changed to `hash_equals()`.

---

### 2.9 Model

**File**: `test/Models/ModelTest.php`
**Source**: `src/Models/Model.php`

| Test | What It Verifies |
|------|-----------------|
| `testGetTable` | Returns table name from class convention |
| `testToArray` | Converts model attributes to array |
| `testSetFillable` | Restricts mass-assignment to fillable fields |
| `testMagicGetSet` | Attribute access via `__get` / `__set` |
| `testSave` | Insert/update logic (mocked DB) |
| `testFind` | Find by ID (mocked DB) |
| `testCreate` | Create new record (mocked DB) |
| `testDestroy` | Delete record(s) (mocked DB) |

> **Note**: Create a concrete test model class extending `Model` with mocked database connection.

---

### 2.10 HasAttributes Trait

**File**: `test/Traits/HasAttributesTest.php`
**Source**: `src/Traits/HasAttributes.php`

| Test | What It Verifies |
|------|-----------------|
| `testMagicGet` | `__get` returns attribute value |
| `testMagicSet` | `__set` stores attribute |
| `testMagicIsset` | `__isset` checks attribute existence |
| `testMagicUnset` | `__unset` removes attribute |
| `testOffsetGet` | ArrayAccess get |
| `testOffsetSet` | ArrayAccess set |
| `testOffsetExists` | ArrayAccess exists |
| `testOffsetUnset` | ArrayAccess unset |
| `testMapAttributes` | Maps array to attributes |
| `testSetAndGetAttributes` | Bulk set/get attributes |

**PHP 8 relevance**: `__get` returns by reference (`&__get`) — stricter in PHP 8.

---

### 2.11 Log

**File**: `test/File/LogTest.php`
**Source**: `src/File/Log.php`

| Test | What It Verifies |
|------|-----------------|
| `testLogError` | Writes ERROR level log entry |
| `testLogInfo` | Writes INFO level log entry |
| `testLogDebug` | Writes DEBUG level log entry |
| `testIsUse` | Checks if logging is enabled via config |
| `testLogFile` | Returns correct log file path |

**Test setup**: Use temporary log directory, clean up after each test.

---

## Phase 3 — Verify Baseline

```bash
# Run full test suite
./vendor/bin/phpunit

# Run with coverage report (optional, requires Xdebug or PCOV)
./vendor/bin/phpunit --coverage-text
```

**Expected result**: All tests pass on current PHP 8.3 with PHPUnit 9.5.

This establishes the **baseline** — any test failure after PHP 8.2 migration changes indicates a regression.

---

## Phase 4 — PHP 8.2 Upgrade

With all tests passing, proceed with the upgrade. **Run tests after each step**.

### Step 4.1: Update Composer

```diff
  "require": {
-     "php": "^7.0"
+     "php": "^8.2"
  },
  "require-dev": {
-     "phpunit/phpunit": "^9.5"
+     "phpunit/phpunit": "^10.0"
  }
```

### Step 4.2: Migrate Tests to PHPUnit 10

| Change | Before (PHPUnit 9) | After (PHPUnit 10) |
|--------|--------------------|--------------------|
| setUp | `protected function setUp()` | `protected function setUp(): void` |
| tearDown | `protected function tearDown()` | `protected function tearDown(): void` |
| Annotations | `@test` | `#[Test]` |
| Data providers | `@dataProvider name` | `#[DataProvider('name')]` |
| String contains | `assertContains($n, $str)` | `assertStringContainsString($n, $str)` |
| Regex | `assertRegExp($p, $str)` | `assertMatchesRegularExpression($p, $str)` |

```bash
./vendor/bin/phpunit  # ✅ Tests pass
```

### Step 4.3: Fix `ReflectionParameter::getClass()`

**File**: `src/Container/Container.php`

```php
// Replace getClass() with getType()
$type = $parameter->getType();
if ($type === null || $type->isBuiltin()) {
    // handle non-class dependency
} else {
    $dependency = new ReflectionClass($type->getName());
}
```

```bash
./vendor/bin/phpunit --filter ContainerTest  # ✅ Tests pass
```

### Step 4.4: Fix Loose Comparisons

**File**: `src/Helpers/helpers.php`
- `strpos()` truthy → `str_contains()`
- `substr() ==` → `str_starts_with()`

**File**: `src/Url.php`
- `$signature == ...` → `hash_equals()`

```bash
./vendor/bin/phpunit --filter "HelpersTest|UrlTest"  # ✅ Tests pass
```

### Step 4.5: Refactor `func_get_args()` → Typed Parameters

**Files**: `src/Validation/Validator.php`, `src/Db/Database.php`

```php
// Before
public function ruleRequired() {
    list($value, $attribute, $message, $params) = func_get_args();
}

// After
public function ruleRequired(mixed $value, string $attribute, string $message, mixed $params = null): bool {
}
```

```bash
./vendor/bin/phpunit --filter "ValidatorTest|DatabaseTest"  # ✅ Tests pass
```

### Step 4.6: Adopt PHP 8 Features

- `str_contains()`, `str_starts_with()`, `str_ends_with()`
- Constructor promotion
- `readonly` properties
- `match` expressions
- Nullsafe operator `?->`
- Union types and return types

```bash
./vendor/bin/phpunit  # ✅ All tests pass
```

---

## File Structure

```
atom/
├── composer.json          # Updated dependencies
├── phpunit.xml            # New PHPUnit configuration
├── src/                   # Source (unchanged in Phase 1-3)
├── docs/
│   ├── php8-modernization-plan.md
│   └── unit-tests-and-php8-upgrade-plan.md  # This file
└── test/
    ├── TestCase.php                  # Base test class
    ├── Helpers/
    │   └── HelpersTest.php           # 16 tests
    ├── Validation/
    │   └── ValidatorTest.php         # ~24 tests
    ├── Container/
    │   └── ContainerTest.php         # 8 tests
    ├── Db/
    │   └── DatabaseTest.php          # ~20 tests
    ├── Libs/
    │   └── JWT/
    │       └── JWTTest.php           # 11 tests
    ├── File/
    │   ├── CSVTest.php               # 10 tests
    │   └── LogTest.php               # 5 tests
    ├── Http/
    │   ├── RequestTest.php           # 6 tests
    │   ├── ResponseTest.php          # 3 tests
    │   ├── GlobalsTest.php           # 5 tests
    │   └── UrlTest.php               # 7 tests
    ├── Models/
    │   └── ModelTest.php             # 8 tests
    └── Traits/
        └── HasAttributesTest.php     # 10 tests
```

**Total: ~133 unit tests across 14 test files**

---

## Verification Checklist

### After Phase 2 (Tests Created)
- [ ] `composer install` succeeds
- [ ] `./vendor/bin/phpunit` — all 133+ tests pass
- [ ] No tests depend on external services (DB, network, filesystem outside /tmp)

### After Phase 4 (PHP 8.2 Upgrade)
- [ ] `composer.json` requires `php: ^8.2`
- [ ] `composer install` succeeds with new constraints
- [ ] `./vendor/bin/phpunit` — all tests still pass
- [ ] No PHP deprecation warnings in test output
- [ ] `ReflectionParameter::getClass()` replaced everywhere
- [ ] All `func_get_args()` replaced with typed parameters
- [ ] No loose `==` comparisons with `strpos()` results
- [ ] `hash_equals()` used for signature verification
