<?php

declare(strict_types=1);

use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Data\FieldSlotData;
use Relaticle\CustomFields\Data\RelationshipDefinitionData;
use Relaticle\CustomFields\Data\VisibilityConditionData;
use Relaticle\CustomFields\Data\VisibilityData;
use Relaticle\CustomFields\Enums\RelationshipCardinality;
use Relaticle\CustomFields\Enums\VisibilityLogic;
use Relaticle\CustomFields\Enums\VisibilityMode;
use Relaticle\CustomFields\Enums\VisibilityOperator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\Relationships\CreateRelationshipDefinition;
use Relaticle\CustomFields\Tests\Fixtures\Livewire\ThroughTable;
use Relaticle\CustomFields\Tests\Fixtures\Models\Comment;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Models\User;
use Spatie\LaravelData\DataCollection;

afterEach(function (): void {
    ThroughTable::$configureUsing = null;
});

function commentOnPostWith(CustomField $field, string $value): Comment
{
    $post = Post::factory()->create();
    $post->saveCustomFieldValue($field, $value);

    return Comment::factory()->create(['post_id' => $post->getKey()]);
}

it('reads the related record field as column state', function (): void {
    $field = throughTextField(Post::class, 'category', 'Category');
    $comment = commentOnPostWith($field, 'Technology');

    throughTable(Comment::class, Post::class, 'post')
        ->assertTableColumnExists('custom_fields.category')
        ->assertCanSeeTableRecords([$comment])
        ->assertTableColumnStateSet('custom_fields.category', 'Technology', $comment);
});

it('renders empty when the row has no related record', function (): void {
    throughTextField(Post::class, 'category', 'Category');

    $orphan = Comment::factory()->create(['post_id' => Post::query()->max('id') + 1000]);

    throughTable(Comment::class, Post::class, 'post')
        ->assertCanSeeTableRecords([$orphan])
        ->assertTableColumnStateSet('custom_fields.category', null, $orphan)
        ->assertTableColumnFormattedStateSet('custom_fields.category', null, $orphan);
});

it('sorts rows by the related field through a belongs-to relation', function (): void {
    $field = throughTextField(Post::class, 'category', 'Category');

    $bravo = commentOnPostWith($field, 'Bravo');
    $alpha = commentOnPostWith($field, 'Alpha');
    $charlie = commentOnPostWith($field, 'Charlie');

    throughTable(Comment::class, Post::class, 'post')
        ->sortTable('custom_fields.category', 'asc')
        ->assertCanSeeTableRecords([$alpha, $bravo, $charlie], inOrder: true)
        ->sortTable('custom_fields.category', 'desc')
        ->assertCanSeeTableRecords([$charlie, $bravo, $alpha], inOrder: true);
});

it('sorts rows by the related field through a has-one relation', function (): void {
    $field = throughTextField(Post::class, 'category', 'Category');

    $authors = collect(['Bravo', 'Alpha', 'Charlie'])->map(function (string $value) use ($field): User {
        $author = User::factory()->create();

        Post::factory()->create(['author_id' => $author->getKey()])->saveCustomFieldValue($field, $value);

        return $author;
    });

    [$bravo, $alpha, $charlie] = $authors->all();

    throughTable(fn (): Builder => User::query()->whereKey($authors->map(fn (User $author): int => (int) $author->getKey())->all()), Post::class, 'post')
        ->sortTable('custom_fields.category', 'asc')
        ->assertCanSeeTableRecords([$alpha, $bravo, $charlie], inOrder: true)
        ->sortTable('custom_fields.category', 'desc')
        ->assertCanSeeTableRecords([$charlie, $bravo, $alpha], inOrder: true);
});

it('sorts rows by the related field through a morph-one relation', function (): void {
    $field = throughTextField(Comment::class, 'sentiment', 'Sentiment');

    $posts = collect(['Bravo', 'Alpha', 'Charlie'])->map(function (string $value) use ($field): Post {
        $post = Post::factory()->create();

        $comment = Comment::factory()->create([
            'post_id' => $post->getKey(),
            'commentable_type' => $post->getMorphClass(),
            'commentable_id' => $post->getKey(),
        ]);

        $comment->saveCustomFieldValue($field, $value);

        return $post;
    });

    [$bravo, $alpha, $charlie] = $posts->all();

    throughTable(fn (): Builder => Post::query()->whereKey($posts->map(fn (Post $post): int => (int) $post->getKey())->all()), Comment::class, 'featuredComment')
        ->sortTable('custom_fields.sentiment', 'asc')
        ->assertCanSeeTableRecords([$alpha, $bravo, $charlie], inOrder: true)
        ->sortTable('custom_fields.sentiment', 'desc')
        ->assertCanSeeTableRecords([$charlie, $bravo, $alpha], inOrder: true);
});

it('searches rows by the related record field', function (): void {
    $field = throughTextField(Post::class, 'category', 'Category', searchable: true);

    $match = commentOnPostWith($field, 'Technology');
    $other = commentOnPostWith($field, 'Science');

    throughTable(Comment::class, Post::class, 'post')
        ->searchTable('Technology')
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$other]);
});

it('evaluates a visibility condition against the related record', function (): void {
    $status = throughTextField(Post::class, 'status', 'Status');
    $priority = throughTextField(Post::class, 'priority', 'Priority', visibility: new VisibilityData(
        mode: VisibilityMode::SHOW_WHEN,
        logic: VisibilityLogic::ALL,
        conditions: new DataCollection(VisibilityConditionData::class, [
            new VisibilityConditionData(
                field_code: 'status',
                operator: VisibilityOperator::EQUALS,
                value: 'published',
            ),
        ]),
    ));

    $published = Post::factory()->create();
    $published->saveCustomFieldValue($status, 'published');
    $published->saveCustomFieldValue($priority, 'high');

    $draft = Post::factory()->create();
    $draft->saveCustomFieldValue($status, 'draft');
    $draft->saveCustomFieldValue($priority, 'low');

    $onPublished = Comment::factory()->create(['post_id' => $published->getKey()]);
    $onDraft = Comment::factory()->create(['post_id' => $draft->getKey()]);

    throughTable(Comment::class, Post::class, 'post')
        ->assertTableColumnFormattedStateSet('custom_fields.priority', 'high', $onPublished)
        ->assertTableColumnFormattedStateSet('custom_fields.priority', null, $onDraft);
});

it('shows a record field through a relation but leaves it unsortable', function (): void {
    registerPostLookupEntity();

    $definition = app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'through_related_post',
        fromEntityType: (new Post)->getMorphClass(),
        toEntityType: (new Post)->getMorphClass(),
        cardinality: RelationshipCardinality::ManyToOne,
        fromField: new FieldSlotData(name: 'Related Post', sectionId: sectionForEntity((new Post)->getMorphClass())->getKey()),
    ));

    $code = $definition->fromField->code;

    $linked = Post::factory()->create(['title' => 'Linked Post']);
    $post = Post::factory()->create(['custom_fields' => [$code => [$linked->getKey()]]]);
    $comment = Comment::factory()->create(['post_id' => $post->getKey()]);

    throughTable(Comment::class, Post::class, 'post')
        ->assertCanSeeTableRecords([$comment])
        ->assertTableColumnExists('custom_fields.'.$code, fn (Column $column): bool => ! $column->isSortable())
        ->assertSee('Linked Post');
});
