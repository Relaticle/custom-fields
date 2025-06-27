<?php

use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\PostResource;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\Pages\ViewPost;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

it('can render page', function () {
    $this->get(PostResource::getUrl('view', [
        'record' => Post::factory()->create(),
    ]))->assertSuccessful();
});

it('can retrieve data', function () {
    $post = Post::factory()->create();

    livewire(ViewPost::class, [
        'record' => $post->getKey(),
    ])
        ->assertSchemaStateSet([
            'author_id' => $post->author->getKey(),
            'content' => $post->content,
            'tags' => $post->tags,
            'title' => $post->title,
        ]);
});

it('can refresh data', function () {
    $post = Post::factory()->create();

    $page = livewire(ViewPost::class, [
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