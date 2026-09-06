<?php

declare(strict_types=1);

use Relaticle\CustomFields\Enums\CustomFieldsFeature;
use Relaticle\CustomFields\FeatureSystem\FeatureConfigurator;
use Relaticle\CustomFields\FeatureSystem\FeatureManager;

it('configures and checks features correctly', function (): void {
    $config = FeatureConfigurator::configure()
        ->enable(
            CustomFieldsFeature::FIELD_CONDITIONAL_VISIBILITY,
            CustomFieldsFeature::UI_TABLE_COLUMNS,
            CustomFieldsFeature::SYSTEM_MANAGEMENT_INTERFACE
        )
        ->disable(
            CustomFieldsFeature::FIELD_ENCRYPTION,
            CustomFieldsFeature::SYSTEM_MULTI_TENANCY
        );

    config(['custom-fields.features' => $config]);

    expect(FeatureManager::isEnabled(CustomFieldsFeature::FIELD_CONDITIONAL_VISIBILITY))->toBeTrue();
    expect(FeatureManager::isEnabled(CustomFieldsFeature::UI_TABLE_COLUMNS))->toBeTrue();
    expect(FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_MANAGEMENT_INTERFACE))->toBeTrue();
    expect(FeatureManager::isEnabled(CustomFieldsFeature::FIELD_ENCRYPTION))->toBeFalse();
    expect(FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_MULTI_TENANCY))->toBeFalse();
});

it('handles feature enabling and disabling', function (): void {
    $config = FeatureConfigurator::configure()
        ->enable(CustomFieldsFeature::FIELD_ENCRYPTION)
        ->disable(CustomFieldsFeature::FIELD_ENCRYPTION); // Should override

    config(['custom-fields.features' => $config]);

    expect(FeatureManager::isEnabled(CustomFieldsFeature::FIELD_ENCRYPTION))->toBeFalse();
});

it('can toggle UI_FIELD_WIDTH_CONTROL feature', function (): void {
    $config = FeatureConfigurator::configure()
        ->enable(CustomFieldsFeature::UI_FIELD_WIDTH_CONTROL);

    config(['custom-fields.features' => $config]);

    expect(FeatureManager::isEnabled(CustomFieldsFeature::UI_FIELD_WIDTH_CONTROL))->toBeTrue();

    $config = FeatureConfigurator::configure()
        ->disable(CustomFieldsFeature::UI_FIELD_WIDTH_CONTROL);

    config(['custom-fields.features' => $config]);

    expect(FeatureManager::isEnabled(CustomFieldsFeature::UI_FIELD_WIDTH_CONTROL))->toBeFalse();
});

it('lists every feature flag explicitly in the shipped config', function (): void {
    $shipped = shippedFeatureConfigurator();

    $configured = (new ReflectionProperty(FeatureConfigurator::class, 'features'))->getValue($shipped);

    $cases = array_map(
        fn (CustomFieldsFeature $feature): string => $feature->value,
        CustomFieldsFeature::cases(),
    );

    expect(array_keys($configured))->toEqualCanonicalizing($cases);
});

it('ships the feature defaults reviewed for 4.0', function (): void {
    config(['custom-fields.features' => shippedFeatureConfigurator()]);

    $actual = [];

    foreach (CustomFieldsFeature::cases() as $feature) {
        $actual[$feature->value] = FeatureManager::isEnabled($feature);
    }

    expect($actual)->toEqual([
        CustomFieldsFeature::FIELD_CONDITIONAL_VISIBILITY->value => true,
        CustomFieldsFeature::FIELD_ENCRYPTION->value => true,
        CustomFieldsFeature::FIELD_OPTION_COLORS->value => true,
        CustomFieldsFeature::FIELD_CODE_AUTO_GENERATE->value => false,
        CustomFieldsFeature::FIELD_MULTI_VALUE->value => false,
        CustomFieldsFeature::FIELD_UNIQUE_VALUE->value => false,
        CustomFieldsFeature::FIELD_VALIDATION_RULES->value => true,
        CustomFieldsFeature::FIELD_DESCRIPTION->value => true,
        CustomFieldsFeature::FIELD_DESCRIPTION_POSITION->value => true,
        CustomFieldsFeature::MODEL_ATTRIBUTE_CONDITIONS->value => false,
        CustomFieldsFeature::SECTION_CONDITIONAL_VISIBILITY->value => true,
        CustomFieldsFeature::UI_TABLE_COLUMNS->value => true,
        CustomFieldsFeature::UI_TABLE_FILTERS->value => true,
        CustomFieldsFeature::UI_TOGGLEABLE_COLUMNS->value => true,
        CustomFieldsFeature::UI_TOGGLEABLE_COLUMNS_HIDDEN_DEFAULT->value => false,
        CustomFieldsFeature::UI_FIELD_WIDTH_CONTROL->value => true,
        CustomFieldsFeature::UI_SECTION_WIDTH_CONTROL->value => true,
        CustomFieldsFeature::SYSTEM_MANAGEMENT_INTERFACE->value => true,
        CustomFieldsFeature::SYSTEM_MULTI_TENANCY->value => false,
        CustomFieldsFeature::SYSTEM_SECTIONS->value => true,
    ]);
});
