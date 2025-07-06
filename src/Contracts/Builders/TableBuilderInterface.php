<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Builders;

use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;

/**
 * ABOUTME: Interface for building Filament table columns and filters from custom fields
 * ABOUTME: Provides methods for creating both display columns and search filters
 */
interface TableBuilderInterface extends CustomFieldsBuilderInterface
{
    /**
     * Build and return table columns
     *
     * @return array<Column>
     */
    public function columns(): array;

    /**
     * Build and return table filters
     *
     * @return array<BaseFilter>
     */
    public function filters(): array;

    /**
     * Build both columns and filters
     *
     * @return array{columns: array<Column>, filters: array<BaseFilter>}
     */
    public function build(): array;

    /**
     * Configure whether columns should be searchable
     *
     * @param  bool  $searchable
     * @return static
     */
    public function searchable(bool $searchable = true): static;

    /**
     * Configure whether columns should be sortable
     *
     * @param  bool  $sortable
     * @return static
     */
    public function sortable(bool $sortable = true): static;

    /**
     * Configure whether columns should be toggleable
     *
     * @param  bool  $toggleable
     * @return static
     */
    public function toggleable(bool $toggleable = true): static;

    /**
     * Set which columns should be hidden by default
     *
     * @param  array<string>  $fieldCodes
     * @return static
     */
    public function hiddenByDefault(array $fieldCodes): static;
}