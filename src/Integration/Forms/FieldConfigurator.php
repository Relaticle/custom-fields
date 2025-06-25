<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms;

use Filament\Forms\Components\Field;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Relaticle\CustomFields\Data\VisibilityConditionData;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\Logic;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValidationService;
use Relaticle\CustomFields\Services\VisibilityService;
use Relaticle\CustomFields\Support\FieldTypeUtils;

final readonly class FieldConfigurator
{
    public function __construct(
        private ValidationService $validationService,
        private VisibilityService $visibilityService,
    )
    {
    }

    public function configure(Field $field, CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        return $field
            ->name("custom_fields.{$customField->code}")
            ->label($customField->name)
            ->afterStateHydrated(fn($component, $state, $record) => $component->state($this->getFieldValue($customField, $state, $record))
            )
            ->dehydrated(fn($state) => $this->visibilityService->shouldAlwaysSave($customField) || filled($state)
            )
            ->required($this->validationService->isRequired($customField))
            ->rules($this->validationService->getValidationRules($customField))
            ->columnSpan($customField->width->getSpanValue())
            ->when(
                $this->hasVisibilityConditions($customField),
                fn(Field $field) => $this->applyVisibility($field, $customField, $allFields)
            )
            ->when(
                filled($dependentFieldCodes),
                fn(Field $field) => $field->live()
            );
    }

    private function getFieldValue(CustomField $customField, $state, $record): mixed
    {
        $value = $record?->getCustomFieldValue($customField)
            ?? $state
            ?? ($customField->type->hasMultipleValues() ? [] : null);

        if ($value instanceof Carbon) {
            $format = $customField->type === CustomFieldType::DATE
                ? FieldTypeUtils::getDateFormat()
                : FieldTypeUtils::getDateTimeFormat();

            return $value->format($format);
        }

        return $value;
    }

    private function hasVisibilityConditions(CustomField $customField): bool
    {
        return $customField->settings?->visibility?->requiresConditions() ?? false;
    }

    private function applyVisibility(Field $field, CustomField $customField, ?Collection $allFields): Field
    {
        $jsExpression = $this->buildVisibilityExpression($customField, $allFields);

        return $jsExpression
            ? $field->live()->visibleJs($jsExpression)
            : $field;
    }

    private function buildVisibilityExpression(CustomField $field, ?Collection $allFields): ?string
    {
        $visibility = $field->settings?->visibility;

        if (!$visibility?->requiresConditions() || blank($visibility->conditions)) {
            return null;
        }

        $conditions = collect([
            $this->buildParentConditions($field, $allFields),
            $this->buildFieldConditions($visibility, $allFields),
        ])
            ->filter()
            ->map(fn($condition) => "({$condition})")
            ->implode(' && ');

        return $conditions ?: null;
    }

    private function buildFieldConditions($visibility, ?Collection $allFields): ?string
    {
        if (!$allFields) {
            return null;
        }

        $conditions = $visibility->conditions->toCollection()
            ->filter(fn($condition) => $allFields->firstWhere('code', $condition->field_code))
            ->map(fn($condition) => $this->buildCondition($condition, $visibility->mode, $allFields))
            ->filter()
            ->values();

        if ($conditions->isEmpty()) {
            return null;
        }

        $operator = ($visibility->logic ?? Logic::ALL) === Logic::ALL ? ' && ' : ' || ';
        return $conditions->implode($operator);
    }

    private function buildParentConditions(CustomField $field, ?Collection $allFields): ?string
    {
        if (!$allFields) {
            return null;
        }

        $parentConditions = collect($this->visibilityService->getDependentFields($field))
            ->map(fn($code) => $allFields->firstWhere('code', $code))
            ->filter()
            ->map(fn($parent) => $parent->settings?->visibility)
            ->filter(fn($vis) => $vis?->requiresConditions() && filled($vis->conditions))
            ->map(fn($vis) => $this->buildFieldConditions($vis, $allFields))
            ->filter();

        return $parentConditions->isNotEmpty()
            ? $parentConditions->implode(' && ')
            : null;
    }

    private function buildCondition(VisibilityConditionData $condition, object $mode, ?Collection $allFields): ?string
    {
        $targetField = $allFields->firstWhere('code', $condition->field_code);
        $fieldValue = "\$get('custom_fields.{$condition->field_code}')";

        $expression = $this->buildOperatorExpression(
            $condition->operator,
            $fieldValue,
            $condition->value,
            $targetField
        );

        if (blank($expression)) {
            return null;
        }

        return $mode->value === 'show_when' ? $expression : "!({$expression})";
    }

    private function buildOperatorExpression(Operator $operator, string $fieldValue, mixed $value, ?CustomField $targetField): ?string
    {
        return match ($operator) {
            Operator::EQUALS => $this->buildEqualsExpression($fieldValue, $value, $targetField),
            Operator::NOT_EQUALS => $this->buildNotEqualsExpression($fieldValue, $value, $targetField),
            Operator::CONTAINS => $this->buildContainsExpression($fieldValue, $value, $targetField),
            Operator::NOT_CONTAINS => "!({$this->buildContainsExpression($fieldValue, $value, $targetField)})",
            Operator::GREATER_THAN => "parseFloat({$fieldValue}) > parseFloat({$this->formatJsValue($value)})",
            Operator::LESS_THAN => "parseFloat({$fieldValue}) < parseFloat({$this->formatJsValue($value)})",
            Operator::IS_EMPTY => $this->buildEmptyExpression($fieldValue, true),
            Operator::IS_NOT_EMPTY => $this->buildEmptyExpression($fieldValue, false),
        };
    }

    private function buildEqualsExpression(string $fieldValue, mixed $value, ?CustomField $targetField): string
    {
        if ($targetField?->type?->isOptionable()) {
            return $this->buildOptionExpression($fieldValue, $value, $targetField, 'equals');
        }

        $jsValue = $this->formatJsValue($value);

        return is_array($value)
            ? "JSON.stringify({$fieldValue}) === JSON.stringify({$jsValue})"
            : "{$fieldValue} === {$jsValue}";
    }

    private function buildNotEqualsExpression(string $fieldValue, mixed $value, ?CustomField $targetField): string
    {
        if ($targetField?->type?->isOptionable()) {
            return $this->buildOptionExpression($fieldValue, $value, $targetField, 'not_equals');
        }

        $jsValue = $this->formatJsValue($value);

        return is_array($value)
            ? "JSON.stringify({$fieldValue}) !== JSON.stringify({$jsValue})"
            : "{$fieldValue} !== {$jsValue}";
    }

    private function buildOptionExpression(string $fieldValue, mixed $value, CustomField $targetField, string $operator): string
    {
        $resolvedValue = $this->resolveOptionValue($value, $targetField);
        $jsValue = $this->formatJsValue($resolvedValue);

        if ($targetField->type->hasMultipleValues()) {
            $condition = is_array($resolvedValue)
                ? "Array.isArray({$fieldValue}) && {$this->formatJsValue($resolvedValue)}.every(id => {$fieldValue}.includes(id))"
                : "Array.isArray({$fieldValue}) && {$fieldValue}.includes({$jsValue})";

            return Str::is('not_equals', $operator) ? "!({$condition})" : $condition;
        }

        return Str::is('not_equals', $operator)
            ? "{$fieldValue} !== {$jsValue}"
            : "{$fieldValue} === {$jsValue}";
    }

    private function resolveOptionValue(mixed $value, CustomField $targetField): mixed
    {
        if (blank($value)) {
            return $value;
        }

        if (is_array($value)) {
            return $targetField->type->hasMultipleValues()
                ? collect($value)->map(fn($v) => $this->convertOptionValue($v, $targetField))->all()
                : $this->convertOptionValue(head($value), $targetField);
        }

        return $this->convertOptionValue($value, $targetField);
    }

    private function convertOptionValue(mixed $value, CustomField $targetField): mixed
    {
        if (blank($value) || is_numeric($value)) {
            return is_numeric($value) ? (int)$value : $value;
        }

        if (is_string($value) && $targetField->options) {
            $option = $targetField->options
                ->first(fn($opt) => filled($opt->name) &&
                    Str::lower(trim($opt->name)) === Str::lower(trim($value))
                );

            return $option?->id ?? $value;
        }

        return $value;
    }

    private function buildContainsExpression(string $fieldValue, mixed $value, ?CustomField $targetField): string
    {
        $resolvedValue = $this->resolveOptionValue($value, $targetField);
        $jsValue = $this->formatJsValue($resolvedValue);

        return "(() => {
            const fieldVal = {$fieldValue};
            const searchVal = {$jsValue};
            return Array.isArray(fieldVal) 
                ? fieldVal.some(item => String(item).toLowerCase().includes(String(searchVal).toLowerCase()))
                : String(fieldVal || '').toLowerCase().includes(String(searchVal).toLowerCase());
        })()";
    }

    private function buildEmptyExpression(string $fieldValue, bool $isEmpty): string
    {
        $condition = "(() => {
            const val = {$fieldValue};
            return val === null || val === undefined || val === '' || (Array.isArray(val) && val.length === 0);
        })()";

        return $isEmpty ? $condition : "!({$condition})";
    }

    private function formatJsValue(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_string($value) && in_array(Str::lower($value), ['true', 'false']) => Str::lower($value),
            is_string($value) => "'" . addslashes($value) . "'",
            is_numeric($value) => (string)$value,
            is_array($value) => '[' . collect($value)->map(fn($item) => $this->formatJsValue($item))->implode(', ') . ']',
            default => "'" . addslashes((string)$value) . "'"
        };
    }
}