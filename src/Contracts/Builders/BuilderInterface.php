<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Builders;

/**
 * ABOUTME: Base interface for all builder implementations in the custom fields system
 * ABOUTME: Defines the common contract that all builders must follow for fluent API construction
 */
interface BuilderInterface
{
    /**
     * Build and return the final result
     *
     * @return mixed The built result (components, columns, filters, etc.)
     */
    public function build(): mixed;

    /**
     * Reset the builder to its initial state
     *
     * @return static
     */
    public function reset(): static;
}