# AGENTS.md — Coding Agent Guide for govcms-viostream-plugin

Drupal module providing Viostream video integration (field formatter, field widget, text filter, CKEditor 5 plugin, API client service, admin settings form, media browser controller). Targets Drupal 10 and 11 only (`^10 || ^11`). PHP `>=8.1`.

## Build & Test Commands

No local PHP/Composer — all commands run via Docker.

```bash
# Run the full test suite (145 tests):
docker run --rm -v "$(pwd)":/app -w /app php:8.3-cli php vendor/bin/phpunit 2>&1

# Run a single test file:
docker run --rm -v "$(pwd)":/app -w /app php:8.3-cli php vendor/bin/phpunit tests/src/Unit/ViostreamClientTest.php 2>&1

# Run a single test method:
docker run --rm -v "$(pwd)":/app -w /app php:8.3-cli php vendor/bin/phpunit --filter testMethodName 2>&1

# Run with coverage (installs pcov each time; container is ephemeral):
docker run --rm -v "$(pwd)":/app -w /app php:8.3-cli sh -c \
  "pecl install pcov && php -dextension=pcov.so -dpcov.enabled=1 vendor/bin/phpunit --coverage-text 2>&1"

# Install dependencies (requires --ignore-platform-reqs because gd is missing):
docker run --rm -v "$(pwd)":/app -w /app composer:2 install --ignore-platform-reqs
```

No linting, static analysis, or CI pipeline is configured. No `composer test` script exists.

## Project Layout

```
src/
  Client/ViostreamClient.php            # API client service (Guzzle + Basic auth)
  Controller/ViostreamMediaBrowserController.php
  Form/ViostreamSettingsForm.php        # ConfigFormBase
  Plugin/
    CKEditor5Plugin/ViostreamVideo.php
    Field/FieldFormatter/ViostreamFormatter.php
    Field/FieldWidget/ViostreamBrowserWidget.php
    Filter/ViostreamVideoFilter.php
tests/
  bootstrap.php                         # PSR-4 namespaces + Drupal container stubs
  src/Unit/                             # All test files (one per source class)
js/                                     # Browser JS (ES5) + CKEditor 5 plugin (built)
```

## Code Style

### PHP — Drupal Coding Standards

Follow Drupal coding standards. Key conventions observed in this codebase:

- **Boolean/null constants:** Always uppercase: `TRUE`, `FALSE`, `NULL`.
- **Indentation:** 2 spaces (PHP, YAML, JS). No tabs.
- **Braces:** Opening brace on same line for functions/methods, new line for classes.
- **Line length:** Soft limit ~80 chars. No hard enforcement.
- **String quotes:** Single quotes unless interpolation is needed.

### Naming Conventions

- **Classes:** PascalCase, prefixed with `Viostream` (e.g., `ViostreamClient`).
- **Methods:** camelCase (e.g., `getMediaDetail`, `extractVideoId`).
- **Properties:** camelCase (e.g., `$httpClient`, `$viostreamClient`).
- **Constructor parameters:** snake_case per Drupal convention (e.g., `$http_client`, `$config_factory`).
- **Local variables:** snake_case (e.g., `$video_id`, `$access_key`, `$embed_url`).
- **Constants:** UPPER_SNAKE_CASE (e.g., `API_BASE_URL`).
- **Config keys:** snake_case (e.g., `'access_key'`).

### Imports (use statements)

Single block, no blank lines between groups. Order: Drupal core, then module classes, then third-party (Guzzle, Symfony, Psr). Alphabetical within each group.

### Type Declarations

- **Properties:** Declared without native types; use `@var` docblock annotation. Always `protected` visibility.
- **Return types:** Generally omitted from method signatures (Drupal 10 convention). Exceptions for interface-required signatures.
- **Parameters:** Typed when practical (`array`, `string`, interface types). Nullable via default `= NULL`, not `?Type`.
- **No constructor property promotion.** Assign explicitly in the constructor body.

### PHPDoc

Every class, property, and non-trivial method must have a docblock.

- **Class:** Short description. Optional longer paragraph.
- **Property:** Description line + blank line + `@var \Full\Qualified\ClassName`.
- **Method:** Description + blank line + `@param`/`@return` with FQN types and 2-space-indented descriptions.
- **Overrides:** Use `{@inheritdoc}` only, no extra description.
- **Plugin annotations:** Standard Drupal `@FieldFormatter`, `@FieldWidget`, `@Filter` annotations with `@Translation()` for labels.

### Error Handling

- The API client returns `NULL` on failure, never throws exceptions to callers.
- Guzzle exceptions are caught internally and logged via Drupal's logger (`$this->logger->error(...)`).
- Log messages use Drupal placeholders: `'@status'`, `'@endpoint'`, `'@message'`.
- Controllers return `JsonResponse` with HTTP status codes (403, 404, 500) on error.

### Dependency Injection

- Constructor injection everywhere. No service locator calls except `\Drupal::httpClient()` in the AJAX callback.
- Plugins implement `ContainerFactoryPluginInterface::create()`.
- Controllers/forms use `ContainerInjectionInterface::create()`.
- Services defined in `viostream.services.yml`.

### JavaScript (js/)

- ES5 only: `var`, named `function` expressions, `+` concatenation. No arrow functions or template literals.
- Drupal behavior pattern: `Drupal.behaviors.name = { attach: function(context) { ... } }`.
- IIFE wrapper: `(function (Drupal, drupalSettings) { 'use strict'; ... })(Drupal, drupalSettings);`.
- Initialization guard via `dataset` attributes to prevent double-binding.
- XSS: use `escapeHtml()`/`escapeAttr()` helpers, never raw `innerHTML` with user data.

## Testing Conventions

### Structure

- Pure PHPUnit unit tests — extend `PHPUnit\Framework\TestCase`, NOT Drupal's `UnitTestCase`.
- Test namespace: `Drupal\Tests\viostream\Unit`.
- One test class per source class, named `{ClassName}Test.php`.
- Class annotations: `@coversDefaultClass` + `@group viostream`.
- Method annotations: `@covers ::methodName` on every test.
- All test methods return `void` with explicit return type.

### setUp Pattern

```php
protected function setUp(): void {
  parent::setUp();
  $this->dependency = $this->createMock(InterfaceClass::class);
  $this->sut = new ClassUnderTest($this->dependency);

  // For classes using $this->t():
  $translation = $this->createMock(TranslationInterface::class);
  $translation->method('translateString')
    ->willReturnCallback(fn(TranslatableMarkup $m) => $m->getUntranslatedString());
  $this->sut->setStringTranslation($translation);
}
```

### Mocking

- `$this->createMock()` for dependencies with expectations; `$this->createStub()` for simple return values.
- Config mocks: use `willReturnMap([['key', $value], ...])`.
- Field item mocks: configure `__get` via `willReturnMap`, NOT property assignment (mock intercepts `__set`).
- FieldItemListInterface mocks: configure Iterator methods (`rewind`, `valid`, `current`, `key`, `next`) and `offsetExists(TRUE)`.
- Container services: swap via `\Drupal::getContainer()->set('service_name', $mock)`.

### Assertions

Prefer `assertSame()` (strict) over `assertEquals()`. Use `assertStringContainsString()`, `assertArrayHasKey()`, `assertCount()`, `assertNull()`, `assertInstanceOf()` as appropriate.

### Protected Methods

Test via reflection helper:
```php
protected function callProtected(string $method, ...$args): mixed {
  $ref = new \ReflectionMethod($this->sut, $method);
  $ref->setAccessible(TRUE);
  return $ref->invoke($this->sut, ...$args);
}
```

### Data Providers

`public static` methods returning associative arrays with descriptive string keys for test case names.

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/) format:

```
<type>: <short description>

[optional body]
```

### Types

- **feat:** A new feature or user-facing functionality.
- **fix:** A bug fix.
- **test:** Adding or updating tests (no production code change).
- **docs:** Documentation only changes.
- **refactor:** Code change that neither fixes a bug nor adds a feature.
- **style:** Formatting, whitespace, coding standards (no logic change).
- **perf:** Performance improvement.
- **chore:** Maintenance tasks, dependency updates, tooling changes.
- **ci:** CI/CD pipeline changes.
- **build:** Build system or external dependency changes.

### Rules

- Use lowercase for the type and description.
- Keep the subject line under 72 characters.
- Use imperative mood in the description (e.g., "add" not "added" or "adds").
- Do not end the subject line with a period.
- Separate subject from body with a blank line if a body is needed.
- Reference issue numbers in the body when applicable (e.g., `Closes #42`).

## Important Notes

- All `share.viostream.com` URLs replace the deprecated `play.viostream.com`.
- LSP "Undefined type" errors for Drupal core classes are expected — the module is developed standalone without Drupal core in the workspace. They are not real errors.
- PHPUnit deprecation warnings about intersection types in mock declarations are cosmetic (PHPUnit 11.x).
- The `ViostreamSettingsForm` constructor does NOT call `parent::__construct()` — the `configFactory` property must be injected via reflection in tests.
- Coverage target: 90%+ (currently 99.05%).
