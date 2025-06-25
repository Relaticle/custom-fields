<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms;

use Filament\Forms\Components\Field;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Optional;
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
            ->afterStateHydrated(fn($component, $state, $record) => $component->state($this->getFieldValue($customField, $state, $record)))
            ->dehydrated(fn($state) => $this->visibilityService->shouldAlwaysSave($customField) || filled($state))
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
        return value(function () use ($customField, $state, $record) {
            $value = $record?->getCustomFieldValue($customField)
                ?? $state
                ?? ($customField->type->hasMultipleValues() ? [] : null);

            return $value instanceof Carbon
                ? $value->format(
                    $customField->type === CustomFieldType::DATE
                        ? FieldTypeUtils::getDateFormat()
                        : FieldTypeUtils::getDateTimeFormat()
                )
                : $value;
        });
    }

    private function hasVisibilityConditions(CustomField $customField): bool
    {
        return $customField->settings?->visibility?->requiresConditions() ?? false;
    }

    private function applyVisibility(Field $field, CustomField $customField, ?Collection $allFields): Optional|Field
    {
        return optional($this->buildVisibilityExpression($customField, $allFields), function ($jsExpression) use ($field) {
            return $field->live()->visibleJs($jsExpression);
        }) ?? $field;
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
            ->map(fn($condition) => "({$condition})");

        return $conditions->isNotEmpty() ? $conditions->implode(' && ') : null;
    }

    private function buildFieldConditions($visibility, ?Collection $allFields): ?string
    {
        if (!$allFields) {
            return null;
        }

        $conditions = $visibility->conditions->toCollection()
            ->filter(fn($condition) => $allFields->contains('code', $condition->field_code))
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
            ->pluck('settings.visibility')
            ->filter(fn($vis) => $vis?->requiresConditions() && filled($vis->conditions))
            ->map(fn($vis) => $this->buildFieldConditions($vis, $allFields))
            ->filter();

        return $parentConditions->isNotEmpty() ? $parentConditions->implode(' && ') : null;
    }

    private function buildCondition(VisibilityConditionData $condition, object $mode, ?Collection $allFields): ?string
    {
        $targetField = $allFields->firstWhere('code', $condition->field_code);
        $fieldValue = "\$get('custom_fields.{$condition->field_code}')";

        return optional(
            $this->buildOperatorExpression(
                $condition->operator,
                $fieldValue,
                $condition->value,
                $targetField
            ),
            fn($expression) => $mode->value === 'show_when' ? $expression : "!({$expression})"
        );
    }

    private function buildOperatorExpression(Operator $operator, string $fieldValue, mixed $value, ?CustomField $targetField): ?string
    {
        return match ($operator) {
            Operator::EQUALS => $this->buildEqualsExpression($fieldValue, $value, $targetField),
            Operator::NOT_EQUALS => $this->buildNotEqualsExpression($fieldValue, $value, $targetField),
            Operator::CONTAINS => $this->buildContainsExpression($fieldValue, $value, $targetField),
            Operator::NOT_CONTAINS => transform(
                $this->buildContainsExpression($fieldValue, $value, $targetField),
                fn($expr) => "!({$expr})"
            ),
            Operator::GREATER_THAN => "parseFloat({$fieldValue}) > parseFloat({$this->formatJsValue($value)})",
            Operator::LESS_THAN => "parseFloat({$fieldValue}) < parseFloat({$this->formatJsValue($value)})",
            Operator::IS_EMPTY => $this->buildEmptyExpression($fieldValue, true),
            Operator::IS_NOT_EMPTY => $this->buildEmptyExpression($fieldValue, false),
        };
    }

    private function buildEqualsExpression(string $fieldValue, mixed $value, ?CustomField $targetField): string
    {
        return when(
            $targetField?->type?->isOptionable(),
            fn() => $this->buildOptionExpression($fieldValue, $value, $targetField, 'equals'),
            fn() => $this->buildStandardEqualsExpression($fieldValue, $value)
        );
    }

    private function buildStandardEqualsExpression(string $fieldValue, mixed $value): string
    {
        $jsValue = $this->formatJsValue($value);

        return is_array($value)
            ? "JSON.stringify({$fieldValue}) === JSON.stringify({$jsValue})"
            : "{$fieldValue} === {$jsValue}";
    }

    private function buildNotEqualsExpression(string $fieldValue, mixed $value, ?CustomField $targetField): string
    {
        return when(
            $targetField?->type?->isOptionable(),
            fn() => $this->buildOptionExpression($fieldValue, $value, $targetField, 'not_equals'),
            fn() => $this->buildStandardNotEqualsExpression($fieldValue, $value)
        );
    }

    private function buildStandardNotEqualsExpression(string $fieldValue, mixed $value): string
    {
        $jsValue = $this->formatJsValue($value);

        return is_array($value)
            ? "JSON.stringify({$fieldValue}) !== JSON.stringify({$jsValue})"
            : "{$fieldValue} !== {$jsValue}";
    }

    private function buildOptionExpression(string $fieldValue, mixed $value, CustomField $targetField, string $operator): string
    {
        $resolvedValue = $this->resolveOptionValue($value, $targetField);
        $jsValue = $this->formatJsValue($resolvedValue);


        $condition = $targetField->type->hasMultipleValues()
            ? $this->buildMultiValueOptionCondition($fieldValue, $resolvedValue, $jsValue)
            : $this->buildSingleValueOptionCondition($fieldValue, $jsValue);

        return Str::is('not_equals', $operator) ? "!({$condition})" : $condition;
    }

    private function buildMultiValueOptionCondition(string $fieldValue, mixed $resolvedValue, string $jsValue): string
    {
        return is_array($resolvedValue)
            ? "(() => {
                const fieldVal = Array.isArray({$fieldValue}) ? {$fieldValue} : [];
                const conditionVal = {$jsValue};
                return conditionVal.some(id => fieldVal.includes(id));
            })()"
            : "(() => {
                const fieldVal = Array.isArray({$fieldValue}) ? {$fieldValue} : [];
                return fieldVal.includes({$jsValue});
            })()";
    }

    private function buildSingleValueOptionCondition(string $fieldValue, string $jsValue): string
    {
        return "(() => {
            const fieldVal = {$fieldValue};
            const conditionVal = {$jsValue};
            return String(fieldVal || '') === String(conditionVal || '');
        })()";
    }

    private function resolveOptionValue(mixed $value, CustomField $targetField): mixed
    {
        return match (true) {
            blank($value) => $value,
            is_array($value) => $this->resolveArrayOptionValue($value, $targetField),
            default => $this->convertOptionValue($value, $targetField)
        };
    }

    private function resolveArrayOptionValue(array $value, CustomField $targetField): mixed
    {
        return $targetField->type->hasMultipleValues()
            ? collect($value)->map(fn($v) => $this->convertOptionValue($v, $targetField))->all()
            : $this->convertOptionValue(head($value), $targetField);
    }

    private function convertOptionValue(mixed $value, CustomField $targetField): mixed
    {
        if (blank($value) || is_numeric($value)) {
            return is_numeric($value) ? (int)$value : $value;
        }

        return rescue(function () use ($value, $targetField) {
            if (is_string($value) && $targetField->options) {
                return $targetField->options
                    ->first(fn($opt) => filled($opt->name) &&
                        Str::lower(trim($opt->name)) === Str::lower(trim($value))
                    )?->id ?? $value;
            }

            return $value;
        }, $value);
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
            is_array($value) => collect($value)
                ->map(fn($item) => $this->formatJsValue($item))
                ->pipe(fn($collection) => '[' . $collection->implode(', ') . ']'),
            default => "'" . addslashes((string)$value) . "'"
        };
    }
}