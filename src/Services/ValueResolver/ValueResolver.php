<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services\ValueResolver;

use Relaticle\CustomFields\Contracts\ValueResolvers;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;

readonly class ValueResolver implements ValueResolvers
{
    public function __construct(
        private LookupMultiValueResolver $multiValueResolver,
        private LookupSingleValueResolver $singleValueResolver
    ) {}

    public function resolve(HasCustomFields $record, CustomField $customField, bool $exportable = false): mixed
    {
        if (! $customField->isChoiceField()) {
            $value = $record->getCustomFieldValue($customField);

            if ($exportable && in_array($customField->type, ['checkbox', 'toggle'])) {
                return $value ? 'Yes' : 'No';
            }

            return $value;
        }

        if ($customField->isMultiChoiceField()) {
            return $this->multiValueResolver->resolve($record, $customField);
        }

        return $this->singleValueResolver->resolve($record, $customField);
    }
}
