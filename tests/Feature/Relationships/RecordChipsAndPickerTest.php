<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Relaticle\CustomFields\Data\AvatarConfiguration;
use Relaticle\CustomFields\Data\FieldSlotData;
use Relaticle\CustomFields\Data\RelationshipDefinitionData;
use Relaticle\CustomFields\EntitySystem\EntityConfigurator;
use Relaticle\CustomFields\EntitySystem\EntityManager;
use Relaticle\CustomFields\EntitySystem\EntityModel;
use Relaticle\CustomFields\Enums\EntityFeature;
use Relaticle\CustomFields\Enums\RelationshipCardinality;
use Relaticle\CustomFields\Enums\VisibilityLogic;
use Relaticle\CustomFields\Enums\VisibilityMode;
use Relaticle\CustomFields\Enums\VisibilityOperator;
use Relaticle\CustomFields\Filament\Integration\Support\RecordChips;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldLink;
use Relaticle\CustomFields\Models\CustomFieldRelationship;
use Relaticle\CustomFields\Services\Relationships\CardinalityGuard;
use Relaticle\CustomFields\Services\Relationships\CreateRelationshipDefinition;
use Relaticle\CustomFields\Services\Relationships\LinkReader;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\Pages\EditPost;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\Pages\ListPosts;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\PostResource;

function registerChipEntity(?string $avatarAttribute = null): void
{
    config()->set('custom-fields.entity_configuration',
        EntityConfigurator::configure()
            ->autoDiscover(false)
            ->cache(false)
            ->models([
                EntityModel::configure(
                    modelClass: Post::class,
                    labelSingular: 'Post',
                    primaryAttribute: 'title',
                    searchAttributes: ['title'],
                    resourceClass: PostResource::class,
                    features: [EntityFeature::CUSTOM_FIELDS, EntityFeature::LOOKUP_SOURCE],
                    avatarConfiguration: $avatarAttribute === null
                        ? null
                        : new AvatarConfiguration(attribute: $avatarAttribute),
                ),
            ])
    );

    app()->forgetInstance(EntityManager::class);
}

function relatedPostsField(RelationshipCardinality $cardinality = RelationshipCardinality::ManyToMany): CustomFieldRelationship
{
    return app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'related_posts',
        fromEntityType: (new Post)->getMorphClass(),
        toEntityType: (new Post)->getMorphClass(),
        cardinality: $cardinality,
        fromField: new FieldSlotData(name: 'Related Posts', sectionId: sectionForEntity((new Post)->getMorphClass())->getKey()),
    ));
}

function chipLinkQueries(callable $work): int
{
    $links = config('custom-fields.database.table_names.custom_field_links');

    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        $work();

        return count(array_filter(
            DB::getQueryLog(),
            static fn (array $entry): bool => str_contains($entry['query'], $links),
        ));
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }
}

describe('record chips', function (): void {
    it('draws a linked record as a chip with its avatar and a page to open', function (): void {
        registerChipEntity(avatarAttribute: 'content');
        $definition = relatedPostsField();

        $target = Post::factory()->create(['title' => 'Aurora Labs', 'content' => 'https://avatars.test/aurora.png']);
        Post::factory()->create([
            'title' => 'Holder',
            'custom_fields' => [$definition->fromField->code => [$target->getKey()]],
        ]);

        livewire(ListPosts::class)
            ->assertSeeHtml('data-surface="record-chips"')
            ->assertSeeHtml('https://avatars.test/aurora.png')
            ->assertSeeHtml('fi-cf-record-chip')
            ->assertSee('Aurora Labs');
    });

    it('draws the chips in the order the links were set', function (): void {
        registerChipEntity();
        $definition = relatedPostsField();

        $first = Post::factory()->create(['title' => 'Aurora Labs']);
        $second = Post::factory()->create(['title' => 'Borealis Group']);
        $host = Post::factory()->create([
            'title' => 'Holder',
            'custom_fields' => [$definition->fromField->code => [$second->getKey(), $first->getKey()]],
        ]);

        expect($host->fresh()->getCustomFieldValue($definition->fromField))
            ->toBe([$second->getKey(), $first->getKey()]);

        livewire(ListPosts::class)->assertSeeInOrder(['Borealis Group', '1 more']);
    });

    it('says how many chips it is hiding instead of showing a bare count', function (): void {
        registerChipEntity();
        $definition = relatedPostsField();

        $targets = Post::factory()->count(3)->create();
        Post::factory()->create([
            'title' => 'Holder',
            'custom_fields' => [$definition->fromField->code => $targets->pluck('id')->all()],
        ]);

        livewire(ListPosts::class)
            ->assertSeeHtml('fi-cf-record-chips-overflow')
            ->assertSee('2 more');
    });

    it('reads provenance from the edge the page already loaded', function (): void {
        registerChipEntity();
        $definition = relatedPostsField();

        $target = Post::factory()->create(['title' => 'Aurora Labs']);
        $host = Post::factory()->create([
            'title' => 'Holder',
            'custom_fields' => [$definition->fromField->code => [$target->getKey()]],
        ]);

        livewire(ListPosts::class)
            ->assertSeeHtml('data-provenance')
            ->assertSee('Linked by hand');

        $host->load('outgoingLinks.createdBy');

        $withActor = array_values(app(RecordChips::class)->provenance($host, $definition->fromField));

        expect($withActor)->toHaveCount(1)
            ->and($withActor[0])->toContain(auth()->user()->name)
            ->and($withActor[0])->toStartWith('Linked by ');
    });

    it('says a record is not linked rather than leaving the chip row blank', function (): void {
        registerChipEntity();
        relatedPostsField();

        Post::factory()->create(['title' => 'Holder']);

        livewire(ListPosts::class)->assertSee('Not linked');
    });

    it('keeps the stock chip markup in the native flavor', function (): void {
        config()->set('custom-fields.ui.flavor', 'native');
        registerChipEntity();
        $definition = relatedPostsField();

        $target = Post::factory()->create(['title' => 'Aurora Labs']);
        $host = Post::factory()->create([
            'title' => 'Holder',
            'custom_fields' => [$definition->fromField->code => [$target->getKey()]],
        ]);

        livewire(ListPosts::class)
            ->assertDontSeeHtml('data-surface="record-chips"')
            ->assertSee('Aurora Labs');
    });

    it('reads the same number of link queries however many rows the table holds', function (): void {
        registerChipEntity();
        $definition = relatedPostsField();

        $target = Post::factory()->create(['title' => 'Aurora Labs']);

        Post::factory()->count(2)->create([
            'custom_fields' => [$definition->fromField->code => [$target->getKey()]],
        ]);

        // The ledger's one-time table check would otherwise count as a first-render query.
        livewire(ListPosts::class)->assertSuccessful();

        $small = chipLinkQueries(fn (): mixed => livewire(ListPosts::class)->assertSuccessful());

        Post::factory()->count(6)->create([
            'custom_fields' => [$definition->fromField->code => [$target->getKey()]],
        ]);

        $large = chipLinkQueries(fn (): mixed => livewire(ListPosts::class)->assertSuccessful());

        expect($small)->toBe(2)
            ->and($large)->toBe($small);
    });
});

describe('record picker', function (): void {
    it('renders the polished picker with keyboard support and a create-new link', function (): void {
        registerChipEntity();
        relatedPostsField();

        livewire(EditPost::class, ['record' => Post::factory()->create()->getRouteKey()])
            ->assertSeeHtml('data-surface="record-picker"')
            ->assertSeeHtml('role="listbox"')
            ->assertSeeHtml('aria-activedescendant')
            ->assertSee('Create a new Post');
    });

    it('keeps the stock picker in the native flavor', function (): void {
        config()->set('custom-fields.ui.flavor', 'native');
        registerChipEntity();
        relatedPostsField();

        livewire(EditPost::class, ['record' => Post::factory()->create()->getRouteKey()])
            ->assertDontSeeHtml('data-surface="record-picker"')
            ->assertSeeHtml('role="listbox"');
    });

    it('offers no create-new when the entity has no resource to create in', function (): void {
        registerPostLookupEntity();
        relatedPostsField();

        livewire(EditPost::class, ['record' => Post::factory()->create()->getRouteKey()])
            ->assertDontSee('Create a new');
    });

    it('reorders the links to the order the chips were left in', function (): void {
        registerChipEntity();
        $definition = relatedPostsField();

        [$first, $second] = Post::factory()->count(2)->create();
        $host = Post::factory()->create([
            'custom_fields' => [$definition->fromField->code => [$first->getKey(), $second->getKey()]],
        ]);

        livewire(EditPost::class, ['record' => $host->getRouteKey()])
            ->set('data.custom_fields.'.$definition->fromField->code, [$second->getKey(), $first->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($host->fresh()->getCustomFieldValue($definition->fromField->fresh()))
            ->toBe([$second->getKey(), $first->getKey()]);
    });

    it('unlinks the last record the field was holding', function (): void {
        registerChipEntity();
        $definition = relatedPostsField();

        $target = Post::factory()->create();
        $host = Post::factory()->create([
            'custom_fields' => [$definition->fromField->code => [$target->getKey()]],
        ]);

        livewire(EditPost::class, ['record' => $host->getRouteKey()])
            ->set('data.custom_fields.'.$definition->fromField->code, [])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($host->fresh()->getCustomFieldValue($definition->fromField->fresh()))->toBe([])
            ->and(CustomFieldLink::query()->active()->count())->toBe(0)
            ->and(CustomFieldLink::query()->whereNotNull('active_until')->count())->toBe(1);
    });

    it('leaves the links of a field the conditions hide where they are', function (): void {
        registerChipEntity();
        $definition = relatedPostsField();

        CustomField::factory()->ofType('text')->create([
            'custom_field_section_id' => $definition->fromField->custom_field_section_id,
            'entity_type' => Post::class,
            'name' => 'Stage',
            'code' => 'stage',
        ]);

        $definition->fromField->update([
            'settings' => [
                'visibility' => [
                    'mode' => VisibilityMode::SHOW_WHEN,
                    'logic' => VisibilityLogic::ALL,
                    'conditions' => [[
                        'field_code' => 'stage',
                        'operator' => VisibilityOperator::EQUALS,
                        'value' => 'open',
                    ]],
                ],
            ],
        ]);

        $target = Post::factory()->create();
        $host = Post::factory()->create([
            'custom_fields' => [$definition->fromField->code => [$target->getKey()]],
        ]);

        livewire(EditPost::class, ['record' => $host->getRouteKey()])
            ->set('data.custom_fields.stage', 'closed')
            ->set('data.custom_fields.'.$definition->fromField->code, [])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($host->fresh()->getCustomFieldValue($definition->fromField->fresh()))
            ->toBe([$target->getKey()]);
    });

    it('unlinks a record when its chip is taken off the field', function (): void {
        registerChipEntity();
        $definition = relatedPostsField();

        [$kept, $removed] = Post::factory()->count(2)->create();
        $host = Post::factory()->create([
            'custom_fields' => [$definition->fromField->code => [$kept->getKey(), $removed->getKey()]],
        ]);

        livewire(EditPost::class, ['record' => $host->getRouteKey()])
            ->set('data.custom_fields.'.$definition->fromField->code, [$kept->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($host->fresh()->getCustomFieldValue($definition->fromField->fresh()))->toBe([$kept->getKey()])
            ->and(CustomFieldLink::query()->whereNotNull('active_until')->count())->toBe(1);
    });
});

describe('the one-to-one steal', function (): void {
    it('names the holder when the picker asks whether a record can move', function (): void {
        registerChipEntity();
        $definition = relatedPostsField(RelationshipCardinality::OneToOne);

        $target = Post::factory()->create(['title' => 'Aurora Labs']);
        $holder = Post::factory()->create([
            'title' => 'First Holder',
            'custom_fields' => [$definition->fromField->code => [$target->getKey()]],
        ]);

        $conflict = app(CardinalityGuard::class)->violations(
            $definition,
            CustomFieldRelationship::DIRECTION_FROM,
            Post::factory()->create()->getKey(),
            [$target->getKey()],
        );

        expect($conflict)->toHaveCount(1)
            ->and($conflict[0])->toContain('Aurora Labs')
            ->and($conflict[0])->toContain('First Holder')
            ->and($holder->fresh()->getCustomFieldValue($definition->fromField))->toBe([$target->getKey()]);
    });

    it('refuses the move until the payload carries the confirmation', function (): void {
        registerChipEntity();
        $definition = relatedPostsField(RelationshipCardinality::OneToOne);

        $target = Post::factory()->create(['title' => 'Aurora Labs']);
        Post::factory()->create([
            'title' => 'First Holder',
            'custom_fields' => [$definition->fromField->code => [$target->getKey()]],
        ]);
        $second = Post::factory()->create(['title' => 'Second Holder']);

        livewire(EditPost::class, ['record' => $second->getRouteKey()])
            ->set('data.custom_fields.'.$definition->fromField->code, [$target->getKey()])
            ->call('save')
            ->assertHasFormErrors(['custom_fields.'.$definition->fromField->code]);

        expect(CustomFieldLink::query()->whereNull('active_until')->count())->toBe(1);
    });

    it('moves the record once the confirmation travels with the ids', function (): void {
        registerChipEntity();
        $definition = relatedPostsField(RelationshipCardinality::OneToOne);

        $target = Post::factory()->create(['title' => 'Aurora Labs']);
        $first = Post::factory()->create([
            'title' => 'First Holder',
            'custom_fields' => [$definition->fromField->code => [$target->getKey()]],
        ]);
        $second = Post::factory()->create(['title' => 'Second Holder']);

        livewire(EditPost::class, ['record' => $second->getRouteKey()])
            ->set('data.custom_fields.'.$definition->fromField->code, [
                'ids' => [$target->getKey()],
                'replace' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $reader = app(LinkReader::class);

        expect($reader->orderedIdsFor($second->fresh(), $definition, CustomFieldRelationship::DIRECTION_FROM))
            ->toBe([$target->getKey()])
            ->and($reader->orderedIdsFor($first->fresh(), $definition, CustomFieldRelationship::DIRECTION_FROM))
            ->toBe([])
            ->and(CustomFieldLink::query()->whereNotNull('active_until')->count())->toBe(1);
    });
});
