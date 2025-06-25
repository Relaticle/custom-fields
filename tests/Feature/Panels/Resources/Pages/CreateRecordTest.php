<?php

use Relaticle\CustomFields\Data\ValidationRuleData;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\Pages\CreatePost;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\PostResource;

use function Pest\Livewire\livewire;


it('can render page', function (): void {
    $this->get(PostResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create', function (): void {
    $newData = Post::factory()->make();

    livewire(CreatePost::class)
        ->fillForm([
            'author_id' => $newData->author->getKey(),
            'content' => $newData->content,
            'tags' => $newData->tags,
            'title' => $newData->title,
            'rating' => $newData->rating,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas(Post::class, [
        'author_id' => $newData->author->getKey(),
        'content' => $newData->content,
        'tags' => json_encode($newData->tags),
        'title' => $newData->title,
        'rating' => $newData->rating,
    ]);
});

it('can create another', function (): void {
    $newData = Post::factory()->make();
    $newData2 = Post::factory()->make();

    livewire(CreatePost::class)
        ->fillForm([
            'author_id' => $newData->author->getKey(),
            'content' => $newData->content,
            'tags' => $newData->tags,
            'title' => $newData->title,
            'rating' => $newData->rating,
        ])
        ->call('create', true)
        ->assertHasNoFormErrors()
        ->assertNoRedirect()
        ->assertSchemaStateSet([
            'author_id' => null,
            'content' => null,
            'tags' => [],
            'title' => null,
            'rating' => null,
        ])
        ->fillForm([
            'author_id' => $newData2->author->getKey(),
            'content' => $newData2->content,
            'tags' => $newData2->tags,
            'title' => $newData2->title,
            'rating' => $newData2->rating,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas(Post::class, [
        'author_id' => $newData->author->getKey(),
        'content' => $newData->content,
        'tags' => json_encode($newData->tags),
        'title' => $newData->title,
        'rating' => $newData->rating,
    ]);

    $this->assertDatabaseHas(Post::class, [
        'author_id' => $newData2->author->getKey(),
        'content' => $newData2->content,
        'tags' => json_encode($newData2->tags),
        'title' => $newData2->title,
        'rating' => $newData2->rating,
    ]);
});

it('can validate input', function (): void {
    Post::factory()->make();

    livewire(CreatePost::class)
        ->fillForm([
            'title' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['title' => 'required']);
});

it('can create with custom fields', function (): void {
    // Create custom field section for Posts
    $section = CustomFieldSection::factory()->create([
        'name' => 'Post Custom Fields',
        'entity_type' => Post::class,
        'active' => true,
        'sort_order' => 1,
    ]);

    // Create custom fields
    CustomField::factory()->createMany([
        [
            'custom_field_section_id' => $section->id,
            'name' => 'SEO Title',
            'code' => 'seo_title',
            'type' => CustomFieldType::TEXT,
            'sort_order' => 1,
            'entity_type' => Post::class,
            'validation_rules' => [],
        ],
        [
            'custom_field_section_id' => $section->id,
            'name' => 'View Count',
            'code' => 'view_count',
            'type' => CustomFieldType::NUMBER,
            'sort_order' => 2,
            'entity_type' => Post::class,
            'validation_rules' => [],
        ]
    ]);

    $newData = Post::factory()->make();

    livewire(CreatePost::class)
        ->fillForm([
            'author_id' => $newData->author->getKey(),
            'content' => $newData->content,
            'tags' => $newData->tags,
            'title' => $newData->title,
            'rating' => $newData->rating,
            'custom_fields' => [
                'seo_title' => 'Custom SEO Title',
                'view_count' => 100,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    // Assert post was created
    $this->assertDatabaseHas(Post::class, [
        'author_id' => $newData->author->getKey(),
        'content' => $newData->content,
        'tags' => json_encode($newData->tags),
        'title' => $newData->title,
        'rating' => $newData->rating,
    ]);

    // Assert custom field values were saved
    $post = Post::query()->firstWhere('title', $newData->title);

    $customFieldValues = $post->customFieldValues->keyBy('customField.code');

    expect($customFieldValues)->toHaveCount(2)
        ->and($customFieldValues->get('seo_title')?->getValue())->toBe('Custom SEO Title')
        ->and($customFieldValues->get('view_count')?->getValue())->toBe(100);
});

it('can create with required custom fields validation', function (): void {
    // Create custom field section for Posts
    $section = CustomFieldSection::factory()->create([
        'name' => 'Post Custom Fields',
        'entity_type' => Post::class,
        'active' => true,
        'sort_order' => 1,
    ]);

    // Create required custom field
    CustomField::factory()->create([
        'custom_field_section_id' => $section->id,
        'name' => 'Required Field',
        'code' => 'required_field',
        'type' => CustomFieldType::TEXT,
        'sort_order' => 1,
        'entity_type' => Post::class,
        'validation_rules' => [
            new ValidationRuleData(name: 'required', parameters: []),
        ],
    ]);

    $newData = Post::factory()->make();

    livewire(CreatePost::class)
        ->fillForm([
            'author_id' => $newData->author->getKey(),
            'content' => $newData->content,
            'tags' => $newData->tags,
            'title' => $newData->title,
            'rating' => $newData->rating,
            // Missing required custom field
        ])
        ->call('create')
        ->assertHasFormErrors();
});