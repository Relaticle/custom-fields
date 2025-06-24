<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Forms;

use Filament\Forms\Components\Field;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Data\VisibilityConditionData;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Enums\Logic;
use Relaticle\CustomFields\Enums\Operator;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValidationService;
use Relaticle\CustomFields\Services\VisibilityService;
use Relaticle\CustomFields\Support\FieldTypeUtils;

final class FieldConfigurator
{
    private static array $fieldCache = [];

    public function __construct(
        private readonly ValidationService $validationService,
        private readonly VisibilityService $visibilityService,
    ) {}

    public function configure(Field $field, CustomField $customField, array $dependentFieldCodes = [], ?Collection $allFields = null): Field
    {
        $field = $field
            ->name('custom_fields.'.$customField->code)
            ->label($customField->name)
            ->afterStateHydrated(function ($component, $state, $record) use ($customField): void {
                $value = $record?->getCustomFieldValue($customField)
                    ?? $state
                    ?? ($customField->type->hasMultipleValues() ? [] : null);

                if ($value instanceof Carbon) {
                    $value = $value->format(
                        $customField->type === CustomFieldType::DATE
                            ? FieldTypeUtils::getDateFormat()
                            : FieldTypeUtils::getDateTimeFormat()
                    );
                }

                $component->state($value);
            })
            ->dehydrated(fn ($state) => $this->visibilityService->shouldAlwaysSave($customField)
                || ($state !== null && $state !== ''))
            ->required($this->validationService->isRequired($customField))
            ->rules($this->validationService->getValidationRules($customField))
            ->columnSpan($customField->width->getSpanValue())
            ->inlineLabel(false);

        if ($this->hasVisibilityConditions($customField)) {
            $field = $this->addConditionalVisibility($field, $customField, $allFields);
        }

        if (! empty($dependentFieldCodes)) {
            $field = $field->live();
        }

        return $field;
    }

    private function hasVisibilityConditions(CustomField $customField): bool
    {
        return $customField->settings?->visibility?->requiresConditions() ?? false;
    }

    private function addConditionalVisibility(Field $field, CustomField $customField, ?Collection $allFields = null): Field
    {
        $jsExpression = $this->generateJavaScriptVisibility($customField, $allFields);

        return $jsExpression ? $field->live()->visibleJs($jsExpression) : $field;
    }

    private function generateJavaScriptVisibility(CustomField $field, ?Collection $allFields = null): ?string
    {
        $visibility = $field->settings?->visibility;
        if (! $visibility?->requiresConditions() || blank($visibility->conditions)) {
            return null;
        }

        // Generate conditions for this field
        $jsConditions = $visibility->conditions->toCollection()
            ->map(fn (VisibilityConditionData $condition) => $this->buildConditionExpression($condition, $visibility->mode))
            ->filter()
            ->values()
            ->all();

        if (blank($jsConditions)) {
            return null;
        }

        $logicOperator = ($visibility->logic ?? Logic::ALL) === Logic::ALL ? ' && ' : ' || ';
        $fieldConditions = '('.collect($jsConditions)->implode($logicOperator).')';

        // Add cascading visibility - ensure all parent fields are visible
        $parentConditions = $this->generateParentVisibilityConditions($field, $allFields);

        if (filled($parentConditions)) {
            return "($parentConditions) && ($fieldConditions)";
        }

        return $fieldConditions;
    }

    private function generateParentVisibilityConditions(CustomField $field, ?Collection $allFields): ?string
    {
        if (! $allFields) {
            return null;
        }

        // Get fields that this field depends on
        $dependentFields = $this->visibilityService->getDependentFields($field);

        if (blank($dependentFields)) {
            return null;
        }

        $parentConditions = [];

        foreach ($dependentFields as $parentFieldCode) {
            $parentField = $allFields->firstWhere('code', $parentFieldCode);

            if (! $parentField) {
                continue;
            }

            // Check if parent field has visibility conditions
            $parentVisibility = $parentField->settings?->visibility;

            if ($parentVisibility?->requiresConditions() && filled($parentVisibility->conditions)) {
                // Generate conditions for parent field (without cascading to avoid recursion)
                $parentJsConditions = $parentVisibility->conditions->toCollection()
                    ->map(fn (VisibilityConditionData $condition) => $this->buildConditionExpression($condition, $parentVisibility->mode))
                    ->filter()
                    ->values()
                    ->all();

                if (filled($parentJsConditions)) {
                    $parentLogicOperator = ($parentVisibility->logic ?? Logic::ALL) === Logic::ALL ? ' && ' : ' || ';
                    $parentConditions[] = '('.collect($parentJsConditions)->implode($parentLogicOperator).')';
                }
            }
            // If parent has no conditions, it's always visible - no need to add a condition
        }

        return blank($parentConditions) ? null : collect($parentConditions)->implode(' && ');
    }

    private function buildConditionExpression(VisibilityConditionData $condition, object $mode): ?string
    {
        $targetField = $this->getCachedField($condition->field_code);
        $fieldValue = "\$get('custom_fields.{$condition->field_code}')";

        $expression = $this->generateOperatorExpression(
            $condition->operator,
            $fieldValue,
            $condition->value,
            $targetField
        );

        if (blank($expression)) {
            return null;
        }

        return $mode->value === 'show_when' ? $expression : "!($expression)";
    }

    private function getCachedField(string $fieldCode): ?CustomField
    {
        return self::$fieldCache[$fieldCode] ??= CustomFields::newCustomFieldModel()::query()
            ->where('code', $fieldCode)
            ->with('options')
            ->first();
    }

    private function generateOperatorExpression(Operator $operator, string $fieldValue, mixed $value, ?CustomField $targetField): ?string
    {
        return match ($operator) {
            Operator::EQUALS => $this->createEqualsExpression($fieldValue, $value, $targetField),
            Operator::NOT_EQUALS => $this->createNotEqualsExpression($fieldValue, $value, $targetField),
            Operator::CONTAINS => $this->createContainsExpression($fieldValue, $value, $targetField),
            Operator::NOT_CONTAINS => $this->createNotContainsExpression($fieldValue, $value, $targetField),
            Operator::GREATER_THAN => "parseFloat($fieldValue) > parseFloat({$this->formatJavaScriptValue($value)})",
            Operator::LESS_THAN => "parseFloat($fieldValue) < parseFloat({$this->formatJavaScriptValue($value)})",
            Operator::IS_EMPTY => $this->createEmptyExpression($fieldValue, true),
            Operator::IS_NOT_EMPTY => $this->createEmptyExpression($fieldValue, false),
        };
    }

    private function createEqualsExpression(string $fieldValue, mixed $value, ?CustomField $targetField): string
    {
        if ($targetField?->type?->isOptionable()) {
            return $this->createOptionableExpression($fieldValue, $value, $targetField, 'equals');
        }

        $jsValue = $this->formatJavaScriptValue($value);

        return is_array($value)
            ? "JSON.stringify($fieldValue) === JSON.stringify($jsValue)"
            : "$fieldValue === $jsValue";
    }

    private function createNotEqualsExpression(string $fieldValue, mixed $value, ?CustomField $targetField): string
    {
        if ($targetField?->type?->isOptionable()) {
            return $this->createOptionableExpression($fieldValue, $value, $targetField, 'not_equals');
        }

        $jsValue = $this->formatJavaScriptValue($value);

        return is_array($value)
            ? "JSON.stringify($fieldValue) !== JSON.stringify($jsValue)"
            : "$fieldValue !== $jsValue";
    }

    private function createOptionableExpression(string $fieldValue, mixed $value, CustomField $targetField, string $operator): string
    {
        $resolvedValue = $this->resolveOptionValue($value, $targetField, $operator);
        $jsValue = $this->formatJavaScriptValue($resolvedValue);

        if ($targetField->type->hasMultipleValues()) {
            $condition = is_array($resolvedValue)
                ? "Array.isArray($fieldValue) && {$this->formatJavaScriptValue($resolvedValue)}.every(id => $fieldValue.includes(id))"
                : "Array.isArray($fieldValue) && $fieldValue.includes($jsValue)";

            return Str::is('not_equals', $operator) ? "!($condition)" : $condition;
        }

        return Str::is('not_equals', $operator) ? "$fieldValue !== $jsValue" : "$fieldValue === $jsValue";
    }

    private function resolveOptionValue(mixed $value, CustomField $targetField, string $operator): mixed
    {
        if (blank($value)) {
            return $value;
        }

        if (is_array($value)) {
            return Str::contains($operator, ['contains', 'not_contains']) && $targetField->type->hasMultipleValues()
                ? collect($value)->map(fn ($v) => $this->convertToOptionId($v, $targetField))->all()
                : $this->convertToOptionId(head($value), $targetField);
        }

        return $this->convertToOptionId($value, $targetField);
    }

    private function convertToOptionId(mixed $value, CustomField $targetField): mixed
    {
        if (blank($value) || is_numeric($value)) {
            return is_numeric($value) ? (int) $value : $value;
        }

        if (filled($value) && is_string($value) && $targetField->options) {
            $matchingOption = $targetField->options->first(function ($option) use ($value) {
                return filled($option->name) && Str::lower(trim($option->name)) === Str::lower(trim($value));
            });

            //            dd($matchingOption->id);

            return $matchingOption?->id ?? $value;
        }

        return $value;
    }

    private function createContainsExpression(string $fieldValue, mixed $value, ?CustomField $targetField): string
    {
        //        if ($targetField?->type?->isOptionable()) {
        //            $resolvedValue = $this->resolveOptionValue($value, $targetField, 'contains');
        //            $jsValue = $this->formatJavaScriptValue($resolvedValue);
        //
        //            return $targetField->type->hasMultipleValues()
        //                ? "Array.isArray($fieldValue) && $fieldValue.includes($jsValue)"
        //                : "$fieldValue === $jsValue";
        //        }

        $resolvedValue = $this->resolveOptionValue($value, $targetField, 'contains');
        $jsValue = $this->formatJavaScriptValue($resolvedValue);

        return Str::of("
            (() => {
                const fieldVal = $fieldValue;
                const searchVal = $jsValue;
                return Array.isArray(fieldVal) 
                    ? fieldVal.some(item => String(item).toLowerCase().includes(String(searchVal).toLowerCase()))
                    : String(fieldVal || '').toLowerCase().includes(String(searchVal).toLowerCase());
            })()
        ")->trim()->toString();
    }

    private function createNotContainsExpression(string $fieldValue, mixed $value, ?CustomField $targetField): string
    {
        return Str::wrap($this->createContainsExpression($fieldValue, $value, $targetField), '!(', ')');
    }

    private function createEmptyExpression(string $fieldValue, bool $isEmpty): string
    {
        $condition = Str::of("
            (() => {
                const val = $fieldValue;
                return val === null || val === undefined || val === '' || (Array.isArray(val) && val.length === 0);
            })()
        ")->trim()->toString();

        return $isEmpty ? $condition : Str::wrap($condition, '!(', ')');
    }

    private function formatJavaScriptValue(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_string($value) && in_array(Str::lower($value), ['true', 'false']) => Str::lower($value),
            is_string($value) => "'".addslashes($value)."'",
            is_numeric($value) => (string) $value,
            is_array($value) => '['.collect($value)->map(fn ($item) => $this->formatJavaScriptValue($item))->implode(', ').']',
            default => "'".addslashes((string) $value)."'"
        };
    }
}
