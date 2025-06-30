
# Testing Best Practices with PestPHP

## Developer Task: Modernizing Our Testing Practices with PestPHP and Refactoring

This document outlines a strategic initiative to elevate our application's testing standards. It is divided into two parts. The first part establishes a set of forward-looking principles for testing with PestPHP, forming the foundation of our quality assurance strategy. The second part applies these principles directly, providing a comprehensive and tactical guide to refactor a critical test file: CustomFieldsPageTest This task is crucial for ensuring our test suite is readable, maintainable, comprehensive, and efficient, setting the standard for all future testing endeavors.

Part 1: Foundational Principles - PestPHP Best Practices for 2025

This section establishes the "why" behind our testing strategy. It moves beyond simple syntax to explore the philosophy and advanced techniques that define a truly modern and effective test suite.

### The Philosophy of Modern PHP Testing: Readability as a Feature

A test's primary audience is not the machine, but the next developer who reads it. Our goal is to create tests that are so expressive and clear they serve as living documentation for the application's behavior. The adoption of Pest is not merely a syntactic preference over PHPUnit; it represents a philosophical shift toward prioritizing the developer experience (DX).1 The framework's elegant syntax and focus on readability are intentionally designed to reduce cognitive load and make testing a more intuitive and enjoyable part of the development lifecycle. This approach directly combats the tendency for tests to become a maintenance burden, thereby organically increasing both test coverage and quality.
A positive developer experience has a direct and tangible economic impact on a project. When the friction to write tests is low, developers are more inclined to write them consistently and thoroughly. This leads to earlier bug detection, and a bug identified during development is orders of magnitude less costly to remediate than one discovered in production.3 Furthermore, a test suite that is easy to read and understand significantly reduces the onboarding time for new team members and makes subsequent debugging and refactoring efforts safer and more efficient.4 Therefore, standardizing on a framework and a set of practices that prioritize DX is a direct investment in development velocity, code quality, and long-term project maintainability.
To this end, the following mandates for expressiveness are to be considered standard practice:
Descriptive Naming: All tests must use clear, descriptive names within the it() or test() function. The name should describe the behavior being tested in a complete sentence, for example: it('validates that the title field is required when creating a new record').
Fluent Expectation API: Pest's expectation API (expect(...)->...) must be used for all assertions. Its fluent, natural-language-like structure is inherently more readable than traditional assertion methods.1
The Arrange-Act-Assert (AAA) Pattern: Every test case must be structured with the AAA pattern. This provides a consistent and predictable structure, making tests easier to read and debug. Comments (// Arrange, // Act, // Assert) may be used to delineate these sections in more complex tests.3

### Architectural Integrity: Proactive Quality Assurance with Pest Arch

Our testing strategy must shift from a reactive (bug-fixing) to a proactive (error-prevention) model. Architecture tests are the primary tool for this transformation, acting as an automated architectural review process that executes alongside the rest of the test suite. The tests/Architecture.php file is a critical component of the application's quality assurance system. It is not optional. Its purpose is to codify our architectural rules, ensuring the entire codebase adheres to established standards, maintains consistency, and prevents architectural drift or decay over time.6
By integrating these architectural tests into our Continuous Integration (CI) pipeline, we establish an automated governance layer. This systematically offloads the responsibility of enforcing structural and stylistic rules from human code reviewers to the machine. This automation is more consistent and reliable than manual checks and allows human reviewers to focus their efforts on higher-level concerns, such as the correctness of business logic, algorithmic efficiency, and the quality of the user experience. This makes the entire development process more efficient and the resulting codebase more robust, scalable, and maintainable.
The following architectural rules represent a baseline for our application and must be enforced within tests/Architecture.php:
Dependency Constraints: To enforce clean architecture principles, direct dependencies between certain layers are forbidden. For example, controllers must not directly use Eloquent models; they should interact with a service or repository layer.
```php
arch('Controllers do not use Models')->expect('App\Models')->not->toBeUsedIn('App\Http\Controllers');
```
Strict Typing and Inheritance: All major application components must extend their correct base classes. This ensures they inherit necessary functionality and conform to the framework's expectations.
```php
arch('Models extend Eloquent Model')->expect('App\Models')->toExtend(Illuminate\Database\Eloquent\Model::class);
arch('Filament Resources extend base Resource')->expect('App\Filament\Resources')->toExtend(Filament\Resources\Resource::class);
```
Naming Conventions: Naming conventions must be enforced automatically to maintain consistency across the codebase.
```php
arch('Services have Service suffix')->expect('App\Services')->toHaveSuffix('Service');
```
Prohibition of Debugging Code: Debugging functions (dd, dump, ray, var_dump) are strictly forbidden from being committed to the main branch. This is a critical safety net to prevent exposure of sensitive information or broken execution flows in production.
```php
arch('No debugging functions are used')->expect(['dd', 'dump', 'ray', 'var_dump'])->not->toBeUsed();
```
Immutability of Data Objects: All Data Transfer Objects (DTOs) must be immutable to ensure predictable data flow.
```php
arch('DTOs are readonly')->expect('App\DataTransferObjects')->toBeReadonly();
```

### Advanced Test Structuring and Organization

A flat, disorganized test suite quickly becomes a maintenance liability. The test suite must be structured logically using Pest's organizational tools to promote clarity and adhere to the Don't Repeat Yourself (DRY) principle.
Datasets for Repetitive Logic: For testing multiple variations of the same functionality, such as a set of validation rules for a single field, datasets are mandatory. They allow a single test closure to be executed with multiple different inputs, which drastically reduces code duplication and improves the clarity of both the test code and its output.1
Grouping with describe(): For complex features or components with multiple facets to test, related tests must be organized into logical blocks using describe() blocks. This improves the file's structure and makes the test runner's output more readable by grouping related results.8
Higher-Order Tests for Fluent Assertions: When testing a single object or component through a series of state changes and assertions, higher-order tests can provide a more fluent and readable test flow. Their use is encouraged where they improve clarity.
Strategic Use of Hooks: The beforeEach() and afterEach() hooks should be used to handle common setup and teardown logic within a test file. For example, creating a specific user or model instance required by all tests in the file should be done in a beforeEach() block to avoid repetition.9
Managing Work-in-Progress with todo(): The todo() method should be used to scaffold future tests or to mark existing tests that require implementation. This creates a self-documenting list of pending work within the test suite itself, which can be easily reviewed using the --todos CLI option.8 The
todo() function can also be leveraged for team management by assigning tasks and linking them to specific issues or pull requests in the project management system.8

### The Efficient Developer Workflow

The speed of the feedback loop between writing code and seeing test results is directly proportional to developer productivity. Practices and tools that provide the fastest possible test execution must be adopted.
Essential CLI Options: Developers should be proficient in using the following Pest CLI options to optimize their local development workflow:
--parallel: This option must be used for full-suite runs to dramatically reduce execution time by running tests across multiple processes.11
--dirty: When actively developing a feature, this option should be used to run only the tests related to uncommitted Git changes, providing a near-instant feedback loop.10
--retry: After a test failure, this option should be used to immediately re-run the failed tests first, allowing for quick verification of a fix without waiting for the entire suite.10
--filter: This option is used to isolate and run a single test or a specific group of tests by name, which is essential for focused debugging.10
--profile: This option should be used periodically to identify the slowest tests in the suite, allowing for targeted optimization efforts to maintain a fast overall test suite.11
Database State Management: The Illuminate\Foundation\Testing\RefreshDatabase trait is the mandated standard for all feature tests that interact with the database. This trait intelligently wraps each test in a database transaction, which is significantly faster and more efficient than re-running migrations for every test.13 The
DatabaseMigrations and DatabaseTruncation traits are slower and should only be used in exceptional, well-justified cases where transactions are not feasible (e.g., testing functionality that explicitly commits transactions).

### Future-Facing Concepts

Our testing practices must be adaptable and evolve with the ecosystem. The following emerging trends in PestPHP should be monitored for future adoption:
Type Coverage: The --type-coverage feature, which analyzes and can enforce type-hint coverage, is a powerful tool for improving code robustness and catching potential type-related errors. Its development should be monitored with the goal of eventually adopting a minimum type coverage threshold for our codebase.16
Domain-Driven Laravel Preset: The community idea for a Pest preset for Domain-Driven Design (DDD) suggests a future where Pest can automatically enforce complex architectural patterns. This aligns perfectly with our goal of maintaining architectural integrity and should be watched closely.16
Auto-retry for Flaky Tests: The suggestion of an auto-retry option for flaky tests highlights the community's focus on solving practical CI/CD challenges. Adopting such a feature could improve the stability and reliability of our CI pipeline.16

## Part 2: Practical Application - A Guide to Refactoring

This section provides the specific, actionable instructions for refactoring the target test file. It applies the principles from Part 1 to a real-world component.

### Task Overview and Objectives

You are tasked with a complete refactoring of the feature test located at tests/Feature
The primary objectives are:
Improve Readability and Maintainability: Implement the principles from Part 1, including descriptive naming, the AAA pattern, and fluent assertions.
Simplify Structure: Eliminate redundant code by leveraging Pest datasets for validation and beforeEach hooks for common setup.
Ensure Comprehensive Coverage: The final test must validate all aspects of the CreateRecord page's functionality: rendering, authorization, successful creation, data validation, and any custom business logic or lifecycle hooks.
Modernize the Tooling: The refactoring must exclusively use the latest testing helpers and conventions provided by Laravel 12, PestPHP, Livewire 3, and Filament 4.

### Core Setup and Environment (beforeEach block)

Proper setup is foundational to writing clean, reliable, and isolated tests.
Global Configuration (tests/Pest.php): First, ensure that the project's tests/Pest.php file is correctly configured to apply the base TestCase and the RefreshDatabase trait to all tests within the Feature directory. This centralizes configuration and keeps individual test files uncluttered.17
```php
// In tests/Pest.php
uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
)->in('Feature');
```


Authentication and Data Generation: Within the  file, a beforeEach() hook must be used to prepare the necessary state for the tests. This involves creating a user who will be authenticated for the tests. All test data must be generated using model factories (e.g., Post::factory()->make()) to decouple tests from hardcoded data and make them resilient to schema changes.13 Do not use database seeders unless absolutely necessary for globally available, static, reference-like data.13
```php
// In tests/Feature/Panels/Resources/Pages/
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(); // Assuming this user has permissions
    actingAs($this->user);
});
```



### Refactoring Strategy: A Step-by-Step Guide

The key to effective testing within the TALL stack is to use the highest-level abstraction available for any given task. Filament provides specific, expressive helpers for testing its components; these should always be preferred over lower-level Livewire or Laravel helpers. This approach leads to more robust and readable tests that are less brittle to underlying implementation changes.
The following table provides a quick-reference guide for selecting the appropriate testing helper. It illustrates the hierarchy of abstractions, from the most specific (Filament) to the most general (Laravel).

| Task/Goal              | Primary Tool (Filament 4 Helper)                   | Secondary Tool (Livewire 3 Helper) | Foundational Tool (Laravel/Pest Helper) |
|------------------------|----------------------------------------------------|------------------------------------|-----------------------------------------|
| Render a Page          | `livewire(CreateRecord::class)`                    | `livewire(Component::class)`       | `$this->get(route(...))`                |
| Authenticate a User    | N/A                                                | `Livewire::actingAs($user)`        | `actingAs($user)`                       |
| Fill Form Fields       | `->fillForm([...])`                                | `->set('property', 'value')`       | N/A                                     |
| Submit a Form/Action   | `->call('create')` or `->callAction('actionName')` | `->call('methodName')`             | `$this->post(...)`                      |
| Check Form Validation  | `->assertHasFormErrors([...])`                     | `->assertHasErrors([...])`         | `->assertSessionHasErrors()`            |
| Check Field Existence  | `->assertFormFieldExists('name')`                  | N/A                                | N/A                                     |
| Check Field Visibility | `->assertFormFieldIsVisible('name')`               | `->assertSee('Field Label')`       | `->assertSee(...)`                      |
| Check Field State      | `->assertFormFieldIsDisabled('name')`              | N/A                                | N/A                                     |
| Assert DB State        | N/A                                                | N/A                                | `assertDatabaseHas(...)`                |
| Assert Redirect        | `->assertRedirect(...)`                            | `->assertRedirect(...)`            | `->assertRedirect(...)`                 |
| Assert Notification    | `->assertNotified()`                               | `->assertDispatched('notify')`     | N/A                                     |


### Foundational Tests: Rendering and Authorization

The first tests should confirm the page's basic accessibility.
Test Case 1: Page Renders Successfully for Authorized User
Arrange: The beforeEach hook handles user creation and authentication.
Act: Mount the Livewire component for the CreateRecord page.
Assert: Confirm that the page loads successfully and that the Filament form component is present on the page.9
```php
it('can render the create page', function () {
    livewire(PostResource\Pages\CreatePost::class)
        ->assertSuccessful()
        ->assertFormExists();
});
```


Test Case 2: Page is Forbidden for Unauthorized User
Arrange: Create and authenticate as a user who lacks the necessary permissions to create the resource.
Act: Make a direct GET request to the resource's create URL.
Assert: Verify that the application returns a 403 Forbidden status code.19
```php
it('is forbidden for users without permission', function () {
    // Arrange
    $unauthorizedUser = User::factory()->create(); // Assuming this user lacks permissions

    // Act & Assert
    actingAs($unauthorizedUser)
        ->get(PostResource::getUrl('create'))
        ->assertForbidden();
});
```



### The "Happy Path": Successful Record Creation

This test case validates the entire successful workflow in a single, fluent chain.
Test Case 3: Can Create a Record with Valid Data
Arrange: Authenticate as an authorized user (handled by beforeEach). Use the resource's model factory to generate a valid set of data.
Act: Use Filament's fillForm helper to populate the form fields and Livewire's call method to trigger the create action.20
Assert: Chain assertions to verify the complete, successful outcome: no validation errors were returned, a success notification was dispatched to the user, and the user was correctly redirected to the appropriate page (typically the edit or view page).9
Final Database Assertion: After the Livewire test interaction, perform a final, crucial assertion directly against the database to confirm that the record was persisted correctly.13
```php
use App\Filament\Resources\PostResource;
use App\Models\Post;
use function Pest\Livewire\livewire;

it('can create a new record with valid data', function () {
    // Arrange
    $newData = Post::factory()->make();

    // Act
    $livewireTest = livewire(PostResource\Pages\CreatePost::class)
        ->fillForm($newData->toArray())
        ->call('create');

    // Assert
    $livewireTest->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect(PostResource::getUrl('edit', ['record' => Post::first()]));

    $this->assertDatabaseHas(Post::class, $newData->toArray());
    $this->assertDatabaseCount('posts', 1);
});
```



### Comprehensive Validation Testing with Datasets

Do not write a separate test method for each individual validation rule. This is inefficient and leads to significant code duplication. Instead, use Pest Datasets to test all validation rules for all form fields in a concise and exhaustive manner.
Test Case 4: Form Fields Validation
Create a single test method that accepts the field name, the invalid value, and the expected validation rule as arguments from a dataset.
The dataset will contain entries for every validation scenario: required fields being null, string length violations, incorrect formats, non-unique values, etc.
```php
it('validates form fields', function (string $field, mixed $value, string|array $rule) {
    livewire(PostResource\Pages\CreatePost::class)
        ->fillForm([$field => $value])
        ->call('create')
        ->assertHasFormErrors([$field => $rule]);
})->with([
    'title is required' => ['title', null, 'required'],
    'title must be a string' => ['title', 12345, 'string'],
    'title must be at least 3 characters' => ['title', 'ab', 'min:3'],
    'slug must be unique' => ['slug', fn() => Post::factory()->create()->slug, 'unique'],
'content is required' => ['content', '', 'required']);
This pattern should be repeated to cover every validation rule for every field in the form.

### Testing Dynamic Form Schemas and Custom Logic

Modern forms often contain dynamic behavior. These conditional logic paths must be tested explicitly.
Test Case 5: Conditional Field Visibility/State
If a field's visibility or disabled state depends on the value of another field, this behavior must be asserted.
Use Filament's dedicated helpers (assertFormFieldIsVisible, assertFormFieldIsHidden, assertFormFieldIsEnabled, assertFormFieldIsDisabled) which are more robust than asserting the presence of text or HTML.18
```php
it('hides the reason field unless status is cancelled', function () {
    livewire(PostResource\Pages\CreatePost::class)
        ->fillForm(['status' => 'published'])
        ->assertFormFieldIsHidden('cancellation_reason')
        ->fillForm(['status' => 'cancelled'])
        ->assertFormFieldIsVisible('cancellation_reason');
});
```


Test Case 6: Custom Lifecycle Hooks
If the CreateRecord page class implements custom logic within its lifecycle hooks (e.g., mutateFormDataBeforeCreate, afterCreate), this logic must be tested.21
For mutateFormDataBeforeCreate, assert the final state in the database reflects the mutation. For example, if the hook adds the authenticated user's ID, check that the user_id column is correctly populated.
```php
it('assigns the authenticated user as the author on creation', function () {
    // Arrange
    $newData = Post::factory()->make(['user_id' => null]); // Ensure user_id is not in the form data

    // Act
    livewire(PostResource\Pages\CreatePost::class)
        ->fillForm($newData->toArray())
        ->call('create')
        ->assertHasNoFormErrors();

    // Assert
    $this->assertDatabaseHas(Post::class, ['user_id' => $this->user->id]);
});
```


Test Case 7: Custom Notifications and Redirects
If the page overrides default behaviors like getCreatedNotification() or getRedirectUrl(), these customizations must be tested.21
For notifications, use assertNotified() with a closure to inspect the notification's content. For redirects, use assertRedirect() with the expected custom URL.
```php
it('displays a custom success notification', function () {
    livewire(PostResource\Pages\CreatePost::class)
        ->fillForm(Post::factory()->make()->toArray())
        ->call('create')
        ->assertNotified(function (Filament\Notifications\Notification $notification) {
            return $notification->getTitle() === 'Post Successfully Created' &&
                   $notification->getStatus() === 'success';
        });
});
```



### Final Review Checklist

Before this task is considered complete, the refactored code must satisfy the following criteria:
[ ] The test file is located at tests/Feature/Panels/Resources/Pages/.
[ ] The file leverages the global uses(TestCase::class, RefreshDatabase::class)->in('Feature'); configuration from tests/Pest.php.
[ ] All test cases use descriptive, sentence-like names via it().
[ ] All test cases strictly follow the Arrange-Act-Assert pattern.
[ ] All test data is generated dynamically using model factories.
[ ] All tests requiring authentication use the actingAs() helper, preferably within a beforeEach hook.
[ ] All form validation rules are exhaustively tested, using datasets to minimize code duplication.
[ ] All conditional form logic (e.g., visibility, disabled states) is explicitly tested using Filament's dedicated assertFormField... helpers.
[ ] The entire test suite passes when executed with the --parallel option: php artisan test --parallel.
[ ] The tests/Architecture.php file has been reviewed and, if necessary, updated to reflect any new patterns or standards introduced by this refactoring.