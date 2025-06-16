<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Models\CustomFieldValue;
use Relaticle\CustomFields\Tests\TestCase;

// Apply base test configuration to all tests
uses(TestCase::class, RefreshDatabase::class)->in(__DIR__);

// Group configurations
uses()->group('unit')->in('Unit');
uses()->group('feature')->in('Feature');
uses()->group('livewire')->in('Feature/Livewire');
uses()->group('filament')->in('Feature/Filament');

// Custom expectations for better test readability
expect()->extend('toBeCustomField', function () {
    return expect($this->value)->toBeInstanceOf(CustomField::class);
});

expect()->extend('toBeCustomFieldSection', function () {
    return expect($this->value)->toBeInstanceOf(CustomFieldSection::class);
});

expect()->extend('toBeCustomFieldValue', function () {
    return expect($this->value)->toBeInstanceOf(CustomFieldValue::class);
});

expect()->extend('toBeCustomFieldOption', function () {
    return expect($this->value)->toBeInstanceOf(CustomFieldOption::class);
});
