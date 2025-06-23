<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Data;

use Relaticle\CustomFields\Enums\Logic;
use Relaticle\CustomFields\Enums\Mode;
use Relaticle\CustomFields\Enums\Operator;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class VisibilityData extends Data
{
    public function __construct(
        public Mode $mode = Mode::ALWAYS_VISIBLE,
        public Logic $logic = Logic::ALL,
        public ?array $conditions = null,
        public bool $alwaysSave = false,
    ) {
        $this->conditions = $this->sanitizeConditions($conditions);
    }

    public function requiresConditions(): bool
    {
        return $this->mode->requiresConditions();
    }

    public function evaluate(array $fieldValues): bool
    {
        if (! $this->requiresConditions() || empty($this->conditions)) {
            return $this->mode === Mode::ALWAYS_VISIBLE;
        }

        $results = [];

        foreach ($this->conditions as $condition) {
            $result = $this->evaluateCondition($condition, $fieldValues);
            $results[] = $result;
        }

        $conditionsMet = $this->logic->evaluate($results);

        return $this->mode->shouldShow($conditionsMet);
    }

    private function evaluateCondition(array $condition, array $fieldValues): bool
    {
        $fieldCode = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;
        $expectedValue = $condition['value'] ?? null;

        if (! $fieldCode || ! $operator) {
            return false;
        }

        try {
            $operatorEnum = Operator::from($operator);
            $fieldValue = $fieldValues[$fieldCode] ?? null;

            return $operatorEnum->evaluate($fieldValue, $expectedValue);
        } catch (\ValueError) {
            return false;
        }
    }

    private function sanitizeConditions(?array $conditions): ?array
    {
        if ($conditions === null) {
            return null;
        }

        $sanitized = [];

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                continue;
            }

            $sanitizedCondition = [];

            if (isset($condition['field']) && is_string($condition['field']) && ! empty($condition['field'])) {
                $sanitizedCondition['field'] = $condition['field'];
            }

            if (isset($condition['operator']) && is_string($condition['operator']) && ! empty($condition['operator'])) {
                $sanitizedCondition['operator'] = $condition['operator'];
            }

            if (isset($condition['value'])) {
                $sanitizedCondition['value'] = $condition['value'];
            }

            if (isset($sanitizedCondition['field']) && isset($sanitizedCondition['operator'])) {
                $sanitized[] = $sanitizedCondition;
            }
        }

        return empty($sanitized) ? null : $sanitized;
    }

    public function getDependentFields(): array
    {
        if (! $this->requiresConditions() || empty($this->conditions)) {
            return [];
        }

        $fields = [];

        foreach ($this->conditions as $condition) {
            if (isset($condition['field']) && is_string($condition['field'])) {
                $fields[] = $condition['field'];
            }
        }

        return array_unique($fields);
    }
}
