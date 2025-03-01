<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services\ValueResolver;

use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Contracts\ValueResolvers;
use Relaticle\CustomFields\Models\CustomField;

readonly class ValueResolver implements ValueResolvers
{
    public function __construct(
        private LookupMultiValueResolver $multiValueResolver,
        private LookupSingleValueResolver $singleValueResolver
    ) {}

    public function resolve(Model $record, CustomField $customField, bool $exportable = false): mixed
    {
        if (! $customField->type->isOptionable()) {
            $value = $record->getCustomFieldValue($customField);

            if ($exportable && $customField->type->isBoolean()) {
                return $value ? 'Yes' : 'No';
            }

            return $value;
        }

        if ($customField->type->hasMultipleValues()) {
            return $this->multiValueResolver->resolve($record, $customField);
        }

        return $this->singleValueResolver->resolve($record, $customField);
    }
}
