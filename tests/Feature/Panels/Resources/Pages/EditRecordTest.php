<?php

use Filament\Actions\DeleteAction;
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