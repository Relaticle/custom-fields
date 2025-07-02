# PestPHP Testing Guide for Laravel Applications

## Quick Start Checklist

Before writing any tests, ensure:
- [ ] PestPHP is installed and configured
- [ ] `tests/Pest.php` has proper global configuration
- [ ] Architecture tests are set up in `tests/Architecture.php`
- [ ] You understand the feature-first testing philosophy

## Core Testing Philosophy

### 🎯 Feature Tests First (80-90% of your tests)
```php
// ✅ GOOD: Feature test that validates behavior
it('can create a new user', function () {
    $this->post('/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ])
    ->assertStatus(201)
    ->assertJson(['message' => 'User created successfully']);
    
    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});

// ❌ BAD: Unit test tightly coupled to implementation
it('calls the user service', function () {
    $mock = Mockery::mock(UserService::class);
    $mock->shouldReceive('create')->once();
    // ... more mocking
});
```

### 🚫 When NOT to Write Unit Tests
- Don't mock every dependency
- Don't test internal method calls
- Don't write tests that break when refactoring
- Focus on behavior, not implementation

## Test Structure Standards

### 1. Basic Test Anatomy
Every test MUST follow this structure:

```php
it('describes what the system should do', function () {
    // Arrange - Set up test data
    $user = User::factory()->create();
    
    // Act - Perform the action
    $response = $this->actingAs($user)
        ->post('/posts', ['title' => 'My Post']);
    
    // Assert - Verify the outcome
    $response->assertCreated();
    $this->assertDatabaseHas('posts', ['title' => 'My Post']);
});
```

### 2. Global Configuration (`tests/Pest.php`)
```php
uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
)->in('Feature');

// Helper functions
function createAuthenticatedUser(): User
{
    return User::factory()->create();
}
```

### 3. Architecture Tests (`tests/Architecture.php`)
```php
// Enforce architectural rules
arch('Controllers do not use Models directly')
    ->expect('App\Models')
    ->not->toBeUsedIn('App\Http\Controllers');

arch('Services have Service suffix')
    ->expect('App\Services')
    ->toHaveSuffix('Service');

arch('No debugging functions in production')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('DTOs are immutable')
    ->expect('App\DataTransferObjects')
    ->toBeReadonly();
```

## Laravel/Filament Testing Reference

### Testing Helper Hierarchy
Always use the most specific helper available:

| Task | First Choice (Filament) | Second Choice (Livewire) | Last Resort (Laravel) |
|------|------------------------|--------------------------|----------------------|
| Render Page | `livewire(CreateRecord::class)` | `livewire(Component::class)` | `$this->get(route(...))` |
| Fill Form | `->fillForm([...])` | `->set('field', 'value')` | N/A |
| Submit Form | `->call('create')` | `->call('method')` | `$this->post(...)` |
| Check Validation | `->assertHasFormErrors([...])` | `->assertHasErrors([...])` | `->assertSessionHasErrors()` |
| Check Field State | `->assertFormFieldIsVisible()` | `->assertSee()` | `->assertSee()` |

## Practical Examples

### Example 1: Testing a Filament Resource Create Page

```php
use App\Filament\Resources\PostResource;
use App\Models\Post;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Post Creation', function () {
    it('renders the create page', function () {
        livewire(PostResource\Pages\CreatePost::class)
            ->assertSuccessful()
            ->assertFormExists();
    });
    
    it('creates a post with valid data', function () {
        $postData = Post::factory()->make()->toArray();
        
        livewire(PostResource\Pages\CreatePost::class)
            ->fillForm($postData)
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();
            
        $this->assertDatabaseHas('posts', $postData);
    });
    
    it('validates required fields', function (string $field, mixed $value, string $rule) {
        livewire(PostResource\Pages\CreatePost::class)
            ->fillForm([$field => $value])
            ->call('create')
            ->assertHasFormErrors([$field => $rule]);
    })->with([
        'title required' => ['title', null, 'required'],
        'title min length' => ['title', 'ab', 'min:3'],
        'slug unique' => ['slug', fn() => Post::factory()->create()->slug, 'unique'],
    ]);
});
```

### Example 2: Testing API Endpoints

```php
describe('API Posts', function () {
    it('lists all posts', function () {
        Post::factory()->count(3)->create();
        
        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });
    
    it('requires authentication to create posts', function () {
        $this->postJson('/api/posts', ['title' => 'Test'])
            ->assertUnauthorized();
    });
});
```

### Example 3: Testing with Datasets

```php
it('calculates order totals correctly', function ($items, $expectedTotal) {
    $order = Order::factory()
        ->hasItems($items)
        ->create();
        
    expect($order->total)->toBe($expectedTotal);
})->with([
    'single item' => [
        [['price' => 10, 'quantity' => 1]], 
        10
    ],
    'multiple items' => [
        [
            ['price' => 10, 'quantity' => 2],
            ['price' => 5, 'quantity' => 3]
        ], 
        35
    ],
]);
```

## Essential CLI Commands

```bash
# Run tests in parallel (fastest)
./vendor/bin/pest --parallel

# Run only changed tests (development)
./vendor/bin/pest --dirty

# Re-run failed tests
./vendor/bin/pest --retry

# Run specific test
./vendor/bin/pest --filter "can create a post"

# Find slowest tests
./vendor/bin/pest --profile

# List todos
./vendor/bin/pest --todos
```

## Best Practices Summary

### ✅ DO
- Write feature tests by default
- Use descriptive test names
- Follow AAA pattern (Arrange-Act-Assert)
- Use model factories for test data
- Test behavior, not implementation
- Use `beforeEach` for common setup
- Leverage datasets for validation testing
- Use `RefreshDatabase` trait

### ❌ DON'T
- Don't write unit tests for everything
- Don't mock unnecessarily
- Don't use database seeders in tests
- Don't hardcode test data
- Don't test framework features
- Don't write brittle implementation tests
- Don't use `dd()` or `dump()` in committed code

## Common Testing Patterns

### 1. Authentication Testing
```php
beforeEach(function () {
    $this->user = User::factory()->create();
});

it('requires authentication', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    
    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertOk();
});
```

### 2. Authorization Testing
```php
it('denies access without permission', function () {
    $userWithoutPermission = User::factory()->create();
    
    $this->actingAs($userWithoutPermission)
        ->get('/admin/users')
        ->assertForbidden();
});
```

### 3. Form Validation Testing
```php
it('validates user registration', function ($field, $value, $error) {
    $this->post('/register', [$field => $value])
        ->assertSessionHasErrors([$field => $error]);
})->with([
    ['email', 'invalid-email', 'email'],
    ['password', '123', 'min:8'],
    ['name', '', 'required'],
]);
```

### 4. File Upload Testing
```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('can upload avatar', function () {
    Storage::fake('avatars');
    
    $file = UploadedFile::fake()->image('avatar.jpg');
    
    $this->actingAs($this->user)
        ->post('/profile/avatar', ['avatar' => $file])
        ->assertOk();
        
    Storage::disk('avatars')->assertExists($file->hashName());
});
```

## Resources

- [PestPHP Documentation](https://pestphp.com)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [Filament Testing Documentation](https://filamentphp.com/docs/4.x/testing/overview)
- Run `./vendor/bin/pest --help` for all CLI options