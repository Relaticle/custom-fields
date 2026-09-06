<?php

declare(strict_types=1);

use Relaticle\CustomFields\Data\CustomFieldSettingsData;
use Relaticle\CustomFields\Enums\CustomFieldsFeature;
use Relaticle\CustomFields\Enums\RelationshipCardinality;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldLink;
use Relaticle\CustomFields\Models\CustomFieldRelationship;
use Relaticle\CustomFields\Models\CustomFieldValue;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;

function legacyRecordField(bool $allowMultiple = true, string $code = 'legacy_related'): CustomField
{
    registerPostLookupEntity();

    return CustomField::factory()->create([
        'code' => $code,
        'name' => 'Legacy Related',
        'type' => 'record',
        'entity_type' => (new Post)->getMorphClass(),
        'lookup_type' => (new Post)->getMorphClass(),
        'settings' => new CustomFieldSettingsData(allow_multiple: $allowMultiple),
        'custom_field_section_id' => sectionForEntity((new Post)->getMorphClass())->getKey(),
    ]);
}

it('migrates json_value arrays into definitions and links', function (): void {
    $field = legacyRecordField();
    [$first, $second] = Post::factory()->count(2)->create();
    $post = Post::factory()->create(['custom_fields' => [$field->code => [$first->getKey(), $second->getKey()]]]);

    expect(CustomFieldValue::query()->where('custom_field_id', $field->getKey())->count())->toBe(1);

    $this->artisan('custom-fields:upgrade', ['--force' => true])
        ->expectsOutputToContain('Migrating record field')
        ->assertSuccessful();

    $definition = CustomFieldRelationship::query()->sole();
    $migrated = $field->fresh();

    expect($definition->code)->toBe($field->code)
        ->and($definition->from_field_id)->toEqual($field->getKey())
        ->and($definition->to_field_id)->toBeNull()
        ->and($definition->cardinality)->toBe(RelationshipCardinality::ManyToMany)
        ->and($definition->from_entity_type)->toBe((new Post)->getMorphClass())
        ->and($definition->to_entity_type)->toBe((new Post)->getMorphClass())
        ->and(CustomFieldLink::query()->active()->pluck('source')->all())->toBe(['migration', 'migration'])
        ->and(CustomFieldLink::query()->active()->orderBy('sort_order')->pluck('sort_order')->all())->toBe([0, 1])
        ->and(CustomFieldValue::query()->where('custom_field_id', $field->getKey())->count())->toBe(1)
        ->and($post->fresh()->getCustomFieldValue($migrated))->toBe([$first->getKey(), $second->getKey()]);
});

it('gives a single-value record field a many to one definition', function (): void {
    $field = legacyRecordField(allowMultiple: false);
    $target = Post::factory()->create();
    Post::factory()->create(['custom_fields' => [$field->code => [$target->getKey()]]]);

    $this->artisan('custom-fields:upgrade', ['--force' => true])->assertSuccessful();

    expect(CustomFieldRelationship::query()->sole()->cardinality)->toBe(RelationshipCardinality::ManyToOne);
});

it('writes no link for a record value that was cleared', function (): void {
    $field = legacyRecordField();
    $target = Post::factory()->create();
    $post = Post::factory()->create(['custom_fields' => [$field->code => [$target->getKey()]]]);
    $post->update(['custom_fields' => [$field->code => []]]);

    $this->artisan('custom-fields:upgrade', ['--force' => true])->assertSuccessful();

    expect(CustomFieldLink::query()->count())->toBe(0)
        ->and(CustomFieldRelationship::query()->count())->toBe(1)
        ->and($post->fresh()->getCustomFieldValue($field->fresh()))->toBe([]);
});

it('reports the migration in dry-run mode and writes nothing', function (): void {
    $field = legacyRecordField();
    $target = Post::factory()->create();
    Post::factory()->create(['custom_fields' => [$field->code => [$target->getKey()]]]);

    $this->artisan('custom-fields:upgrade', ['--dry-run' => true])
        ->expectsOutputToContain('would be created')
        ->assertSuccessful();

    expect(CustomFieldLink::query()->count())->toBe(0)
        ->and(CustomFieldRelationship::query()->count())->toBe(0)
        ->and(CustomFieldValue::query()->where('custom_field_id', $field->getKey())->count())->toBe(1);
});

it('creates nothing twice across reruns', function (): void {
    $field = legacyRecordField();
    [$first, $second] = Post::factory()->count(2)->create();
    Post::factory()->create(['custom_fields' => [$field->code => [$first->getKey(), $second->getKey()]]]);

    $this->artisan('custom-fields:upgrade', ['--force' => true])->assertSuccessful();
    $this->artisan('custom-fields:upgrade', ['--force' => true])->assertSuccessful();

    expect(CustomFieldRelationship::query()->count())->toBe(1)
        ->and(CustomFieldLink::query()->count())->toBe(2);
});

it('migrates the values a field with a definition still holds', function (): void {
    $field = legacyRecordField();
    $first = Post::factory()->create();
    Post::factory()->create(['custom_fields' => [$field->code => [$first->getKey()]]]);

    $this->artisan('custom-fields:upgrade', ['--force' => true])->assertSuccessful();

    $leftover = Post::factory()->create();
    $second = Post::factory()->create();

    CustomFieldValue::query()->create([
        'entity_type' => $leftover->getMorphClass(),
        'entity_id' => $leftover->getKey(),
        'custom_field_id' => $field->getKey(),
        'json_value' => [$second->getKey()],
    ]);

    $this->artisan('custom-fields:upgrade', ['--force' => true])->assertSuccessful();

    expect(CustomFieldRelationship::query()->count())->toBe(1)
        ->and(CustomFieldLink::query()->active()->count())->toBe(2)
        ->and($leftover->fresh()->getCustomFieldValue($field->fresh()))->toBe([$second->getKey()]);
});

it('keeps the migrated value rows until the purge is asked for', function (): void {
    $field = legacyRecordField();
    $target = Post::factory()->create();
    Post::factory()->create(['custom_fields' => [$field->code => [$target->getKey()]]]);

    $this->artisan('custom-fields:upgrade', ['--force' => true])
        ->expectsOutputToContain('Skipping: purge-record-values')
        ->assertSuccessful();

    expect(CustomFieldValue::query()->where('custom_field_id', $field->getKey())->count())->toBe(1);
});

it('purges the migrated value rows when asked, leaving the links alone', function (): void {
    $field = legacyRecordField();
    $target = Post::factory()->create();
    $post = Post::factory()->create(['custom_fields' => [$field->code => [$target->getKey()]]]);

    $this->artisan('custom-fields:upgrade', ['--force' => true])->assertSuccessful();
    $this->artisan('custom-fields:upgrade', ['--force' => true, '--purge' => true])
        ->expectsOutputToContain('Purge Migrated Record Values')
        ->assertSuccessful();

    expect(CustomFieldValue::query()->where('custom_field_id', $field->getKey())->count())->toBe(0)
        ->and(CustomFieldLink::query()->active()->count())->toBe(1)
        ->and($post->fresh()->getCustomFieldValue($field->fresh()))->toBe([$target->getKey()]);
});

it('deletes nothing in a dry-run purge', function (): void {
    $field = legacyRecordField();
    $target = Post::factory()->create();
    Post::factory()->create(['custom_fields' => [$field->code => [$target->getKey()]]]);

    $this->artisan('custom-fields:upgrade', ['--force' => true])->assertSuccessful();
    $this->artisan('custom-fields:upgrade', ['--dry-run' => true, '--purge' => true])
        ->expectsOutputToContain('would be deleted')
        ->assertSuccessful();

    expect(CustomFieldValue::query()->where('custom_field_id', $field->getKey())->count())->toBe(1);
});

it('refuses to purge while a record field still has no definition', function (): void {
    $field = legacyRecordField();
    $target = Post::factory()->create();
    Post::factory()->create(['custom_fields' => [$field->code => [$target->getKey()]]]);

    $this->artisan('custom-fields:upgrade', [
        '--force' => true,
        '--purge' => true,
        '--skip' => 'migrate-record-links',
    ])
        ->expectsOutputToContain('run the Migrate Record Links step first')
        ->assertFailed();

    expect(CustomFieldValue::query()->where('custom_field_id', $field->getKey())->count())->toBe(1);
});

it('warns about record links still in json_value while the run migrates them', function (): void {
    $field = legacyRecordField();
    $target = Post::factory()->create();
    Post::factory()->create(['custom_fields' => [$field->code => [$target->getKey()]]]);

    $this->artisan('custom-fields:upgrade', ['--force' => true])
        ->expectsOutputToContain('links still in json_value, migrating below')
        ->assertSuccessful();
});

it('fails validation when the record-links step is skipped and links are still in json_value', function (): void {
    $field = legacyRecordField();
    $target = Post::factory()->create();
    Post::factory()->create(['custom_fields' => [$field->code => [$target->getKey()]]]);

    $this->artisan('custom-fields:upgrade', ['--force' => true, '--skip' => 'migrate-record-links'])
        ->expectsOutputToContain('record links still in json_value')
        ->assertFailed();

    expect(CustomFieldLink::query()->count())->toBe(0);
});

it('fails validation while the relationship tables are missing', function (): void {
    config()->set('custom-fields.database.table_names.custom_field_links', 'not_a_links_table');

    $this->artisan('custom-fields:upgrade', ['--force' => true])
        ->expectsOutputToContain('Table not_a_links_table: MISSING')
        ->assertFailed();
});

it('validates the schema without the relationship tables while the feature is off', function (): void {
    config('custom-fields.features')->disable(CustomFieldsFeature::SYSTEM_RELATIONSHIPS);
    config()->set('custom-fields.database.table_names.custom_field_links', 'not_a_links_table');

    $this->artisan('custom-fields:upgrade', ['--force' => true])
        ->expectsOutputToContain('Skipping: purge-record-values')
        ->assertSuccessful();
});
