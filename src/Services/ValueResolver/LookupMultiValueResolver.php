<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services\ValueResolver;

use Illuminate\Database\Eloquent\Model;
use Relaticle\CustomFields\Contracts\ValueResolvers;
use Relaticle\CustomFields\Models\CustomField;
use Throwable;

final readonly class LookupMultiValueResolver implements ValueResolvers
{
    public function __construct(private LookupResolver $lookupResolver) {}

    /**
     * @throws Throwable
     */
    public function resolve(Model $record, CustomField $customField): array
    {
        $value = $record->getCustomFieldValue($customField) ?? [];
        $lookupValues = $this->lookupResolver->resolveLookupValues($value, $customField);

        return $lookupValues->isNotEmpty() ? $lookupValues->toArray() : [];
    }
}
