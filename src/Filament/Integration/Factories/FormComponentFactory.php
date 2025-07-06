<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Factories;

use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Contracts\Components\FormComponentInterface;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Factory for creating form components from custom field definitions
 * ABOUTME: Maps field types to form component classes and configures common properties
 */
class FormComponentFactory extends AbstractComponentFactory
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
        return $this->componentRegistry->getFormComponent($fieldType);
    }

    /**
     * Get the expected interface that components must implement
     *
     * @return class-string
     */
    protected function getExpectedInterface(): string
    {
        return FormComponentInterface::class;
    }

    /**
     * Configure the component with field-specific settings
     *
     * @param  mixed  $componentInstance
     * @param  CustomField  $field
     * @return Field
     */
    protected function configureComponent(mixed $componentInstance, CustomField $field): Field
    {
        /** @var FormComponentInterface $componentInstance */
        $component = $componentInstance->make($field);

        // Apply common field configuration
        $this->applyCommonConfiguration($component, $field);

        // Apply field-specific configuration
        $this->applyFieldSpecificConfiguration($component, $field);

        return $component;
    }

    /**
     * Apply common configuration to all form components
     *
     * @param  Field  $component
     * @param  CustomField  $field
     * @return void
     */
    protected function applyCommonConfiguration(Field $component, CustomField $field): void
    {
        $component
            ->label($field->label)
            ->helperText($field->help_text)
            ->required($field->is_required)
            ->disabled($field->is_readonly)
            ->default($field->default_value);

        // Add placeholder if configured
        if ($field->placeholder) {
            $component->placeholder($field->placeholder);
        }

        // Add hint if configured
        if ($field->hint) {
            $component->hint($field->hint);
        }

        // Configure validation rules
        if ($field->validation_rules && is_array($field->validation_rules)) {
            $component->rules($field->validation_rules);
        }
    }

    /**
     * Apply field type specific configuration
     *
     * @param  Field  $component
     * @param  CustomField  $field
     * @return void
     */
    protected function applyFieldSpecificConfiguration(Field $component, CustomField $field): void
    {
        // Get field configuration
        $config = $field->field_config ?? [];

        // Apply min/max length for text fields
        if (in_array($field->type->value, ['text', 'textarea', 'email', 'url', 'tel'])) {
            if (isset($config['minLength'])) {
                $component->minLength($config['minLength']);
            }
            if (isset($config['maxLength'])) {
                $component->maxLength($config['maxLength']);
            }
        }

        // Apply min/max values for numeric fields
        if (in_array($field->type->value, ['number', 'currency'])) {
            if (isset($config['min'])) {
                $component->minValue($config['min']);
            }
            if (isset($config['max'])) {
                $component->maxValue($config['max']);
            }
            if (isset($config['step'])) {
                $component->step($config['step']);
            }
        }

        // Apply date constraints
        if (in_array($field->type->value, ['date', 'datetime'])) {
            if (isset($config['minDate'])) {
                $component->minDate($config['minDate']);
            }
            if (isset($config['maxDate'])) {
                $component->maxDate($config['maxDate']);
            }
            if (isset($config['format'])) {
                $component->format($config['format']);
            }
            if (isset($config['displayFormat'])) {
                $component->displayFormat($config['displayFormat']);
            }
        }

        // Configure file upload settings
        if ($field->type->value === 'file') {
            if (isset($config['acceptedFileTypes'])) {
                $component->acceptedFileTypes($config['acceptedFileTypes']);
            }
            if (isset($config['maxSize'])) {
                $component->maxSize($config['maxSize']);
            }
            if (isset($config['multiple'])) {
                $component->multiple($config['multiple']);
            }
        }

        // Configure select/multiselect options
        if (in_array($field->type->value, ['select', 'multiselect', 'radio', 'checkboxlist', 'togglebuttons'])) {
            if ($field->options && $field->options->isNotEmpty()) {
                $options = $field->options->pluck('label', 'value')->toArray();
                $component->options($options);
            }
            
            // Configure searchable for select fields
            if (in_array($field->type->value, ['select', 'multiselect']) && isset($config['searchable'])) {
                $component->searchable($config['searchable']);
            }
        }

        // Configure textarea rows
        if ($field->type->value === 'textarea' && isset($config['rows'])) {
            $component->rows($config['rows']);
        }

        // Configure rich editor
        if ($field->type->value === 'richtext') {
            if (isset($config['toolbarButtons'])) {
                $component->toolbarButtons($config['toolbarButtons']);
            }
            if (isset($config['fileAttachmentsDirectory'])) {
                $component->fileAttachmentsDirectory($config['fileAttachmentsDirectory']);
            }
        }

        // Configure color picker
        if ($field->type->value === 'color' && isset($config['format'])) {
            $component->format($config['format']);
        }
    }
}