<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$definitionsMigration = fn (): Migration => require dirname(__DIR__, 3).'/database/migrations/create_relationship_definitions_table.php';
$linksMigration = fn (): Migration => require dirname(__DIR__, 3).'/database/migrations/create_relationship_links_table.php';

it('points the partial index at the prefixed table', function () use ($linksMigration): void {
    Schema::getConnection()->setTablePrefix('pfx_');

    $statement = collect(DB::pretend(function () use ($linksMigration): void {
        $linksMigration()->up();
    }))
        ->pluck('query')
        ->first(fn (string $query): bool => str_contains($query, 'cf_links_active_edge_unique'));

    expect($statement)->toContain('pfx_custom_field_links');
})->skip(
    fn (): bool => DB::connection()->getDriverName() === 'mysql',
    'The MySQL family has no partial index, so there is nothing to prefix.',
);

it('keys the slot columns off the custom field model, not the package key type', function () use ($definitionsMigration): void {
    config()->set('custom-fields.database.key_type', 'ulid');
    config()->set('custom-fields.database.table_names.custom_field_relationships', 'probe_relationships');

    $definitionsMigration()->up();

    $definitions = collect(Schema::getColumns('probe_relationships'))->keyBy('name');
    $customFieldKey = collect(Schema::getColumns('custom_fields'))->keyBy('name')->get('id');

    expect($definitions->get('from_field_id')['type_name'])->toBe($customFieldKey['type_name'])
        ->and($definitions->get('to_field_id')['type_name'])->toBe($customFieldKey['type_name'])
        ->and($definitions->get('id')['type_name'])->not->toBe($customFieldKey['type_name']);
});
