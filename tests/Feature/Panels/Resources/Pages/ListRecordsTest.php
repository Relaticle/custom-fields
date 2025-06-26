<?php

use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\Pages\ListPosts;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\PostResource;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

it('can render posts page', function (): void {
    $this->get(PostResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list posts', function (): void {
    $posts = Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->assertCanSeeTableRecords($posts);
});

it('can render post titles', function (): void {
    Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->assertCanRenderTableColumn('title');
});

it('can render post authors', function (): void {
    Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->assertCanRenderTableColumn('author.name');
});

it('can sort posts by title', function (): void {
    $posts = Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->sortTable('title')
        ->assertCanSeeTableRecords($posts->sortBy('title'), inOrder: true)
        ->sortTable('title', 'desc')
        ->assertCanSeeTableRecords($posts->sortByDesc('title'), inOrder: true);
});

it('can sort posts by author', function (): void {
    $posts = Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->sortTable('author.name')
        ->assertCanSeeTableRecords($posts->sortBy('author.name'), inOrder: true)
        ->sortTable('author.name', 'desc')
        ->assertCanSeeTableRecords($posts->sortByDesc('author.name'), inOrder: true);
});

it('can sort posts with default sort key', function (): void {

    $faker = fake()->unique();
    $posts = Post::factory()->count(10)->state(function () use ($faker) {
        return [
            'id' => $faker->randomDigit(),
            'title' => 'Lorem Ipsum',
        ];
    })->create();

    livewire(ListPosts::class)
        ->sortTable('title')
        ->assertCanSeeTableRecords($posts->sortBy([['title', 'asc'], ['id', 'asc']]), inOrder: true)
        ->sortTable('title', 'desc')
        ->assertCanSeeTableRecords($posts->sortBy([['title', 'desc'], ['id', 'asc']]), inOrder: true);
});

it('can search posts by title', function (): void {
    $posts = Post::factory()->count(10)->create();

    $title = $posts->first()->title;

    livewire(ListPosts::class)
        ->searchTable($title)
        ->assertCanSeeTableRecords($posts->where('title', $title))
        ->assertCanNotSeeTableRecords($posts->where('title', '!=', $title));
});

it('can search posts by author', function (): void {
    $posts = Post::factory()->count(10)->create();

    $author = $posts->first()->author->name;

    livewire(ListPosts::class)
        ->searchTable($author)
        ->assertCanSeeTableRecords($posts->where('author.name', $author))
        ->assertCanNotSeeTableRecords($posts->where('author.name', '!=', $author));
});

it('can filter posts by `is_published`', function (): void {
    $posts = Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->assertCanSeeTableRecords($posts)
        ->filterTable('is_published')
        ->assertCanSeeTableRecords($posts->where('is_published', true))
        ->assertCanNotSeeTableRecords($posts->where('is_published', false));
});