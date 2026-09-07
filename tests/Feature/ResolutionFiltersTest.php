<?php

declare(strict_types=1);

use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Relaticle\CustomFields\CustomFields as CustomFieldsRegistry;
use Relaticle\CustomFields\Enums\ResolutionKind;
use Relaticle\CustomFields\Enums\VisibilityOperator;
use Relaticle\CustomFields\Facades\CustomFields;
use Relaticle\CustomFields\Filament\Integration\Builders\FieldResolutionContext;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;

afterEach(function (): void {
    CustomFieldsRegistry::flushResolutionFilters();
});

describe('resolution filter registry', function (): void {
    it('starts with no filters registered', function (): void {
        expect(CustomFieldsRegistry::fieldFilters())->toBe([])
            ->and(CustomFieldsRegistry::sectionFilters())->toBe([]);
    });

    it('keeps field and section filters in registration order and flushes both', function (): void {
        $first = fn (Collection $fields, FieldResolutionContext $context): Collection => $fields;
        $second = fn (Collection $fields, FieldResolutionContext $context): Collection => $fields;
        $sections = fn (Collection $sections, FieldResolutionContext $context): Collection => $sections;

        CustomFieldsRegistry::filterFieldsUsing($first);
        CustomFieldsRegistry::filterFieldsUsing($second);
        CustomFieldsRegistry::filterSectionsUsing($sections);

        expect(CustomFieldsRegistry::fieldFilters())->toBe([$first, $second])
            ->and(CustomFieldsRegistry::sectionFilters())->toBe([$sections]);

        CustomFieldsRegistry::flushResolutionFilters();

        expect(CustomFieldsRegistry::fieldFilters())->toBe([])
            ->and(CustomFieldsRegistry::sectionFilters())->toBe([]);
    });

    it('exposes a readonly context with entity type, kind and optional record', function (): void {
        $context = new FieldResolutionContext(entityType: Post::class, kind: ResolutionKind::Table);

        expect($context->entityType)->toBe(Post::class)
            ->and($context->kind)->toBe(ResolutionKind::Table)
            ->and($context->record)->toBeNull()
            ->and(ResolutionKind::Exporter->value)->toBe('exporter');
    });
});

/**
 * Two sections on Post, two text fields each, plus one select field so the table has a filter.
 *
 * @return array{public: CustomFieldSection, internal: CustomFieldSection}
 */
function seedTwoSections(): array
{
    $public = CustomFieldSection::factory()->forEntityType(Post::class)->create(['name' => 'Public', 'code' => 'public', 'sort_order' => 1]);
    $internal = CustomFieldSection::factory()->forEntityType(Post::class)->create(['name' => 'Internal', 'code' => 'internal', 'sort_order' => 2]);

    CustomField::factory()->create(['custom_field_section_id' => $public->id, 'entity_type' => Post::class, 'name' => 'Headline', 'code' => 'headline', 'type' => 'text']);
    CustomField::factory()->ofType('select')->withOptions(['Yes', 'No'])->create(['custom_field_section_id' => $public->id, 'entity_type' => Post::class, 'name' => 'Featured', 'code' => 'featured']);
    CustomField::factory()->create(['custom_field_section_id' => $internal->id, 'entity_type' => Post::class, 'name' => 'Reviewer Notes', 'code' => 'reviewer_notes', 'type' => 'text']);
    CustomField::factory()->create(['custom_field_section_id' => $internal->id, 'entity_type' => Post::class, 'name' => 'Cost', 'code' => 'cost', 'type' => 'text']);

    return ['public' => $public, 'internal' => $internal];
}

/**
 * @param  Collection<int, Column|BaseFilter>  $components
 * @return array<int, string>
 */
function componentNames(Collection $components): array
{
    return $components->map(fn (Column|BaseFilter $component): string => $component->getName())->values()->all();
}

describe('table builder', function (): void {
    beforeEach(function (): void {
        seedTwoSections();
    });

    it('renders every field when no filter is registered', function (): void {
        expect(componentNames(CustomFields::table()->forModel(Post::class)->columns()))
            ->toBe(['custom_fields.headline', 'custom_fields.featured', 'custom_fields.reviewer_notes', 'custom_fields.cost'])
            ->and(componentNames(CustomFields::table()->forModel(Post::class)->filters()))
            ->toBe(['custom_fields.featured']);
    });

    it('drops fields rejected by a field filter from columns and filters', function (): void {
        CustomFieldsRegistry::filterFieldsUsing(fn (Collection $fields, FieldResolutionContext $context): Collection => $fields
            ->reject(fn (CustomField $field): bool => in_array($field->code, ['featured', 'cost'], true)));

        expect(componentNames(CustomFields::table()->forModel(Post::class)->columns()))
            ->toBe(['custom_fields.headline', 'custom_fields.reviewer_notes'])
            ->and(componentNames(CustomFields::table()->forModel(Post::class)->filters()))
            ->toBe([]);
    });

    it('drops every field of a section rejected by a section filter', function (): void {
        CustomFieldsRegistry::filterSectionsUsing(fn (Collection $sections, FieldResolutionContext $context): Collection => $sections
            ->reject(fn (CustomFieldSection $section): bool => $section->code === 'internal'));

        expect(componentNames(CustomFields::table()->forModel(Post::class)->columns()))
            ->toBe(['custom_fields.headline', 'custom_fields.featured']);
    });

    it('hands the table builder context to the filter', function (): void {
        $seen = null;

        CustomFieldsRegistry::filterFieldsUsing(function (Collection $fields, FieldResolutionContext $context) use (&$seen): Collection {
            $seen = $context;

            return $fields;
        });

        CustomFields::table()->forModel(Post::class)->columns();

        expect($seen)->toBeInstanceOf(FieldResolutionContext::class)
            ->and($seen->kind)->toBe(ResolutionKind::Table)
            ->and($seen->entityType)->toBe(Post::class)
            ->and($seen->record)->toBeNull();
    });

    it('composes several field filters in registration order', function (): void {
        CustomFieldsRegistry::filterFieldsUsing(fn (Collection $fields, FieldResolutionContext $context): Collection => $fields
            ->reject(fn (CustomField $field): bool => $field->code === 'headline'));
        CustomFieldsRegistry::filterFieldsUsing(fn (Collection $fields, FieldResolutionContext $context): Collection => $fields
            ->reject(fn (CustomField $field): bool => $field->code === 'cost'));

        expect(componentNames(CustomFields::table()->forModel(Post::class)->columns()))
            ->toBe(['custom_fields.featured', 'custom_fields.reviewer_notes']);
    });

    it('still evaluates a condition against a field the filter removed', function (): void {
        $public = CustomFieldSection::query()->where('code', 'public')->sole();
        $promoCopy = CustomField::factory()
            ->conditionallyVisible('headline', VisibilityOperator::EQUALS->value, 'Sale')
            ->create(['custom_field_section_id' => $public->id, 'entity_type' => Post::class, 'name' => 'Promo Copy', 'code' => 'promo_copy', 'type' => 'text']);

        CustomFieldsRegistry::filterFieldsUsing(fn (Collection $fields, FieldResolutionContext $context): Collection => $fields
            ->reject(fn (CustomField $field): bool => $field->code === 'headline'));

        $post = Post::factory()->create();
        $post->saveCustomFieldValue(CustomField::query()->where('code', 'headline')->sole(), 'Sale');
        $post->saveCustomFieldValue($promoCopy, 'Buy now');
        $post->refresh()->load('customFieldValues.customField.options');

        $builder = CustomFields::table()->forModel(Post::class);

        $column = $builder->columns()
            ->first(fn (Column $column): bool => $column->getName() === 'custom_fields.promo_copy');

        expect($column)->not->toBeNull()
            ->and($column->record($post)->formatState('Buy now'))->toBe('Buy now');

        $secondColumn = $builder->columns()
            ->first(fn (Column $column): bool => $column->getName() === 'custom_fields.promo_copy');

        expect($secondColumn)->not->toBeNull()
            ->and($secondColumn->record($post)->formatState('Buy now'))->toBe('Buy now');
    });

    it('returns no columns from a builder that was never given a model', function (): void {
        CustomFieldsRegistry::filterFieldsUsing(fn (Collection $fields, FieldResolutionContext $context): Collection => $fields);

        expect(CustomFields::table()->columns())->toBeEmpty();
    });
});

describe('table builder query efficiency', function (): void {
    it('loads field metadata once per builder even with a filter registered', function (): void {
        $section = CustomFieldSection::factory()->forEntityType(Post::class)->create(['name' => 'Public', 'code' => 'public']);

        CustomField::factory()->create(['custom_field_section_id' => $section->id, 'entity_type' => Post::class, 'name' => 'Headline', 'code' => 'headline', 'type' => 'text']);

        CustomFieldsRegistry::filterFieldsUsing(fn (Collection $fields, FieldResolutionContext $context): Collection => $fields);

        $customFieldsTable = config('custom-fields.database.table_names.custom_fields');

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            CustomFields::table()->forModel(Post::class)->columns();

            $customFieldQueries = array_filter(DB::getQueryLog(), static fn (array $entry): bool => str_contains($entry['query'], '"'.$customFieldsTable.'"')
                || str_contains($entry['query'], '`'.$customFieldsTable.'`'));

            expect($customFieldQueries)->toHaveCount(1);
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    });
});
