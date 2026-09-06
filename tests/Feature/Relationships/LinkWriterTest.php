<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Relaticle\CustomFields\Data\FieldSlotData;
use Relaticle\CustomFields\Data\RelationshipDefinitionData;
use Relaticle\CustomFields\Enums\RelationshipCardinality;
use Relaticle\CustomFields\Events\RelationshipLinkClosed;
use Relaticle\CustomFields\Events\RelationshipLinkCreated;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldLink;
use Relaticle\CustomFields\Models\CustomFieldRelationship;
use Relaticle\CustomFields\Services\Relationships\CreateRelationshipDefinition;
use Relaticle\CustomFields\Services\Relationships\LinkWriter;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Models\User;

function makeAuthorship(RelationshipCardinality $cardinality = RelationshipCardinality::ManyToOne): CustomFieldRelationship
{
    return app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'authorship',
        fromEntityType: (new Post)->getMorphClass(),
        toEntityType: (new User)->getMorphClass(),
        cardinality: $cardinality,
        fromField: new FieldSlotData(name: 'Author', sectionId: sectionForEntity((new Post)->getMorphClass())->getKey()),
        toField: new FieldSlotData(name: 'Posts', sectionId: sectionForEntity((new User)->getMorphClass())->getKey()),
    ));
}

function makeSpouse(): CustomFieldRelationship
{
    return app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'spouse',
        fromEntityType: (new User)->getMorphClass(),
        toEntityType: (new User)->getMorphClass(),
        cardinality: RelationshipCardinality::OneToOne,
        isSymmetric: true,
        fromField: new FieldSlotData(name: 'Spouse', sectionId: sectionForEntity((new User)->getMorphClass())->getKey()),
    ));
}

it('adds, keeps, and removes links from a diff', function (): void {
    $definition = makeAuthorship(RelationshipCardinality::ManyToMany);
    $post = Post::factory()->create();
    [$a, $b, $c] = User::factory()->count(3)->create();

    app(LinkWriter::class)->apply($post, $definition->fromField, [$a->getKey(), $b->getKey()]);

    $kept = CustomFieldLink::query()->where('to_entity_id', $b->getKey())->sole();

    app(LinkWriter::class)->apply($post, $definition->fromField, [$b->getKey(), $c->getKey()]);

    expect(CustomFieldLink::query()->active()->orderBy('sort_order')->pluck('to_entity_id')->map(intval(...))->all())
        ->toBe([$b->getKey(), $c->getKey()])
        ->and(CustomFieldLink::query()->count())->toBe(3)
        ->and($kept->refresh()->active_until)->toBeNull()
        ->and($kept->sort_order)->toBe(0);
});

it('replaces the link when a single end takes a new target', function (): void {
    $definition = makeAuthorship();
    $post = Post::factory()->create();
    [$a, $b] = User::factory()->count(2)->create();

    app(LinkWriter::class)->apply($post, $definition->fromField, [$a->getKey()]);
    app(LinkWriter::class)->apply($post, $definition->fromField, [$b->getKey()]);

    $active = CustomFieldLink::query()->active()->get();

    expect($active)->toHaveCount(1)
        ->and($active->first()->to_entity_id)->toEqual($b->getKey())
        ->and(CustomFieldLink::query()->count())->toBe(2);
});

it('clears every link for an empty payload', function (): void {
    $definition = makeAuthorship();
    $post = Post::factory()->create();
    $user = User::factory()->create();

    app(LinkWriter::class)->apply($post, $definition->fromField, [$user->getKey()]);
    app(LinkWriter::class)->apply($post, $definition->fromField, []);

    expect(CustomFieldLink::query()->active()->count())->toBe(0)
        ->and(CustomFieldLink::query()->count())->toBe(1);
});

it('writes the same edge whichever side applies it', function (): void {
    $definition = makeAuthorship();
    $post = Post::factory()->create();
    $user = User::factory()->create();

    app(LinkWriter::class)->apply($post, $definition->fromField, [$user->getKey()]);
    app(LinkWriter::class)->apply($user, $definition->toField, [$post->getKey()]);

    expect(CustomFieldLink::query()->count())->toBe(1)
        ->and(CustomFieldLink::query()->active()->count())->toBe(1);
});

it('clears from the far side of the same edge', function (): void {
    $definition = makeAuthorship();
    $post = Post::factory()->create();
    $user = User::factory()->create();

    app(LinkWriter::class)->apply($post, $definition->fromField, [$user->getKey()]);
    app(LinkWriter::class)->apply($user, $definition->toField, []);

    expect(CustomFieldLink::query()->active()->count())->toBe(0);
});

it('leaves the many end alone when only the other end is single', function (): void {
    $definition = makeAuthorship();
    [$postA, $postB] = Post::factory()->count(2)->create();
    $user = User::factory()->create();

    app(LinkWriter::class)->apply($postA, $definition->fromField, [$user->getKey()]);
    app(LinkWriter::class)->apply($postB, $definition->fromField, [$user->getKey()]);

    expect(CustomFieldLink::query()->active()->count())->toBe(2);
});

it('steals a taken one to one end and closes the displaced edge', function (): void {
    $definition = makeAuthorship(RelationshipCardinality::OneToOne);
    [$postA, $postB] = Post::factory()->count(2)->create();
    $user = User::factory()->create();

    app(LinkWriter::class)->apply($postA, $definition->fromField, [$user->getKey()]);
    app(LinkWriter::class)->apply($postB, $definition->fromField, [$user->getKey()]);

    $active = CustomFieldLink::query()->active()->get();

    expect($active)->toHaveCount(1)
        ->and($active->first()->from_entity_id)->toEqual($postB->getKey())
        ->and(CustomFieldLink::query()->count())->toBe(2)
        ->and(CustomFieldLink::query()->whereNotNull('active_until')->sole()->from_entity_id)->toEqual($postA->getKey());
});

it('canonicalizes a symmetric edge to one row read from both records', function (): void {
    $definition = makeSpouse();
    [$a, $b] = User::factory()->count(2)->create();

    app(LinkWriter::class)->apply($a, $definition->fromField, [$b->getKey()]);
    app(LinkWriter::class)->apply($b, $definition->fromField, [$a->getKey()]);

    $link = CustomFieldLink::query()->sole();

    expect(CustomFieldLink::query()->count())->toBe(1)
        ->and(strcmp((string) $link->from_entity_id, (string) $link->to_entity_id))->toBeLessThanOrEqual(0)
        ->and([(string) $link->from_entity_id, (string) $link->to_entity_id])
        ->toEqualCanonicalizing([(string) $a->getKey(), (string) $b->getKey()]);
});

it('steals a taken symmetric end from either side', function (): void {
    $definition = makeSpouse();
    [$a, $b, $c] = User::factory()->count(3)->create();

    app(LinkWriter::class)->apply($a, $definition->fromField, [$b->getKey()]);
    app(LinkWriter::class)->apply($c, $definition->fromField, [$b->getKey()]);

    expect(CustomFieldLink::query()->active()->count())->toBe(1)
        ->and(CustomFieldLink::query()->count())->toBe(2);
});

it('emits a created and a closed event carrying the edge', function (): void {
    Event::fake([RelationshipLinkCreated::class, RelationshipLinkClosed::class]);

    $definition = makeAuthorship();
    $post = Post::factory()->create();
    $user = User::factory()->create();

    app(LinkWriter::class)->apply($post, $definition->fromField, [$user->getKey()]);
    app(LinkWriter::class)->apply($post, $definition->fromField, []);

    Event::assertDispatchedTimes(RelationshipLinkCreated::class, 1);
    Event::assertDispatchedTimes(RelationshipLinkClosed::class, 1);
    Event::assertDispatched(RelationshipLinkClosed::class, fn (RelationshipLinkClosed $event): bool => $event->link->to_entity_id === $user->getKey()
        && $event->link->active_until !== null);
});

it('touches nothing when the payload matches the stored links', function (): void {
    $definition = makeAuthorship();
    $post = Post::factory()->create();
    $user = User::factory()->create();

    app(LinkWriter::class)->apply($post, $definition->fromField, [$user->getKey()]);

    Event::fake([RelationshipLinkCreated::class, RelationshipLinkClosed::class]);

    app(LinkWriter::class)->apply($post, $definition->fromField, [$user->getKey()]);

    Event::assertNothingDispatched();
    expect(CustomFieldLink::query()->count())->toBe(1);
});

it('stamps the actor and the source on the edge', function (): void {
    $definition = makeAuthorship();
    $actor = User::factory()->create();
    $this->actingAs($actor);
    $post = Post::factory()->create();
    $user = User::factory()->create();

    app(LinkWriter::class)->apply($post, $definition->fromField, [$user->getKey()], CustomFieldLink::SOURCE_IMPORT);

    $link = CustomFieldLink::query()->sole();

    expect($link->created_by_id)->toEqual($actor->getKey())
        ->and($link->created_by_type)->toBe($actor->getMorphClass())
        ->and($link->source)->toBe(CustomFieldLink::SOURCE_IMPORT)
        ->and($link->active_from)->not->toBeNull();
});

it('rejects a target that does not exist', function (): void {
    $definition = makeAuthorship();
    $post = Post::factory()->create();

    $apply = fn (): mixed => app(LinkWriter::class)->apply($post, $definition->fromField, [404]);

    expect($apply)->toThrow(ValidationException::class)
        ->and(CustomFieldLink::query()->count())->toBe(0);
});

it('rejects a target the host query cannot reach', function (): void {
    $definition = makeAuthorship();
    $post = Post::factory()->create();
    $foreign = User::factory()->create();

    User::addGlobalScope('other_tenant', fn (Builder $query) => $query->whereKeyNot($foreign->getKey()));

    $apply = fn (): mixed => app(LinkWriter::class)->apply($post, $definition->fromField, [$foreign->getKey()]);

    expect($apply)->toThrow(ValidationException::class)
        ->and(CustomFieldLink::query()->count())->toBe(0);
});

it('refuses a record field with no definition', function (): void {
    $field = CustomField::factory()->create([
        'type' => 'record',
        'entity_type' => (new Post)->getMorphClass(),
        'custom_field_section_id' => sectionForEntity((new Post)->getMorphClass())->getKey(),
    ]);

    app(LinkWriter::class)->apply(Post::factory()->create(), $field, []);
})->throws(InvalidArgumentException::class);

it('refuses a record from the wrong end of the definition', function (): void {
    $definition = makeAuthorship();

    app(LinkWriter::class)->apply(User::factory()->create(), $definition->fromField, []);
})->throws(InvalidArgumentException::class);

it('translates a lost race into a validation error', function (): void {
    $definition = makeAuthorship();
    $post = Post::factory()->create();
    [$a, $b] = User::factory()->count(2)->create();

    app(LinkWriter::class)->apply($post, $definition->fromField, [$a->getKey()]);

    Event::listen(RelationshipLinkClosed::class, function () use ($definition, $post, $b): void {
        CustomFieldLink::factory()->create([
            'relationship_id' => $definition->getKey(),
            'from_entity_type' => $post->getMorphClass(),
            'from_entity_id' => $post->getKey(),
            'to_entity_type' => $b->getMorphClass(),
            'to_entity_id' => $b->getKey(),
        ]);
    });

    $apply = fn (): mixed => app(LinkWriter::class)->apply($post, $definition->fromField, [$b->getKey()]);

    expect($apply)->toThrow(ValidationException::class)
        ->and(CustomFieldLink::query()->active()->sole()->to_entity_id)->toEqual($a->getKey());
})->skip(
    fn (): bool => DB::connection()->getDriverName() === 'mysql',
    'The MySQL family has no partial index, so there is no constraint to race against.',
);

it('copies the tenant of the definition onto every edge', function (): void {
    useTenantSchema(7);

    $definition = makeAuthorship();
    $post = Post::factory()->create();
    $user = User::factory()->create();

    app(LinkWriter::class)->apply($post, $definition->fromField, [$user->getKey()]);

    expect(CustomFieldLink::query()->sole()->tenant_id)->toBe(7);

    DB::table(config('custom-fields.database.table_names.custom_field_links'))->update(['tenant_id' => 8]);

    expect(CustomFieldLink::query()->count())->toBe(0);
})->skip(
    fn (): bool => DB::connection()->getDriverName() === 'mysql',
    'MySQL commits DDL implicitly, so the added tenant columns would outlive the test transaction.',
);
