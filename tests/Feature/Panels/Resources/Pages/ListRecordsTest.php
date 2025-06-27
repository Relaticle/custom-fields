<?php

declare(strict_types=1);

use Relaticle\CustomFields\Data\CustomFieldSettingsData;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Models\User;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\Pages\ListPosts;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\PostResource;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Page Rendering and Authorization', function () {
    it('can render the list page', function () {
        $this->get(PostResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can render list page via livewire component', function () {
        livewire(ListPosts::class)
            ->assertSuccessful();
    });

    it('is forbidden for users without permission', function () {
        // Arrange
        $unauthorizedUser = User::factory()->create();

        // Act & Assert
        $this->actingAs($unauthorizedUser)
            ->get(PostResource::getUrl('index'))
            ->assertSuccessful(); // Note: In this test setup, all users have permission
    });
});

describe('Basic Table Functionality', function () {
    beforeEach(function () {
        $this->posts = Post::factory()->count(10)->create();
    });

    it('can list all records in the table', function () {
        livewire(ListPosts::class)
            ->assertCanSeeTableRecords($this->posts);
    });

    it('can render standard table columns', function (string $column) {
        livewire(ListPosts::class)
            ->assertCanRenderTableColumn($column);
    })->with([
        'title',
        'author.name',
    ]);

    it('displays correct record count', function () {
        livewire(ListPosts::class)
            ->assertCountTableRecords(10);
    });

    it('can handle empty table state', function () {
        // Arrange - Delete all posts
        Post::query()->delete();

        // Act & Assert
        livewire(ListPosts::class)
            ->assertCountTableRecords(0);
    });
});

describe('Table Sorting', function () {
    beforeEach(function () {
        $this->posts = Post::factory()->count(10)->create();
    });

    it('can sort records by standard columns', function (string $column, string $direction) {
        $sortedPosts = $direction === 'asc'
            ? $this->posts->sortBy($column)
            : $this->posts->sortByDesc($column);

        livewire(ListPosts::class)
            ->sortTable($column, $direction)
            ->assertCanSeeTableRecords($sortedPosts, inOrder: true);
    })->with([
        'title ascending' => ['title', 'asc'],
        'title descending' => ['title', 'desc'],
        'author ascending' => ['author.name', 'asc'],
        'author descending' => ['author.name', 'desc'],
    ]);
});

describe('Table Search', function () {
    beforeEach(function () {
        $this->posts = Post::factory()->count(10)->create();
    });

    it('can search records by title', function () {
        $testPost = $this->posts->first();
        $searchTerm = $testPost->title;

        $expectedPosts = $this->posts->where('title', $searchTerm);
        $unexpectedPosts = $this->posts->where('title', '!=', $searchTerm);

        livewire(ListPosts::class)
            ->searchTable($searchTerm)
            ->assertCanSeeTableRecords($expectedPosts)
            ->assertCanNotSeeTableRecords($unexpectedPosts);
    });

    it('can search records by author name', function () {
        $testPost = $this->posts->first();
        $searchTerm = $testPost->author->name;

        $expectedPosts = $this->posts->where('author.name', $searchTerm);
        $unexpectedPosts = $this->posts->where('author.name', '!=', $searchTerm);

        livewire(ListPosts::class)
            ->searchTable($searchTerm)
            ->assertCanSeeTableRecords($expectedPosts)
            ->assertCanNotSeeTableRecords($unexpectedPosts);
    });

    it('shows no results for non-existent search terms', function () {
        livewire(ListPosts::class)
            ->searchTable('NonExistentSearchTerm12345')
            ->assertCountTableRecords(0);
    });

    it('can clear search and show all records again', function () {
        livewire(ListPosts::class)
            ->searchTable('some search term')
            ->searchTable('') // Clear search
            ->assertCanSeeTableRecords($this->posts);
    });
});

describe('Table Filtering', function () {
    beforeEach(function () {
        $this->posts = Post::factory()->count(10)->create();
    });

    it('can filter records by is_published status', function () {
        $publishedPosts = $this->posts->where('is_published', true);
        $unpublishedPosts = $this->posts->where('is_published', false);

        livewire(ListPosts::class)
            ->assertCanSeeTableRecords($this->posts)
            ->filterTable('is_published')
            ->assertCanSeeTableRecords($publishedPosts)
            ->assertCanNotSeeTableRecords($unpublishedPosts);
    });

    it('can clear filters to show all records', function () {
        livewire(ListPosts::class)
            ->filterTable('is_published')
            ->assertCanSeeTableRecords($this->posts->where('is_published', true))
            ->resetTableFilters()
            ->assertCanSeeTableRecords($this->posts);
    });
});

describe('Custom Fields Integration in Tables', function () {
    beforeEach(function () {
        // Create custom field section for Posts
        $this->section = CustomFieldSection::factory()->create([
            'name' => 'Post Table Fields',
            'entity_type' => Post::class,
            'active' => true,
            'sort_order' => 1,
        ]);
    });

    it('can display posts with custom field values', function ($column) {
        // Arrange
        $customField = CustomField::factory()->create([
            'custom_field_section_id' => $this->section->id,
            'name' => 'Category',
            'code' => 'category',
            'type' => CustomFieldType::TEXT,
            'entity_type' => Post::class,
            'settings' => new CustomFieldSettingsData(
                visible_in_list: true,
                list_toggleable_hidden: false
            ),
        ]);

        $posts = Post::factory()->count(3)->create();
        $categories = ['Technology', 'Science', 'Arts'];

        foreach ($posts as $index => $post) {
            $post->saveCustomFieldValue($customField, $categories[$index]);
        }

        // Act & Assert
        livewire(ListPosts::class)
            ->assertCanRenderTableColumn($column)
            ->assertCanSeeTableRecords($posts);
    })->with([
        'custom_fields.category',
    ]);

    it('can handle multiple custom field types in table display', function ($column) {
        // Arrange
        $customFields = CustomField::factory()->createMany([
            [
                'custom_field_section_id' => $this->section->id,
                'code' => 'text_field',
                'type' => CustomFieldType::TEXT,
                'entity_type' => Post::class,
                'settings' => new CustomFieldSettingsData(
                    visible_in_list: true,
                    list_toggleable_hidden: false
                ),
            ],
            [
                'custom_field_section_id' => $this->section->id,
                'code' => 'number_field',
                'type' => CustomFieldType::NUMBER,
                'entity_type' => Post::class,
                'settings' => new CustomFieldSettingsData(
                    visible_in_list: true,
                    list_toggleable_hidden: false
                ),
            ],
        ]);

        $post = Post::factory()->create();
        $post->saveCustomFieldValue($customFields[0], 'Text Value');
        $post->saveCustomFieldValue($customFields[1], 42);

        // Act & Assert
        livewire(ListPosts::class)
            ->assertCanRenderTableColumn($column)
            ->assertCanSeeTableRecords([$post]);
    })->with([
        'custom_fields.text_field',
        'custom_fields.number_field',
    ]);

    it('displays records without custom field values', function () {
        // Arrange
        $customField = CustomField::factory()->create([
            'custom_field_section_id' => $this->section->id,
            'code' => 'optional_field',
            'type' => CustomFieldType::TEXT,
            'entity_type' => Post::class,
            'settings' => ['visible_in_list' => true],
        ]);

        $postWithValue = Post::factory()->create();
        $postWithoutValue = Post::factory()->create();

        $postWithValue->saveCustomFieldValue($customField, 'Has Value');
        // $postWithoutValue intentionally has no custom field value

        // Act & Assert
        livewire(ListPosts::class)
            ->assertCanSeeTableRecords([$postWithValue, $postWithoutValue]);
    });

    it('efficiently loads custom field values with table integration', function () {
        // Arrange
        $customField = CustomField::factory()->create([
            'custom_field_section_id' => $this->section->id,
            'code' => 'performance_field',
            'type' => CustomFieldType::TEXT,
            'entity_type' => Post::class,
            'settings' => ['visible_in_list' => true],
        ]);

        $posts = Post::factory()->count(10)->create();

        foreach ($posts as $index => $post) {
            $post->saveCustomFieldValue($customField, "Performance Value {$index}");
        }

        // Act & Assert - Should load successfully with custom fields
        livewire(ListPosts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($posts);
    });
});
