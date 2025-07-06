<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Base;

use Filament\Forms\Components\Field;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Contracts\Components\FormComponentInterface;
use Relaticle\CustomFields\Filament\Integration\Components\Concerns\ConfiguresVisibility;
use Relaticle\CustomFields\Filament\Integration\Components\Concerns\HasCustomFieldState;
use Relaticle\CustomFields\Models\CustomField;

/**
 * ABOUTME: Abstract base class for all custom field form input components
 * ABOUTME: Provides common functionality for form field configuration and state management
 */
abstract class CustomFieldInput implements FormComponentInterface
{
    use ConfiguresVisibility;
    use HasCustomFieldState;

    /**
     * Create and configure a form field component
     *
     * @param  CustomField  $customField
     * @param  array<string>  $dependentFieldCodes
     * @param  Collection<int, CustomField>|null  $allFields
     * @return Field
     */
    public function make(CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        // Create the specific field component
        $field = $this->createField($customField);

        // Configure common properties
        $this->configureField($field, $customField);

        // Apply field-specific configuration
        $this->applyFieldSpecificConfiguration($field, $customField);

        // Apply visibility configuration if dependencies exist
        if (! empty($dependentFieldCodes)) {
            $this->configureVisibility($field, $customField, $dependentFieldCodes);
        }

        return $field;
    }

    /**
     * Create the specific Filament field component
     *
     * @param  CustomField  $customField
     * @return Field
     */
    abstract protected function createField(CustomField $customField): Field;

    /**
     * Apply field-specific configuration
     *
     * @param  Field  $field
     * @param  CustomField  $customField
     * @return void
     */
    abstract protected function applyFieldSpecificConfiguration(Field $field, CustomField $customField): void;

    /**
     * Configure common field properties
     *
     * @param  Field  $field
     * @param  CustomField  $customField
     * @return void
     */
    protected function configureField(Field $field, CustomField $customField): void
    {
        // Set the state path
        $field->statePath($this->getStateAttributeName($customField));

        // Basic configuration
        $field->label($customField->name)
            ->required(false)
            ->disabled(false);

        // Add helper text if available from config
        $config = $customField->field_config ?? [];
        if (isset($config['help_text'])) {
            $field->helperText($config['help_text']);
        }

        // Add hint if available from config
        if (isset($config['hint'])) {
            $field->hint($config['hint']);
        }

        // Get field configuration
        $config = $customField->field_config ?? [];

        // Apply placeholder
        if (isset($config['placeholder'])) {
            $field->placeholder($config['placeholder']);
        }

        // Apply default value
        if (isset($config['default'])) {
            $field->default($config['default']);
        }

        // Apply column span
        if (isset($config['columnSpan'])) {
            $field->columnSpan($config['columnSpan']);
        }

        // Apply inline label
        if (isset($config['inlineLabel']) && $config['inlineLabel']) {
            $field->inlineLabel();
        }

        // Apply hidden state
        if (isset($config['hidden']) && $config['hidden']) {
            $field->hidden();
        }

        // Configure validation rules
        $this->configureValidation($field, $customField);

        // Configure state processing
        $this->configureStateProcessing($field, $customField);
    }

    /**
     * Configure validation rules for the field
     *
     * @param  Field  $field
     * @param  CustomField  $customField
     * @return void
     */
    protected function configureValidation(Field $field, CustomField $customField): void
    {
        $rules = [];

        // Add required rule
        if ($customField->is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Add validation rules from configuration
        $config = $customField->field_config ?? [];
        if (isset($config['validation_rules']) && is_array($config['validation_rules'])) {
            $rules = array_merge($rules, $config['validation_rules']);
        }

        // Apply field type specific validation
        $typeRules = $this->getFieldTypeValidationRules($customField);
        if (! empty($typeRules)) {
            $rules = array_merge($rules, $typeRules);
        }

        // Apply rules to field
        if (! empty($rules)) {
            $field->rules($rules);
        }
    }

    /**
     * Get field type specific validation rules
     *
     * @param  CustomField  $customField
     * @return array<string>
     */
    protected function getFieldTypeValidationRules(CustomField $customField): array
    {
        $config = $customField->field_config ?? [];

        return match ($customField->type) {
            'email' => ['email'],
            'url' => ['url'],
            'number' => array_filter([
                'numeric',
                isset($config['min']) ? "min:{$config['min']}" : null,
                isset($config['max']) ? "max:{$config['max']}" : null,
            ]),
            'tel' => ['regex:/^[+]?[0-9\s\-\(\)]+$/'],
            'color' => ['regex:/^#[0-9A-Fa-f]{6}$/'],
            default => [],
        };
    }

    /**
     * Configure state processing for the field
     *
     * @param  Field  $field
     * @param  CustomField  $customField
     * @return void
     */
    protected function configureStateProcessing(Field $field, CustomField $customField): void
    {
        // Configure how to load state from the model
        $field->loadStateFromRelationshipsUsing(function (Field $component, $state) use ($customField): void {
            if ($record = $component->getRecord()) {
                $value = $this->resolveState($record, $customField);
                $component->state($value);
            }
        });

        // Configure how to save state to the model
        $field->saveRelationshipsUsing(function (Field $component, $state) use ($customField): void {
            if ($record = $component->getRecord()) {
                $this->saveCustomFieldValue($record, $customField, $state);
            }
        });

        // Configure state hydration
        $field->afterStateHydrated(function (Field $component, $state) use ($customField): void {
            if ($state !== null) {
                $processedState = $this->processValue($state, $customField);
                $component->state($processedState);
            }
        });

        // Configure state dehydration
        $field->dehydrateStateUsing(function ($state) use ($customField) {
            return $this->prepareValueForStorage($state, $customField);
        });
    }

}