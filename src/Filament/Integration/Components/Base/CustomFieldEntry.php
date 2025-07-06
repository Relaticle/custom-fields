<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Base;

use Filament\Infolists\Components\Entry;
use Relaticle\CustomFields\Contracts\Components\InfolistEntryInterface;
use Relaticle\CustomFields\Filament\Integration\Components\Concerns\ConfiguresVisibility;
use Relaticle\CustomFields\Filament\Integration\Components\Concerns\HasCustomFieldState;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Abstract base class for all custom field infolist entry components
 * ABOUTME: Provides common functionality for infolist entry configuration and state display
 */
abstract class CustomFieldEntry implements InfolistEntryInterface
{
    use ConfiguresVisibility;
    use HasCustomFieldState;

    /**
     * Create and configure an infolist entry component
     *
     * @param  CustomField  $customField
     * @return Entry
     */
    public function makeInfolistEntry(CustomField $customField): Entry
    {
        // Create the specific entry component
        $entry = $this->createEntry($customField);

        // Configure common properties
        $this->configureEntry($entry, $customField);

        // Apply entry-specific configuration
        $this->applyEntrySpecificConfiguration($entry, $customField);

        return $entry;
    }

    /**
     * Create the specific Filament entry component
     *
     * @param  CustomField  $customField
     * @return Entry
     */
    abstract protected function createEntry(CustomField $customField): Entry;

    /**
     * Apply entry-specific configuration
     *
     * @param  Entry  $entry
     * @param  CustomField  $customField
     * @return void
     */
    abstract protected function applyEntrySpecificConfiguration(Entry $entry, CustomField $customField): void;

    /**
     * Configure common entry properties
     *
     * @param  Entry  $entry
     * @param  CustomField  $customField
     * @return void
     */
    protected function configureEntry(Entry $entry, CustomField $customField): void
    {
        // Basic configuration
        $entry->label($customField->name);

        // Configure state path
        // Note: Unlike form components, infolist entries don't have getStateUsing method
        // The state is resolved through the column name/path

        // Add helper text as tooltip if available from config
        $config = $customField->field_config ?? [];
        if (isset($config['help_text'])) {
            $entry->tooltip($config['help_text']);
        }

        // Get field configuration
        $config = $customField->field_config ?? [];

        // Configure visibility
        if (isset($config['hidden']) && $config['hidden']) {
            $entry->hidden();
        }

        // Configure column span
        if (isset($config['columnSpan'])) {
            $entry->columnSpan($config['columnSpan']);
        }

        // Configure hint
        if ($customField->hint) {
            $entry->hint($customField->hint);
        }

        // Configure icon
        if (isset($config['icon'])) {
            $entry->icon($config['icon']);
        }

        // Configure icon position
        if (isset($config['iconPosition'])) {
            $entry->iconPosition($config['iconPosition']);
        }

        // Configure weight/font
        if (isset($config['weight'])) {
            $entry->weight($config['weight']);
        }

        // Configure size
        if (isset($config['size'])) {
            $entry->size($config['size']);
        }

        // Configure color
        if (isset($config['color'])) {
            $entry->color($config['color']);
        }

        // Apply empty state configuration
        $this->configureEmptyState($entry, $customField);

        // Apply state formatting
        $this->configureStateFormatting($entry, $customField);
    }

    /**
     * Configure empty state display
     *
     * @param  Entry  $entry
     * @param  CustomField  $customField
     * @return void
     */
    protected function configureEmptyState(Entry $entry, CustomField $customField): void
    {
        $config = $customField->field_config ?? [];

        if (isset($config['placeholder'])) {
            $entry->placeholder($config['placeholder']);
        } elseif (isset($config['default'])) {
            $entry->default($config['default']);
        }
    }

    /**
     * Configure state formatting
     *
     * @param  Entry  $entry
     * @param  CustomField  $customField
     * @return void
     */
    protected function configureStateFormatting(Entry $entry, CustomField $customField): void
    {
        $config = $customField->field_config ?? [];

        // Apply custom state formatting if provided
        if (isset($config['formatStateUsing']) && is_callable($config['formatStateUsing'])) {
            $entry->formatStateUsing($config['formatStateUsing']);
        }
    }

    /**
     * Configure copyable functionality for appropriate field types
     *
     * @param  Entry  $entry
     * @param  CustomField  $customField
     * @return void
     */
    protected function configureCopyable(Entry $entry, CustomField $customField): void
    {
        $config = $customField->field_config ?? [];

        if (isset($config['copyable']) && $config['copyable']) {
            $entry->copyable();

            if (isset($config['copyMessage'])) {
                $entry->copyMessage($config['copyMessage']);
            }

            if (isset($config['copyMessageDuration'])) {
                $entry->copyMessageDuration($config['copyMessageDuration']);
            }
        }
    }

    /**
     * Configure badge display for appropriate field types
     *
     * @param  Entry  $entry
     * @param  CustomField  $customField
     * @return void
     */
    protected function configureBadge(Entry $entry, CustomField $customField): void
    {
        $config = $customField->field_config ?? [];

        if (isset($config['badge']) && $config['badge']) {
            $entry->badge();

            if (isset($config['colors']) && is_array($config['colors'])) {
                $entry->colors($config['colors']);
            }
        }
    }

    /**
     * Configure text limiting
     *
     * @param  Entry  $entry
     * @param  CustomField  $customField
     * @return void
     */
    protected function configureTextLimiting(Entry $entry, CustomField $customField): void
    {
        $config = $customField->field_config ?? [];

        if (isset($config['characterLimit'])) {
            $entry->limit($config['characterLimit']);
        } elseif (isset($config['words'])) {
            $entry->words($config['words']);
        }
    }

    /**
     * Create and configure entry with visibility rules
     *
     * @param  CustomField  $customField
     * @param  array<string>  $dependentFieldCodes
     * @return Entry
     */
    public function makeWithVisibility(CustomField $customField, array $dependentFieldCodes = []): Entry
    {
        $entry = $this->make($customField);

        // Apply visibility configuration if dependencies exist
        if (! empty($dependentFieldCodes) && ! empty($customField->visibility_rules)) {
            $visibilityClosure = $this->createVisibilityClosure(
                $customField->visibility_rules,
                $dependentFieldCodes
            );
            $entry->visible($visibilityClosure);
        }

        return $entry;
    }
    
    /**
     * Get the custom field state key for infolist entries
     *
     * @param  CustomField  $customField
     * @return string
     */
    protected function getCustomFieldStateKey(CustomField $customField): string
    {
        // Use a relationship path that Filament can resolve to the custom field value
        return "customFieldValues.{$customField->id}.value";
    }
}