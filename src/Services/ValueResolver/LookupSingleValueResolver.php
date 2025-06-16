<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services\ValueResolver;

use Relaticle\CustomFields\Contracts\ValueResolvers;
use Relaticle\CustomFields\Models\CustomField;

final readonly class LookupSingleValueResolver implements ValueResolvers
{
    public function __construct(private LookupResolver $lookupResolver) {}

    public function resolve($record, CustomField $customField, bool $exportable = false): string
    {
        $value = $record->getCustomFieldValue($customField);
        $lookupValue = $this->lookupResolver->resolveLookupValues([$value], $customField)->first();

        return (string) $lookupValue;
    }
}
