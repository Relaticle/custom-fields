<?php

declare(strict_types=1);

use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;

it('reads a field setting from the additional bag with a default', function (): void {
    $section = CustomFieldSection::factory()->forEntityType(Post::class)->create();
    $field = CustomField::factory()->create([
        'custom_field_section_id' => $section->id,
        'entity_type' => Post::class,
        'code' => 'cost',
        'name' => 'Cost',
        'type' => 'text',
        'settings' => ['additional' => ['hidden_in_panels' => ['portal'], 'nullable_flag' => null]],
    ]);

    expect($field->setting('hidden_in_panels'))->toBe(['portal'])
        ->and($field->setting('missing', []))->toBe([])
        ->and($field->setting('nullable_flag', 'fallback'))->toBeNull();
});

it('reads a section setting from the extra bag with a default', function (): void {
    $section = CustomFieldSection::factory()->forEntityType(Post::class)->create([
        'settings' => ['extra' => ['hidden_in_panels' => ['portal']]],
    ]);

    expect($section->setting('hidden_in_panels'))->toBe(['portal'])
        ->and($section->setting('missing', false))->toBeFalse();
});
