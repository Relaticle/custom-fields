<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Data;

use Relaticle\CustomFields\Enums\ConditionalVisibilityLogic;
use Relaticle\CustomFields\Enums\ConditionalVisibilityMode;
use Relaticle\CustomFields\Enums\ConditionOperator;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class CustomFieldConditionsData extends Data
{
    public function __construct(
        public ConditionalVisibilityMode $enabled = ConditionalVisibilityMode::ALWAYS,
        public ConditionalVisibilityLogic $logic = ConditionalVisibilityLogic::ALL,
        public ?array $conditions = null,
        public bool $always_save = false,
    ) {}

    /**
     * Check if conditions are required.
     */
    public function requiresConditions(): bool
    {
        return $this->enabled->requiresConditions();
    }

    /**
     * Evaluate all conditions against field values.
     *
     * @param  array<string, mixed>  $fieldValues
     */
    public function evaluate(array $fieldValues): bool
    {
        if (! $this->requiresConditions() || empty($this->conditions)) {
            return $this->enabled === ConditionalVisibilityMode::ALWAYS;
        }

        $results = [];
        foreach ($this->conditions as $condition) {
            $fieldCode = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? null;
            $expectedValue = $condition['value'] ?? null;

            if (! $fieldCode || ! $operator) {
                $results[] = false;

                continue;
            }

            try {
                $conditionOperator = ConditionOperator::from($operator);
                $fieldValue = $fieldValues[$fieldCode] ?? null;
                $results[] = $conditionOperator->evaluate($fieldValue, $expectedValue);
            } catch (\ValueError) {
                $results[] = false;
            }
        }

        $conditionsResult = $this->logic->evaluate($results);

        return $this->enabled->shouldShow($conditionsResult);
    }
}
