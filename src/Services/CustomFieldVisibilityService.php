<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\Visibility\BackendVisibilityService;
use Relaticle\CustomFields\Services\Visibility\FrontendVisibilityService;

/**
 * Unified service for custom field visibility evaluation across all contexts.
 *
 * This service provides a single source of truth for visibility logic that works
 * consistently across forms (frontend with visibleJs), infolists (backend), and exports.
 *
 * SOLID Principles:
 * - Single Responsibility: Handles only field visibility evaluation and value extraction
 * - Open/Closed: Extensible via strategy pattern for different contexts
 * - Liskov Substitution: Consistent interface across all visibility evaluations
 * - Interface Segregation: Focused methods for specific use cases
 * - Dependency Inversion: Depends on abstractions (VisibilityService) not concrete implementations
 */
final readonly class CustomFieldVisibilityService
{
    public function __construct(
        private BackendVisibilityService $backendVisibilityService,
        private FrontendVisibilityService $frontendVisibilityService
    ) {}

    /**
     * Extract field values from a record for visibility evaluation.
     *
     * This is the core method that provides consistent field value extraction
     * across all contexts (forms, infolists, exports).
     */
    public function extractFieldValues(Model $record, Collection $fields): array
    {
        return $this->backendVisibilityService->extractFieldValues($record, $fields);
    }

    /**
     * Filter fields to only those that should be visible for the given record.
     *
     * Uses backend visibility evaluation - suitable for infolists and exports.
     */
    public function getVisibleFields(Model $record, Collection $fields): Collection
    {
        return $this->backendVisibilityService->getVisibleFields($record, $fields);
    }

    /**
     * Get field values normalized for visibility evaluation.
     *
     * This method ensures consistent value formatting across all contexts.
     */
    public function getNormalizedFieldValues(Model $record, Collection $fields): array
    {
        return $this->backendVisibilityService->getNormalizedFieldValues($record, $fields);
    }

    /**
     * Check if a specific field should be visible for the given record.
     */
    public function isFieldVisible(Model $record, CustomField $field, Collection $allFields): bool
    {
        return $this->backendVisibilityService->isFieldVisible($record, $field, $allFields);
    }

    /**
     * Export visibility logic to JavaScript format for frontend use.
     *
     * This enables the frontend (CustomFieldsForm) to use the same visibility logic
     * as the backend, ensuring consistency across all contexts.
     */
    public function exportVisibilityLogicToJs(Collection $fields): array
    {
        return $this->frontendVisibilityService->exportVisibilityLogicToJs($fields);
    }

    /**
     * Validate that field visibility evaluation is working correctly.
     *
     * Useful for debugging and ensuring consistency across contexts.
     */
    public function validateVisibilityConsistency(Model $record, Collection $fields): array
    {
        return $this->backendVisibilityService->validateVisibilityConsistency($record, $fields);
    }

    /**
     * Calculate field dependencies for all fields.
     */
    public function calculateDependencies(Collection $allFields): array
    {
        return $this->backendVisibilityService->calculateDependencies($allFields);
    }
}
