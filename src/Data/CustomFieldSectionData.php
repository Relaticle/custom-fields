<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Data;

use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class CustomFieldSectionData extends Data
{
    /**
     * Create a new instance of the CustomFieldData class.
     *
     * @param  string  $name  The name of the custom field.
     * @param  string  $code  The code of the custom field.
     * @return void
     */
    public function __construct(
        public string $name,
        public string $code,
        public string $entityType,
        public CustomFieldSectionType $type = CustomFieldSectionType::SECTION,
        public bool $active = true,
        public ?CustomFieldSectionSettingsData $settings = null
    ) {}
}
