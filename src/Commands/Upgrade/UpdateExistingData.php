<?php

namespace Relaticle\CustomFields\Commands\Upgrade;

use Closure;
use Illuminate\Support\Facades\DB;
use Relaticle\CustomFields\Commands\UpgradeCommand;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Enums\CustomFieldWidth;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;

class UpdateExistingData
{
    public function handle(UpgradeCommand $command, Closure $next): mixed
    {
        $isDryRun = $command->isDryRun();

        $command->info('--- Updating existing data...');

        // Fetch custom fields that require updating
        $customFields = CustomField::whereNull('custom_field_section_id')
            ->select('id', 'name', 'entity_type', 'tenant_id')
            ->get();

        if ($customFields->isEmpty()) {
            $command->info('No custom fields found that require updating.');

            return $next($command);
        }

        // Group custom fields by entity_type and tenant_id to minimize queries
        $customFieldsByGroup = $customFields->groupBy(function ($customField) {
            return $customField->entity_type . '|' . $customField->tenant_id;
        });

        // Begin database transaction
        DB::transaction(function () use ($command, $isDryRun, $customFields, $customFieldsByGroup): void {
            foreach ($customFieldsByGroup as $groupKey => $groupedCustomFields) {
                // Extract entity_type and tenant_id from group key
                [$entityType, $tenantId] = explode('|', $groupKey);

                // Use cache to store and retrieve sections to avoid duplicate queries
                static $sectionsCache = [];

                $sectionCacheKey = $entityType . '|' . $tenantId;

                if (! isset($sectionsCache[$sectionCacheKey])) {
                    // Get or create the section once per group
                    $sectionsCache[$sectionCacheKey] = CustomFieldSection::firstOrCreate(
                        [
                            'code' => 'new_section',
                            'entity_type' => $entityType,
                            'tenant_id' => $tenantId,
                        ],
                        [
                            'name' => __('custom-fields::custom-fields.section.default.new_section'),
                            'type' => CustomFieldSectionType::HEADLESS,
                        ]
                    );
                }

                $section = $sectionsCache[$sectionCacheKey];

                if ($isDryRun) {
                    foreach ($groupedCustomFields as $customField) {
                        $command->line("Custom field `{$customField->name}` will be moved to a new section.");
                    }

                    continue;
                }

                // Collect IDs of custom fields to update
                $customFieldIds = $groupedCustomFields->pluck('id')->toArray();

                // Perform bulk update on all custom fields in the group
                CustomField::whereIn('id', $customFieldIds)->update([
                    'custom_field_section_id' => $section->id,
                    'width' => CustomFieldWidth::_100,
                ]);
            }

            $command->info($customFields->count() . ' custom fields have been updated.');

            $command->newLine();

            $command->info('Existing data update step completed.');
        });

        return $next($command);
    }
}
