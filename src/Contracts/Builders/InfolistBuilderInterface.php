<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Builders;

use Filament\Infolists\Components\Component;

/**
 * ABOUTME: Interface for building Filament infolist components from custom fields
 * ABOUTME: Used for displaying custom field values in view/detail pages
 */
interface InfolistBuilderInterface extends CustomFieldsBuilderInterface
{
    /**
     * Build and return infolist components
     *
     * @return array<Component>
     */
    public function build(): array;

    /**
     * Configure whether to group entries by sections
     *
     * @param  bool  $group
     * @return static
     */
    public function groupBySections(bool $group = true): static;

    /**
     * Set the column span for all entries
     *
     * @param  int|string|null  $span
     * @return static
     */
    public function columnSpan(int|string|null $span): static;

    /**
     * Configure whether to show empty values
     *
     * @param  bool  $show
     * @return static
     */
    public function showEmptyValues(bool $show = false): static;

    /**
     * Set custom placeholder for empty values
     *
     * @param  string  $placeholder
     * @return static
     */
    public function emptyPlaceholder(string $placeholder): static;
}