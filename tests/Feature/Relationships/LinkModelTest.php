<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Models\CustomFieldLink;
use Relaticle\CustomFields\Models\CustomFieldRelationship;

it('persists an active edge and closes it without deleting', function (): void {
    $definition = CustomFieldRelationship::factory()->create();

    $link = CustomFieldLink::factory()->create([
        'relationship_id' => $definition->id,
        'from_entity_type' => 'post',
        'from_entity_id' => 1,
        'to_entity_type' => 'user',
        'to_entity_id' => 2,
    ]);

    expect(CustomFieldLink::query()->active()->count())->toBe(1)
        ->and($link->source)->toBe(CustomFieldLink::SOURCE_USER)
        ->and($link->relationship)->toBeSameModel($definition);

    $link->close(now());

    expect(CustomFieldLink::query()->active()->count())->toBe(0)
        ->and(CustomFieldLink::query()->count())->toBe(1)
        ->and($link->refresh()->active_until)->not->toBeNull();
});

it('refuses a duplicate active edge at the database level', function (): void {
    $definition = CustomFieldRelationship::factory()->create();
    $attributes = [
        'relationship_id' => $definition->id,
        'from_entity_type' => 'post',
        'from_entity_id' => 1,
        'to_entity_type' => 'user',
        'to_entity_id' => 2,
    ];

    CustomFieldLink::factory()->create($attributes);

    DB::transaction(function () use ($attributes): void {
        CustomFieldLink::factory()->create($attributes);
    });
})
    ->throws(QueryException::class)
    ->skip(
        fn (): bool => DB::connection()->getDriverName() === 'mysql',
        'The MySQL family has no partial index, so the writer is the only wall there.',
    );

it('allows re-linking after a close', function (): void {
    $definition = CustomFieldRelationship::factory()->create();
    $attributes = [
        'relationship_id' => $definition->id,
        'from_entity_type' => 'post',
        'from_entity_id' => 1,
        'to_entity_type' => 'user',
        'to_entity_id' => 2,
    ];

    CustomFieldLink::factory()->create($attributes)->close(now());

    expect(CustomFieldLink::factory()->create($attributes))->toBeInstanceOf(CustomFieldLink::class);
});

it('keeps a closed edge queryable as history', function (): void {
    $definition = CustomFieldRelationship::factory()->create();
    $closedAt = now()->subDay();

    $link = CustomFieldLink::factory()->create(['relationship_id' => $definition->id]);
    $link->close($closedAt);

    expect(CustomFieldLink::query()->whereNotNull('active_until')->count())->toBe(1)
        ->and($link->refresh()->active_until?->toDateTimeString())->toBe($closedAt->toDateTimeString())
        ->and($link->active_from)->not->toBeNull();
});

it('resolves the link model through the swap registry', function (): void {
    expect(CustomFields::linkModel())->toBe(CustomFieldLink::class)
        ->and(CustomFields::newLinkModel())->toBeInstanceOf(CustomFieldLink::class)
        ->and(CustomFields::newLinkModel()->getTable())
        ->toBe(config('custom-fields.database.table_names.custom_field_links'));
});
