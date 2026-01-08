# Contributing to xAI PHP SDK

Thank you for your interest in contributing to the xAI PHP SDK! This guide outlines the process for contributing code, fixing bugs, or improving documentation.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Scope of Contributions](#scope-of-contributions)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Testing Requirements](#testing-requirements)
- [Pull Request Process](#pull-request-process)
- [Commit Guidelines](#commit-guidelines)

## Code of Conduct

By participating in this project, you agree to maintain a respectful and inclusive environment. Be kind, constructive, and professional in all interactions.

## Scope of Contributions

You can contribute in the following ways:

- **Bug Fixes**: Fix issues in existing functionality
- **New Features**: Add new capabilities that align with the Python SDK parity goal
- **Documentation**: Improve README, examples, or inline documentation
- **Tests**: Add or improve test coverage
- **Performance**: Optimize existing code

**Note**: For larger changes or new features, please open an issue first to discuss the approach with maintainers. This ensures alignment before you invest significant time.

Version bumps and releases are managed by project maintainers.

## Development Setup

### Prerequisites

- PHP 8.4 or higher
- Composer 2.x
- Git

### Installation

1. **Fork and Clone**

   ```bash
   # Fork the repository on GitHub, then clone your fork
   git clone https://github.com/YOUR_USERNAME/xai-sdk-php.git
   cd xai-sdk-php
   ```

2. **Install Dependencies**

   ```bash
   composer install
   ```

3. **Verify Setup**

   ```bash
   # Run all quality checks
   composer qa
   ```

### Directory Structure

```
xai-sdk-php/
├── src/                    # Source code
│   ├── Chat/               # Chat-related classes
│   ├── Config/             # Configuration classes
│   ├── Exceptions/         # Exception hierarchy
│   ├── Http/               # HTTP client wrapper
│   ├── Resources/          # API resource classes
│   ├── Responses/          # Response DTOs
│   ├── Streaming/          # SSE streaming support
│   ├── Telemetry/          # OpenTelemetry integration
│   ├── Tools/              # Tool/function calling
│   └── XaiClient.php       # Main client entry point
├── tests/
│   ├── Unit/               # Unit tests
│   ├── Integration/        # Integration tests
│   └── Fixtures/           # Test fixtures (JSON files)
├── examples/               # Example scripts
└── ...
```

## Coding Standards

### PHP Version

- Target PHP 8.4+ features
- Use strict types in all files: `declare(strict_types=1);`
- Use readonly properties where appropriate
- Use constructor property promotion

### Style Guide (PSR-12)

We follow PSR-12 coding standards with additional rules enforced by PHP CS Fixer.

```bash
# Check code style
composer cs:check

# Automatically fix style issues
composer cs:fix
```

**Key Rules:**

- Use 4 spaces for indentation (no tabs)
- Opening braces on the same line for classes and methods
- One class per file
- Namespace declarations followed by a blank line
- Use statements grouped and sorted alphabetically
- No trailing whitespace
- Files end with a single newline

### Static Analysis (PHPStan Level 8)

All code must pass PHPStan analysis at level 8 (strictest).

```bash
composer stan
```

**Requirements:**

- All parameters and return types must be declared
- No mixed types without explicit documentation
- No ignored errors without justification
- Proper null handling with strict null checks

### Documentation

- All public classes and methods must have PHPDoc blocks
- Include `@param`, `@return`, and `@throws` tags
- Add `@example` blocks for complex functionality
- Keep descriptions concise but informative

**Example:**

```php
/**
 * Creates a new chat session with the specified configuration.
 *
 * @param string $model The model identifier (e.g., 'grok-3').
 * @param array<Message> $messages Initial messages for the conversation.
 * @param float $temperature Sampling temperature (0.0-2.0).
 *
 * @return Chat The configured chat session.
 *
 * @throws InvalidArgumentException If the model is not supported.
 *
 * @example
 * ```php
 * $chat = $client->chat->create(
 *     model: 'grok-3',
 *     messages: [system('You are helpful.')],
 * );
 * ```
 */
public function create(
    string $model,
    array $messages = [],
    float $temperature = 1.0,
): Chat {
    // ...
}
```

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `ChatResource`, `XaiClient` |
| Methods | camelCase | `createChat()`, `getResponse()` |
| Properties | camelCase | `$apiKey`, `$httpClient` |
| Constants | UPPER_SNAKE | `MAX_TOKENS`, `DEFAULT_MODEL` |
| Files | PascalCase | `ChatResource.php` |

### Type Safety

- Always declare parameter types and return types
- Use union types sparingly and only when necessary
- Prefer specific types over `mixed`
- Use `array<Type>` syntax in PHPDoc for typed arrays

```php
// Good
public function process(string $input): ChatResponse

// Good - when null is valid
public function find(string $id): ?Model

// Avoid
public function process($input)  // Missing types
```

## Testing Requirements

### Test Coverage

- All new features must include tests
- Bug fixes should include regression tests
- Aim for >90% code coverage on new code
- Unit tests are required; integration tests are encouraged

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage report
composer test:coverage

# Run only unit tests
composer test:unit

# Run only integration tests
composer test:integration

# Run a specific test file
./vendor/bin/phpunit tests/Unit/Chat/ChatTest.php

# Run a specific test method
./vendor/bin/phpunit --filter testCreateChat
```

### Test Structure

```php
<?php

declare(strict_types=1);

namespace Displace\XaiSdk\Tests\Unit\Chat;

use Displace\XaiSdk\Tests\TestCase;
use Displace\XaiSdk\Chat\Chat;

final class ChatTest extends TestCase
{
    public function testCreateChatWithDefaults(): void
    {
        // Arrange
        $client = $this->createMockClient();

        // Act
        $chat = $client->chat->create(model: 'grok-3');

        // Assert
        $this->assertInstanceOf(Chat::class, $chat);
        $this->assertSame('grok-3', $chat->getModel());
    }

    public function testCreateChatWithInvalidModelThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid model');

        $client = $this->createMockClient();
        $client->chat->create(model: '');
    }
}
```

### Test Guidelines

1. **Descriptive Names**: Test method names should describe the scenario
   - `testCreateChatWithValidModel()`
   - `testStreamThrowsExceptionOnTimeout()`

2. **Arrange-Act-Assert**: Structure tests clearly
   ```php
   // Arrange - Set up test data and mocks
   // Act - Execute the code under test
   // Assert - Verify the results
   ```

3. **One Assertion Per Concept**: Group related assertions, but test one concept
4. **Use Fixtures**: Store JSON test data in `tests/Fixtures/`
5. **Mock External Dependencies**: Never make real API calls in unit tests

### Using Test Fixtures

```php
// Load a fixture file
$fixture = $this->loadFixture('chat_completion_response.json');

// Use in mock
$mockResponse = new Response(200, [], json_encode($fixture));
```

## Pull Request Process

### Step 1: Create a Branch

```bash
# Ensure you're on main and up to date
git checkout main
git pull upstream main

# Create a feature branch
git checkout -b feature/add-streaming-timeout

# Or for bug fixes
git checkout -b fix/handle-empty-response
```

### Step 2: Make Changes

1. Write your code following the coding standards
2. Add or update tests as needed
3. Update documentation if applicable
4. Run quality checks locally:

   ```bash
   composer qa
   ```

### Step 3: Commit Changes

Follow the [commit guidelines](#commit-guidelines) below.

```bash
git add .
git commit -m "feat: Add streaming timeout configuration"
```

### Step 4: Push and Create PR

```bash
git push origin feature/add-streaming-timeout
```

Then create a Pull Request on GitHub with:

- **Clear Title**: Summarize the change in one line
- **Description**: Explain what, why, and how
- **Issue Reference**: Link related issues with `Fixes #123`
- **Testing Notes**: Describe how to test the changes

### Step 5: Address Review Feedback

- Respond to all review comments
- Push additional commits to address feedback
- Re-request review when ready

### Step 6: Merge

Once approved and all checks pass, a maintainer will merge your PR.

## Commit Guidelines

We follow [Conventional Commits](https://www.conventionalcommits.org/) for clear history.

### Format

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Types

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation changes |
| `style` | Code style (formatting, no logic change) |
| `refactor` | Code refactoring |
| `perf` | Performance improvement |
| `test` | Adding or updating tests |
| `chore` | Maintenance tasks |

### Examples

```bash
# Feature
feat(chat): Add support for streaming timeout configuration

# Bug fix
fix(streaming): Handle empty SSE events gracefully

# Documentation
docs(readme): Add telemetry setup examples

# Tests
test(responses): Add unit tests for ChatResponse parsing

# Refactoring
refactor(http): Extract retry logic to separate class
```

### Commit Best Practices

- Keep commits atomic (one logical change per commit)
- Write in imperative mood ("Add feature" not "Added feature")
- Keep the subject line under 72 characters
- Reference issues in the body when applicable

## CI/CD Checks

All PRs must pass these automated checks:

| Check | Command | Description |
|-------|---------|-------------|
| Code Style | `composer cs:check` | PSR-12 compliance |
| Static Analysis | `composer stan` | PHPStan level 8 |
| Unit Tests | `composer test:unit` | All unit tests pass |
| PHP Versions | Matrix | Tests on PHP 8.4+ |

## Questions?

- Open an issue for questions or discussions
- Check existing issues before creating new ones
- Join discussions on open PRs for context

Thank you for contributing to the xAI PHP SDK!
