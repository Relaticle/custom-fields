<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Relaticle\CustomFields\Data\FieldSlotData;
use Relaticle\CustomFields\Data\RelationshipDefinitionData;
use Relaticle\CustomFields\Enums\RelationshipCardinality;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldLink;
use Relaticle\CustomFields\Models\CustomFieldRelationship;
use Relaticle\CustomFields\Services\Relationships\CreateRelationshipDefinition;
use Relaticle\CustomFields\Services\Relationships\DeleteRelationshipDefinition;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Models\User;

function authorship(): CustomFieldRelationship
{
    return app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'authorship',
        fromEntityType: (new Post)->getMorphClass(),
        toEntityType: (new User)->getMorphClass(),
        cardinality: RelationshipCardinality::ManyToOne,
        fromField: new FieldSlotData(name: 'Author', sectionId: sectionForEntity((new Post)->getMorphClass())->getKey()),
        toField: new FieldSlotData(name: 'Posts', sectionId: sectionForEntity((new User)->getMorphClass())->getKey()),
    ));
}

it('creates a paired definition with two record fields in one transaction', function (): void {
    $definition = authorship();

    expect($definition->cardinality)->toBe(RelationshipCardinality::ManyToOne)
        ->and($definition->fromField)->not->toBeNull()
        ->and($definition->fromField->type)->toBe('record')
        ->and($definition->fromField->code)->toBe('author')
        ->and($definition->fromField->entity_type)->toBe((new Post)->getMorphClass())
        ->and($definition->toField->entity_type)->toBe((new User)->getMorphClass())
        ->and($definition->toField->code)->toBe('posts')
        ->and(CustomField::query()->count())->toBe(2);
});

it('creates a one-way definition with a single field', function (): void {
    $definition = app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'referrer',
        fromEntityType: (new User)->getMorphClass(),
        toEntityType: (new User)->getMorphClass(),
        cardinality: RelationshipCardinality::ManyToOne,
        fromField: new FieldSlotData(name: 'Referred by', sectionId: sectionForEntity((new User)->getMorphClass())->getKey()),
    ));

    expect($definition->to_field_id)->toBeNull()
        ->and($definition->isHeadless())->toBeFalse()
        ->and(CustomField::query()->count())->toBe(1);
});

it('creates a headless definition with no fields', function (): void {
    $definition = app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'works_with',
        fromEntityType: (new User)->getMorphClass(),
        toEntityType: (new User)->getMorphClass(),
        cardinality: RelationshipCardinality::ManyToMany,
    ));

    expect($definition->isHeadless())->toBeTrue()
        ->and(CustomField::query()->count())->toBe(0);
});

it('points both slots at one field for a symmetric definition', function (): void {
    $definition = app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'spouse',
        fromEntityType: (new User)->getMorphClass(),
        toEntityType: (new User)->getMorphClass(),
        cardinality: RelationshipCardinality::OneToOne,
        isSymmetric: true,
        fromField: new FieldSlotData(name: 'Spouse', sectionId: sectionForEntity((new User)->getMorphClass())->getKey()),
    ));

    expect($definition->from_field_id)->toBe($definition->to_field_id)
        ->and($definition->directionFor($definition->fromField))->toBe(CustomFieldRelationship::DIRECTION_FROM)
        ->and(CustomField::query()->count())->toBe(1);
});

it('rejects a symmetric definition across two entity types', function (): void {
    app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'spouse',
        fromEntityType: (new Post)->getMorphClass(),
        toEntityType: (new User)->getMorphClass(),
        cardinality: RelationshipCardinality::OneToOne,
        isSymmetric: true,
        fromField: new FieldSlotData(name: 'Spouse'),
    ));
})->throws(InvalidArgumentException::class);

it('rejects a second slot on a symmetric definition', function (): void {
    app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'spouse',
        fromEntityType: (new User)->getMorphClass(),
        toEntityType: (new User)->getMorphClass(),
        cardinality: RelationshipCardinality::OneToOne,
        isSymmetric: true,
        fromField: new FieldSlotData(name: 'Spouse'),
        toField: new FieldSlotData(name: 'Spouse of'),
    ));
})->throws(InvalidArgumentException::class);

it('rejects a directional cardinality on a symmetric definition', function (): void {
    app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'sibling',
        fromEntityType: (new User)->getMorphClass(),
        toEntityType: (new User)->getMorphClass(),
        cardinality: RelationshipCardinality::OneToMany,
        isSymmetric: true,
        fromField: new FieldSlotData(name: 'Sibling'),
    ));
})->throws(InvalidArgumentException::class);

it('writes no field when the definition is rejected', function (): void {
    $create = fn (): CustomFieldRelationship => app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'spouse',
        fromEntityType: (new Post)->getMorphClass(),
        toEntityType: (new User)->getMorphClass(),
        cardinality: RelationshipCardinality::OneToOne,
        isSymmetric: true,
        fromField: new FieldSlotData(name: 'Spouse'),
    ));

    expect($create)->toThrow(InvalidArgumentException::class)
        ->and(CustomField::query()->withDeactivated()->count())->toBe(0)
        ->and(CustomFieldRelationship::query()->count())->toBe(0);
});

it('rejects an end that resolves to no model', function (): void {
    app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'authorship',
        fromEntityType: (new Post)->getMorphClass(),
        toEntityType: 'acme_ghosts',
        cardinality: RelationshipCardinality::ManyToOne,
    ));
})->throws(InvalidArgumentException::class);

it('rejects a code already used by another definition', function (): void {
    authorship();

    app(CreateRelationshipDefinition::class)->execute(new RelationshipDefinitionData(
        code: 'authorship',
        fromEntityType: (new Post)->getMorphClass(),
        toEntityType: (new User)->getMorphClass(),
        cardinality: RelationshipCardinality::ManyToMany,
    ));
})->throws(InvalidArgumentException::class);

it('unpairs on delete, keeping each field on its own one-way definition', function (): void {
    $definition = authorship();
    CustomFieldLink::factory()->create(['relationship_id' => $definition->getKey()]);

    app(DeleteRelationshipDefinition::class)->execute($definition, deleteFields: false);

    expect(CustomFieldRelationship::query()->count())->toBe(2)
        ->and(CustomFieldRelationship::query()->whereNotNull('from_field_id')->count())->toBe(1)
        ->and(CustomFieldRelationship::query()->whereNotNull('to_field_id')->count())->toBe(1)
        ->and(CustomFieldLink::query()->count())->toBe(0)
        ->and(CustomField::query()->count())->toBe(2);
});

it('deletes the slot fields when asked', function (): void {
    $definition = authorship();
    CustomFieldLink::factory()->create(['relationship_id' => $definition->getKey()]);

    app(DeleteRelationshipDefinition::class)->execute($definition, deleteFields: true);

    expect(CustomFieldRelationship::query()->count())->toBe(0)
        ->and(CustomFieldLink::query()->count())->toBe(0)
        ->and(CustomField::query()->withDeactivated()->count())->toBe(0);
});

it('keeps the definition and its edges when one slot field is deleted', function (): void {
    $definition = authorship();
    CustomFieldLink::factory()->create(['relationship_id' => $definition->getKey()]);

    $definition->toField->delete();

    expect($definition->refresh()->to_field_id)->toBeNull()
        ->and($definition->from_field_id)->not->toBeNull()
        ->and(CustomFieldLink::query()->count())->toBe(1);
});

it('refuses to move the ends of an existing definition', function (): void {
    $definition = authorship();

    $definition->update(['to_entity_type' => (new Post)->getMorphClass()]);
})->throws(RuntimeException::class);

it('allows a cardinality change on an existing definition', function (): void {
    $definition = authorship();

    $definition->update(['cardinality' => RelationshipCardinality::ManyToMany]);

    expect($definition->refresh()->cardinality)->toBe(RelationshipCardinality::ManyToMany);
});

it('stamps the tenant on the definition and both slot fields', function (): void {
    useTenantSchema(7);

    $definition = authorship();

    expect($definition->tenant_id)->toBe(7)
        ->and($definition->fromField->tenant_id)->toBe(7)
        ->and($definition->toField->tenant_id)->toBe(7);

    DB::table(config('custom-fields.database.table_names.custom_field_relationships'))
        ->where('id', $definition->getKey())
        ->update(['tenant_id' => 8]);

    expect(CustomFieldRelationship::query()->count())->toBe(0);
})->skip(
    fn (): bool => DB::connection()->getDriverName() === 'mysql',
    'MySQL commits DDL implicitly, so the added tenant columns would outlive the test transaction.',
);
