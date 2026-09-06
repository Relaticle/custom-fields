<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Data\CustomFieldOptionSettingsData;
use Relaticle\CustomFields\Enums\OptionCategory;
use Relaticle\CustomFields\Models\CustomField;
use Spatie\LaravelData\Exceptions\CannotCastEnum;

dataset('option categories', fn (): array => array_map(
    fn (OptionCategory $category): array => [$category],
    OptionCategory::cases(),
));

function selectFieldForCategories(): CustomField
{
    return CustomField::factory()->ofType('select')->create();
}

it('treats completed and cancelled as terminal categories', function (): void {
    expect(OptionCategory::Unstarted->isTerminal())->toBeFalse()
        ->and(OptionCategory::Started->isTerminal())->toBeFalse()
        ->and(OptionCategory::Completed->isTerminal())->toBeTrue()
        ->and(OptionCategory::Cancelled->isTerminal())->toBeTrue();
});

it('round-trips a category through the stored option settings', function (OptionCategory $category): void {
    $option = selectFieldForCategories()->options()->create([
        'name' => 'Some option',
        'sort_order' => 0,
        'settings' => ['category' => $category->value],
    ]);

    expect($option->fresh()->settings->category)->toBe($category);
})->with('option categories');

it('stores the category as its backed value in the settings json', function (): void {
    $option = selectFieldForCategories()->options()->create([
        'name' => 'Closed Won',
        'sort_order' => 0,
        'settings' => new CustomFieldOptionSettingsData(category: OptionCategory::Completed),
    ]);

    $stored = json_decode($option->fresh()->getRawOriginal('settings'), true, flags: JSON_THROW_ON_ERROR);

    expect($stored['category'])->toBe('completed');
});

it('keeps the category null when an option is saved without one', function (): void {
    $option = selectFieldForCategories()->options()->create([
        'name' => 'Untagged',
        'sort_order' => 0,
    ]);

    expect($option->fresh()->settings->category)->toBeNull();
});

it('clears the category back to null', function (): void {
    $option = selectFieldForCategories()->options()->create([
        'name' => 'Done',
        'sort_order' => 0,
        'settings' => ['category' => OptionCategory::Completed->value],
    ]);

    $option->update(['settings' => ['category' => null]]);

    expect($option->fresh()->settings->category)->toBeNull();
});

it('fails validation on an unknown category', function (): void {
    expect(fn (): CustomFieldOptionSettingsData => CustomFieldOptionSettingsData::validateAndCreate([
        'category' => 'archived',
    ]))->toThrow(ValidationException::class);
});

it('refuses to store an unknown category', function (): void {
    $field = selectFieldForCategories();

    expect(fn () => $field->options()->create([
        'name' => 'Archived',
        'sort_order' => 0,
        'settings' => ['category' => 'archived'],
    ]))->toThrow(CannotCastEnum::class);

    expect(CustomFields::newOptionModel()->query()->count())->toBe(0);
});

it('returns the options of one category in sort order', function (): void {
    $field = selectFieldForCategories();
    $field->options()->createMany([
        ['name' => 'Won Back', 'sort_order' => 3, 'settings' => ['category' => 'completed']],
        ['name' => 'Closed Won', 'sort_order' => 2, 'settings' => ['category' => 'completed']],
        ['name' => 'Closed Lost', 'sort_order' => 4, 'settings' => ['category' => 'cancelled']],
        ['name' => 'Discovery', 'sort_order' => 1],
    ]);

    expect($field->optionsInCategory(OptionCategory::Completed)->pluck('name')->all())
        ->toBe(['Closed Won', 'Won Back'])
        ->and($field->optionsInCategory(OptionCategory::Cancelled)->pluck('name')->all())
        ->toBe(['Closed Lost'])
        ->and($field->optionsInCategory(OptionCategory::Started))->toBeEmpty()
        ->and($field->optionsInCategory(OptionCategory::Unstarted))->toBeEmpty();
});

it("keeps another field's options out of the category result", function (): void {
    $field = selectFieldForCategories();
    $field->options()->create(['name' => 'Closed Won', 'sort_order' => 1, 'settings' => ['category' => 'completed']]);

    $otherField = selectFieldForCategories();
    $otherField->options()->create(['name' => 'Done', 'sort_order' => 1, 'settings' => ['category' => 'completed']]);

    expect($field->optionsInCategory(OptionCategory::Completed)->pluck('name')->all())->toBe(['Closed Won']);
});

it('filters options by category through the query builder', function (): void {
    $field = selectFieldForCategories();
    $field->options()->createMany([
        ['name' => 'Closed Won', 'sort_order' => 1, 'settings' => ['category' => 'completed']],
        ['name' => 'Won Back', 'sort_order' => 2, 'settings' => ['category' => 'completed']],
        ['name' => 'Closed Lost', 'sort_order' => 3, 'settings' => ['category' => 'cancelled']],
        ['name' => 'Discovery', 'sort_order' => 4],
    ]);

    $completed = CustomFields::newOptionModel()->query()->whereCategory(OptionCategory::Completed)->get();

    expect($completed->pluck('name')->all())->toEqualCanonicalizing(['Closed Won', 'Won Back'])
        ->and(CustomFields::newOptionModel()->query()->whereCategory(OptionCategory::Cancelled)->count())->toBe(1)
        ->and(CustomFields::newOptionModel()->query()->whereCategory(OptionCategory::Unstarted)->count())->toBe(0);
});
