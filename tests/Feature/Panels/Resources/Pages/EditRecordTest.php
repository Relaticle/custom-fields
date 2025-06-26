<?php

use Filament\Actions\DeleteAction;
use Relaticle\CustomFields\Data\ValidationRuleData;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\Pages\EditPost;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\PostResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;
use function Pest\Laravel\assertSoftDeleted;

it('can render page', function (): void {
    $this->get(PostResource::getUrl('edit', [
        'record' => Post::factory()->create(),
    ]))->assertSuccessful();
});

it('can retrieve data', function (): void {
    $post = Post::factory()->create();

    livewire(EditPost::class, [
        'record' => $post->getKey(),
    ])
        ->assertSchemaStateSet([
            'author_id' => $post->author->getKey(),
            'content' => $post->content,
            'tags' => $post->tags,
            'title' => $post->title,
            'rating' => $post->rating,
        ]);
});

it('can save', function (): void {
    $post = Post::factory()->create();
    $newData = Post::factory()->make();

    livewire(EditPost::class, [
        'record' => $post->getKey(),
    ])
        ->fillForm([
            'author_id' => $newData->author->getKey(),
            'content' => $newData->content,
            'tags' => $newData->tags,
            'title' => $newData->title,
            'rating' => $newData->rating,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($post->refresh())
        ->author->toBeSameModel($newData->author)
        ->content->toBe($newData->content)
        ->tags->toBe($newData->tags)
        ->title->toBe($newData->title);
});

it('can validate input', function (): void {
    $post = Post::factory()->create();

    livewire(EditPost::class, [
        'record' => $post->getKey(),
    ])
        ->fillForm([
            'title' => null,
        ])
        ->call('save')
        ->assertHasFormErrors(['title' => 'required']);
});

it('can delete', function (): void {
    $post = Post::factory()->create();

    livewire(EditPost::class, [
        'record' => $post->getKey(),
    ])
        ->callAction(DeleteAction::class);

    assertSoftDeleted($post);
});

it('can refresh data', function (): void {
    $post = Post::factory()->create();

    $page = livewire(EditPost::class, [
        'record' => $post->getKey(),
    ]);

    $originalPostTitle = $post->title;

    $page->assertSchemaStateSet([
        'title' => $originalPostTitle,
    ]);

    $newPostTitle = Str::random();

    $post->title = $newPostTitle;
    $post->save();

    $page->assertSchemaStateSet([
        'title' => $originalPostTitle,
    ]);

    $page->call('refreshTitle');

    $page->assertSchemaStateSet([
        'title' => $newPostTitle,
    ]);
});

test('actions will not interfere with database transactions on an error', function (): void {
    $post = Post::factory()->create();

    $transactionLevel = DB::transactionLevel();

    try {
        livewire(EditPost::class, [
            'record' => $post->getKey(),
        ])
            ->callAction('randomize_title');
    } catch (Exception $exception) {
        // This can be caught and handled somewhere else, code continues...
    }

    // Original transaction level should be unaffected...

    expect(DB::transactionLevel())
        ->toBe($transactionLevel);
});

it('can edit with custom fields', function (): void {
    // Create custom field section for Posts
    $section = CustomFieldSection::factory()->create([
        'name' => 'Post Custom Fields',
        'entity_type' => Post::class,
        'active' => true,
        'sort_order' => 1,
    ]);

    // Create custom fields
    $customFields = CustomField::factory()->createMany([
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

    // Create post with initial custom field values
    $post = Post::factory()->create();
    $post->saveCustomFieldValue($customFields->first(), 'Original SEO Title');
    $post->saveCustomFieldValue($customFields->last(), 50);

    $newData = Post::factory()->make();

    livewire(EditPost::class, [
        'record' => $post->getKey(),
    ])
        ->fillForm([
            'author_id' => $newData->author->getKey(),
            'content' => $newData->content,
            'tags' => $newData->tags,
            'title' => $newData->title,
            'rating' => $newData->rating,
            'custom_fields' => [
                'seo_title' => 'Updated SEO Title',
                'view_count' => 200,
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Assert post was updated
    expect($post->refresh())
        ->author->toBeSameModel($newData->author)
        ->content->toBe($newData->content)
        ->tags->toBe($newData->tags)
        ->title->toBe($newData->title);

    // Assert custom field values were updated
    $customFieldValues = $post->customFieldValues->keyBy('customField.code');

    expect($customFieldValues)->toHaveCount(2)
        ->and($customFieldValues->get('seo_title')?->getValue())->toBe('Updated SEO Title')
        ->and($customFieldValues->get('view_count')?->getValue())->toBe(200);
});

it('can retrieve data with custom fields', function (): void {
    // Create custom field section for Posts
    $section = CustomFieldSection::factory()->create([
        'name' => 'Post Custom Fields',
        'entity_type' => Post::class,
        'active' => true,
        'sort_order' => 1,
    ]);

    // Create custom field
    $customField = CustomField::factory()->create([
        'custom_field_section_id' => $section->id,
        'name' => 'SEO Title',
        'code' => 'seo_title',
        'type' => CustomFieldType::TEXT,
        'sort_order' => 1,
        'entity_type' => Post::class,
        'validation_rules' => [],
    ]);

    // Create post with custom field values
    $post = Post::factory()->create();
    $post->saveCustomFieldValue($customField, 'Test SEO Title');

    livewire(EditPost::class, [
        'record' => $post->getKey(),
    ])
        ->assertSchemaStateSet([
            'author_id' => $post->author->getKey(),
            'content' => $post->content,
            'tags' => $post->tags,
            'title' => $post->title,
            'rating' => $post->rating,
            'custom_fields' => [
                'seo_title' => 'Test SEO Title',
            ],
        ]);
});

it('can edit with required custom fields validation', function (): void {
    // Create a custom field section for Posts
    $section = CustomFieldSection::factory()->create([
        'name' => 'Post Custom Fields',
        'entity_type' => Post::class,
        'active' => true,
        'sort_order' => 1,
    ]);

    // Create required custom field
    $metaDescriptionCustonField = CustomField::factory()->create([
        'custom_field_section_id' => $section->id,
        'name' => 'Meta Description',
        'code' => 'meta_description',
        'type' => CustomFieldType::TEXT,
        'sort_order' => 1,
        'entity_type' => Post::class,
        'validation_rules' => [
            new ValidationRuleData(name: 'required', parameters: []),
        ],
    ]);

    // Create post with initial custom field value
    $post = Post::factory()->create();
    $post->saveCustomFieldValue($metaDescriptionCustonField, 'Original meta description');

    livewire(EditPost::class, [
        'record' => $post->getKey(),
    ])
        ->fillForm([
            'title' => 'Updated Title',
            'custom_fields' => [
                'meta_description' => '', // Empty required field
            ],
        ])
        ->call('save')
        ->assertHasFormErrors(['custom_fields.meta_description']);
});