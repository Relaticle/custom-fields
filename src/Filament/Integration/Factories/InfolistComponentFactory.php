<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Factories;

use Filament\Infolists\Components\Entry;
use Relaticle\CustomFields\Contracts\Components\InfolistEntryInterface;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Factory for creating infolist entries from custom field definitions
 * ABOUTME: Maps field types to infolist entry classes and configures display properties
 */
class InfolistComponentFactory extends AbstractComponentFactory
{
    /**
     * Get the component class for a specific field type
     *
     * @param  string  $fieldType
     * @return class-string|null
     */
    public function getComponentClass(string $fieldType): ?string
    {
        // Check custom components first
        if (isset($this->customComponents[$fieldType])) {
            return $this->customComponents[$fieldType];
        }

        // Get from registry
        return $this->componentRegistry->getInfolistEntry($fieldType);
    }

    /**
     * Get the expected interface that components must implement
     *
     * @return class-string
     */
    protected function getExpectedInterface(): string
    {
        return InfolistEntryInterface::class;
    }

    /**
     * Configure the component with field-specific settings
     *
     * @param  mixed  $componentInstance
     * @param  CustomField  $field
     * @return Entry
     */
    protected function configureComponent(mixed $componentInstance, CustomField $field): Entry
    {
        /** @var InfolistEntryInterface $componentInstance */
        $entry = $componentInstance->make($field);

        // Apply common entry configuration
        $this->applyCommonConfiguration($entry, $field);

        // Apply field-specific configuration
        $this->applyFieldSpecificConfiguration($entry, $field);

        return $entry;
    }

    /**
     * Apply common configuration to all infolist entries
     *
     * @param  Entry  $entry
     * @param  CustomField  $field
     * @return void
     */
    protected function applyCommonConfiguration(Entry $entry, CustomField $field): void
    {
        $entry->label($field->label);

        // Add helper text as tooltip if available
        if ($field->help_text) {
            $entry->tooltip($field->help_text);
        }

        // Get field configuration
        $config = $field->field_config ?? [];

        // Configure visibility
        if (isset($config['hidden'])) {
            $entry->hidden($config['hidden']);
        }

        // Configure column span
        if (isset($config['columnSpan'])) {
            $entry->columnSpan($config['columnSpan']);
        }

        // Configure hint
        if ($field->hint) {
            $entry->hint($field->hint);
        }

        // Configure icon
        if (isset($config['icon'])) {
            $entry->icon($config['icon']);
        }

        // Configure icon position
        if (isset($config['iconPosition'])) {
            $entry->iconPosition($config['iconPosition']);
        }
    }

    /**
     * Apply field type specific configuration
     *
     * @param  Entry  $entry
     * @param  CustomField  $field
     * @return void
     */
    protected function applyFieldSpecificConfiguration(Entry $entry, CustomField $field): void
    {
        $config = $field->field_config ?? [];

        // Configure date/time formatting
        if (in_array($field->type->value, ['date', 'datetime', 'time'])) {
            if (isset($config['dateFormat'])) {
                $entry->dateTime($config['dateFormat']);
            }
            if (isset($config['timezone'])) {
                $entry->timezone($config['timezone']);
            }
            if (isset($config['since']) && $config['since']) {
                $entry->since();
            }
        }

        // Configure numeric formatting
        if (in_array($field->type->value, ['number', 'currency'])) {
            if (isset($config['decimalPlaces'])) {
                $entry->numeric($config['decimalPlaces']);
            }
            if ($field->type->value === 'currency' && isset($config['currency'])) {
                $entry->money($config['currency']);
            }
        }

        // Configure boolean display
        if (in_array($field->type->value, ['boolean', 'toggle'])) {
            if (isset($config['trueLabel'])) {
                $entry->trueLabel($config['trueLabel']);
            }
            if (isset($config['falseLabel'])) {
                $entry->falseLabel($config['falseLabel']);
            }
            if (isset($config['trueIcon'])) {
                $entry->trueIcon($config['trueIcon']);
            }
            if (isset($config['falseIcon'])) {
                $entry->falseIcon($config['falseIcon']);
            }
            if (isset($config['trueColor'])) {
                $entry->trueColor($config['trueColor']);
            }
            if (isset($config['falseColor'])) {
                $entry->falseColor($config['falseColor']);
            }
        }

        // Configure badge display for select fields
        if (in_array($field->type->value, ['select', 'multiselect', 'tags'])) {
            if (isset($config['badge']) && $config['badge']) {
                $entry->badge();
            }
            if (isset($config['colors'])) {
                $entry->colors($config['colors']);
            }
            // Configure separator for multi-value fields
            if (in_array($field->type->value, ['multiselect', 'tags']) && isset($config['separator'])) {
                $entry->separator($config['separator']);
            }
        }

        // Configure color display
        if ($field->type->value === 'color') {
            if (isset($config['copyable'])) {
                $entry->copyable($config['copyable']);
            }
            if (isset($config['copyMessage'])) {
                $entry->copyMessage($config['copyMessage']);
            }
        }

        // Configure character limit for text fields
        if (in_array($field->type->value, ['text', 'textarea', 'richtext', 'markdown'])) {
            if (isset($config['characterLimit'])) {
                $entry->limit($config['characterLimit']);
            }
            if (isset($config['words'])) {
                $entry->words($config['words']);
            }
        }

        // Configure HTML rendering
        if (in_array($field->type->value, ['richtext', 'markdown'])) {
            if (isset($config['html']) && $config['html']) {
                $entry->html();
            }
            if (isset($config['markdown']) && $config['markdown']) {
                $entry->markdown();
            }
            if (isset($config['prose']) && $config['prose']) {
                $entry->prose();
            }
        }

        // Configure URL display
        if ($field->type->value === 'url') {
            if (isset($config['openExternally']) && $config['openExternally']) {
                $entry->openUrlInNewTab();
            }
            if (isset($config['copyable'])) {
                $entry->copyable($config['copyable']);
            }
        }

        // Configure email display
        if ($field->type->value === 'email') {
            if (isset($config['copyable'])) {
                $entry->copyable($config['copyable']);
            }
            if (isset($config['mailto']) && $config['mailto']) {
                $entry->url(fn ($state) => "mailto:{$state}");
            }
        }

        // Configure phone display
        if ($field->type->value === 'tel') {
            if (isset($config['copyable'])) {
                $entry->copyable($config['copyable']);
            }
            if (isset($config['tel']) && $config['tel']) {
                $entry->url(fn ($state) => "tel:{$state}");
            }
        }

        // Configure file display
        if ($field->type->value === 'file') {
            if (isset($config['downloadable']) && $config['downloadable']) {
                $entry->downloadable();
            }
        }

        // Configure default empty state
        if (isset($config['placeholder'])) {
            $entry->placeholder($config['placeholder']);
        } elseif (isset($config['default'])) {
            $entry->default($config['default']);
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

        // Configure state formatting
        if (isset($config['formatStateUsing'])) {
            $entry->formatStateUsing($config['formatStateUsing']);
        }
    }
}