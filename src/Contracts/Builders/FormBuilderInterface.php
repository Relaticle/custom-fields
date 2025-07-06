<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Builders;

use Filament\Forms\Components\Component;

/**
 * ABOUTME: Interface for building Filament form components from custom fields
 * ABOUTME: Extends the base builder with form-specific functionality
 */
interface FormBuilderInterface extends CustomFieldsBuilderInterface
{
    /**
     * Build and return form components
     *
     * @return array<Component>
     */
    public function build(): array;

    /**
     * Configure whether to group fields by sections
     *
     * @param  bool  $group
     * @return static
     */
    public function groupBySections(bool $group = true): static;

    /**
     * Set the column span for all components
     *
     * @param  int|string|null  $span
     * @return static
     */
    public function columnSpan(int|string|null $span): static;

    /**
     * Configure live validation
     *
     * @param  bool  $live
     * @return static
     */
    public function live(bool $live = true): static;

    /**
     * Set debounce time for live fields
     *
     * @param  int  $milliseconds
     * @return static
     */
    public function debounce(int $milliseconds): static;
}