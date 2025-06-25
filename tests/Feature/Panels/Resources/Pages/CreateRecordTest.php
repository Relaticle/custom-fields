<?php

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