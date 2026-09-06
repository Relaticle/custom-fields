<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Relaticle\CustomFields\Data\FieldSlotData;
use Relaticle\CustomFields\Data\RelationshipDefinitionData;
use Relaticle\CustomFields\Enums\RelationshipCardinality;
use Relaticle\CustomFields\Filament\Management\Forms\Components\TypeField;
use Relaticle\CustomFields\Livewire\ManageFieldsTable;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\Relationships\CreateRelationshipDefinition;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

function postFieldsTable(): Testable
{
    return livewire(ManageFieldsTable::class, ['entityType' => Post::class]);
}

describe('the attribute table', function (): void {
    it('gives every active row a reorder handle, a type icon and its badges', function (): void {
        CustomField::factory()->ofType('text')->create([
            'entity_type' => Post::class,
            'name' => 'Account owner',
            'settings' => ['unique_per_entity_type' => true],
            'validation_rules' => ['required' => true],
        ]);

        postFieldsTable()
            ->assertSeeHtml('data-surface="attribute-table"')
            ->assertSeeHtml('x-sortable-handle')
            ->assertSeeHtml('fi-cf-attribute-row')
            ->assertSee('Account owner')
            ->assertSee('Unique')
            ->assertSee('Required');
    });

    it('offers an archived field an activate button without opening the menu', function (): void {
        $field = CustomField::factory()->ofType('text')->create([
            'entity_type' => Post::class,
            'name' => 'Retired field',
        ]);
        $field->deactivate();

        postFieldsTable()
            ->assertSee('Archived')
            ->assertSeeHtml('activateField')
            ->callAction('activateField', arguments: ['fieldId' => $field->getKey()]);

        expect($field->fresh()->isActive())->toBeTrue();
    });

    it('connects the two rows a relationship pairs', function (): void {
        $section = sectionForEntity(Post::class);

        $definition = app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
            code: 'mentions',
            fromEntityType: Post::class,
            toEntityType: Post::class,
            cardinality: RelationshipCardinality::ManyToMany,
            fromField: new FieldSlotData(name: 'Mentions', sectionId: $section->getKey()),
            toField: new FieldSlotData(name: 'Mentioned By', sectionId: $section->getKey()),
        ));

        postFieldsTable()
            ->assertSeeHtml('data-pair="'.$definition->getKey().'"')
            ->assertSeeHtml('data-pair-partner="'.$definition->to_field_id.'"')
            ->assertSeeHtml('data-pair-partner="'.$definition->from_field_id.'"')
            ->assertSee('Paired with Mentioned By on Post')
            ->assertSee('Paired with Mentions on Post');
    });

    it('says what a custom field is when there are none', function (): void {
        postFieldsTable()
            ->assertSee('No custom fields yet')
            ->assertSee('adds a column of your own to every record');
    });

    it('holds a skeleton for the row that is still loading', function (): void {
        CustomField::factory()->ofType('text')->create(['entity_type' => Post::class, 'name' => 'Owner']);

        postFieldsTable()
            ->assertSeeHtml('fi-cf-attribute-skeleton')
            ->assertSeeHtml('wire:target="search"');
    });

    it('reads the relationship pairs in one query however many record fields there are', function (): void {
        $section = sectionForEntity(Post::class);

        foreach (['first', 'second', 'third'] as $index => $code) {
            app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
                code: 'pair_'.$code,
                fromEntityType: Post::class,
                toEntityType: Post::class,
                cardinality: RelationshipCardinality::ManyToMany,
                fromField: new FieldSlotData(name: 'Pair '.$index, sectionId: $section->getKey()),
            ));
        }

        $component = postFieldsTable();

        expect($component->instance()->relationshipPairs())->toHaveCount(3);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        unset($component->instance()->relationshipPairs);
        $component->instance()->relationshipPairs();

        expect($queries)->toBeLessThanOrEqual(4);
    });

    it('keeps the pre-redesign table in the native flavor', function (): void {
        config()->set('custom-fields.ui.flavor', 'native');

        CustomField::factory()->ofType('text')->create(['entity_type' => Post::class, 'name' => 'Owner']);

        postFieldsTable()
            ->assertDontSeeHtml('data-surface="attribute-table"')
            ->assertSee('Owner');
    });
});

describe('the type picker', function (): void {
    it('describes every field type the package ships', function (): void {
        $choices = TypeField::make('type')->getTypeChoices();

        expect($choices)->not->toBeEmpty();

        $missing = array_values(array_filter(
            $choices,
            static fn (array $choice): bool => $choice['description'] === null,
        ));

        expect($missing)->toBeEmpty(implode(', ', array_column($missing, 'key')));
    });

    it('renders the grid with a search box, icons and descriptions', function (): void {
        $choices = TypeField::make('type')->getTypeChoices();

        $html = view('custom-fields::flavors.polished.partials.type-picker-grid', [
            'choices' => $choices,
            'isDisabled' => false,
            'label' => 'Type',
            'stateBinding' => "\$entangle('data.type')",
        ])->render();

        expect($html)
            ->toContain('data-surface="type-picker"')
            ->toContain('Search field types')
            ->toContain('role="radiogroup"')
            ->toContain('A single line of text.')
            ->toContain('dark:');
    });

    it('says the type is locked rather than offering a grid on an existing field', function (): void {
        $html = view('custom-fields::flavors.polished.partials.type-picker-grid', [
            'choices' => TypeField::make('type')->getTypeChoices(),
            'isDisabled' => true,
            'label' => 'Type',
            'stateBinding' => "\$entangle('data.type')",
        ])->render();

        expect($html)
            ->toContain('A field keeps the type it was created with.')
            ->not->toContain('Search field types');
    });
});
