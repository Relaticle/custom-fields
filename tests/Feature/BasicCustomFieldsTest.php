<?php

declare(strict_types=1);

use Relaticle\CustomFields\Data\CustomFieldSectionSettingsData;
use Relaticle\CustomFields\Data\CustomFieldSettingsData;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Tests\Models\User;

test('can create custom field section', function () {
    $section = CustomFieldSection::create([
        'name' => 'Test Section',
        'code' => 'test_section',
        'entity_type' => User::class,
        'type' => CustomFieldSectionType::SECTION,
        'settings' => new CustomFieldSectionSettingsData,
        'sort_order' => 1,
        'active' => true,
        'system_defined' => false,
    ]);

    expect($section)->toBeInstanceOf(CustomFieldSection::class)
        ->and($section->name)->toBe('Test Section')
        ->and($section->entity_type)->toBe(User::class);
});

test('can create custom field', function () {
    $section = CustomFieldSection::create([
        'name' => 'Test Section',
        'code' => 'test_section',
        'entity_type' => User::class,
        'type' => CustomFieldSectionType::SECTION,
        'settings' => new CustomFieldSectionSettingsData,
        'sort_order' => 1,
        'active' => true,
        'system_defined' => false,
    ]);

    $field = CustomField::create([
        'custom_field_section_id' => $section->id,
        'name' => 'TestField',
        'code' => 'test_field',
        'type' => CustomFieldType::TEXT,
        'entity_type' => User::class,
        'settings' => new CustomFieldSettingsData,
        'validation_rules' => [],
        'sort_order' => 1,
        'active' => true,
        'system_defined' => false,
    ]);

    expect($field)->toBeInstanceOf(CustomField::class)
        ->and($field->name)->toBe('TestField')
        ->and($field->type)->toBe(CustomFieldType::TEXT)
        ->and($field->custom_field_section_id)->toBe($section->id);
});

test('can save and retrieve custom field values', function () {
    $section = CustomFieldSection::create([
        'name' => 'Test Section',
        'code' => 'test_section',
        'entity_type' => User::class,
        'type' => CustomFieldSectionType::SECTION,
        'settings' => new CustomFieldSectionSettingsData,
        'sort_order' => 1,
        'active' => true,
        'system_defined' => false,
    ]);

    $field = CustomField::create([
        'custom_field_section_id' => $section->id,
        'name' => 'TestField',
        'code' => 'test_field',
        'type' => CustomFieldType::TEXT,
        'entity_type' => User::class,
        'settings' => new CustomFieldSettingsData,
        'validation_rules' => [],
        'sort_order' => 1,
        'active' => true,
        'system_defined' => false,
    ]);

    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // Save custom field value
    $user->saveCustomFieldValue($field, 'Test Value');

    // Retrieve custom field value
    $value = $user->getCustomFieldValue($field);
    expect($value)->toBe('Test Value');
});

test('user model implements HasCustomFields interface', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(\Relaticle\CustomFields\Models\Contracts\HasCustomFields::class)
        ->and(method_exists($user, 'customFields'))->toBeTrue()
        ->and(method_exists($user, 'customFieldValues'))->toBeTrue()
        ->and(method_exists($user, 'getCustomFieldValue'))->toBeTrue()
        ->and(method_exists($user, 'saveCustomFieldValue'))->toBeTrue();
});
