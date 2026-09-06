<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Observers;

use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Enums\CustomFieldsFeature;
use Relaticle\CustomFields\FeatureSystem\FeatureManager;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\Visibility\BackendVisibilityService;
use RuntimeException;

final class CustomFieldObserver
{
    /**
     * Prevent modification of protected attributes on system-defined fields.
     */
    public function updating(CustomField $customField): void
    {
        if (! $customField->getOriginal('system_defined')) {
            return;
        }

        if ($customField->isDirty(['name', 'code', 'type'])) {
            throw new RuntimeException('Cannot modify name, code, or type of system-defined fields.');
        }
    }

    /**
     * Clear field cache after create or update.
     */
    public function saved(CustomField $customField): void
    {
        BackendVisibilityService::clearCache($customField->entity_type);
    }

    public function deleted(CustomField $customField): void
    {
        BackendVisibilityService::clearCache($customField->entity_type);

        // Delete the custom field options
        $customField->options()->delete();

        // Delete the custom field values
        $customField->values()->delete();

        $this->unpairRelationshipSlots($customField);
    }

    /**
     * Losing one presentation slot leaves the definition and its edges intact: the partner
     * keeps reading them from its own side.
     */
    private function unpairRelationshipSlots(CustomField $customField): void
    {
        if (! FeatureManager::isEnabled(CustomFieldsFeature::SYSTEM_RELATIONSHIPS)) {
            return;
        }

        foreach (['from_field_id', 'to_field_id'] as $slot) {
            CustomFields::newRelationshipModel()
                ->newQuery()
                ->where($slot, $customField->getKey())
                ->update([$slot => null]);
        }
    }
}
