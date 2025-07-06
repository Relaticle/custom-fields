<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Factories;

/**
 * ABOUTME: Base interface for all factory implementations in the custom fields system
 * ABOUTME: Defines the common contract for creating objects from configuration
 */
interface FactoryInterface
{
    /**
     * Check if the factory can create an object for the given type
     *
     * @param  mixed  $type
     * @return bool
     */
    public function supports(mixed $type): bool;
}