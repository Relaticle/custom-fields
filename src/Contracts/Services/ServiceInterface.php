<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts\Services;

/**
 * ABOUTME: Base interface for all service classes in the custom fields system
 * ABOUTME: Provides common contract for service initialization and configuration
 */
interface ServiceInterface
{
    /**
     * Check if the service is properly initialized
     *
     * @return bool
     */
    public function isInitialized(): bool;

    /**
     * Initialize the service with configuration
     *
     * @param  array<string, mixed>  $config
     * @return void
     */
    public function initialize(array $config = []): void;
}