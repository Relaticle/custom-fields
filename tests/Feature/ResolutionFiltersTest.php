<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Relaticle\CustomFields\CustomFields as CustomFieldsRegistry;
use Relaticle\CustomFields\Enums\ResolutionKind;
use Relaticle\CustomFields\Filament\Integration\Builders\FieldResolutionContext;
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
