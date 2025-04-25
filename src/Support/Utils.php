<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Support;

class Utils
{
    public static function getResourceCluster(): ?string
    {
        return config('custom-fields.custom_fields_resource.cluster', null);
    }

    public static function getResourceSlug(): string
    {
        return (string) config('custom-fields.custom_fields_resource.slug');
    }

    public static function isResourceNavigationRegistered(): bool
    {
        return config('custom-fields.custom_fields_resource.should_register_navigation', true);
    }

    public static function getResourceNavigationSort(): ?int
    {
        return config('custom-fields.custom_fields_resource.navigation_sort');
    }

    public static function isResourceNavigationGroupEnabled(): bool
    {
        return config('custom-fields.custom_fields_resource.navigation_group', true);
    }

    public static function isTableColumnsEnabled(): bool
    {
        return config('custom-fields.resource.table.columns.enabled', true);
    }

    public static function isTableColumnsToggleableEnabled(): bool
    {
        return config('custom-fields.resource.table.columns_toggleable.enabled', true);
    }

    public static function isTableColumnsToggleableHiddenByDefault(): bool
    {
        return config('custom-fields.resource.table.columns_toggleable.hidden_by_default', true);
    }

    public static function isTableColumnsToggleableUserControlEnabled(): bool
    {
        return config('custom-fields.resource.table.columns_toggleable.user_control', false);
    }

    public static function isTableFiltersEnabled(): bool
    {
        return config('custom-fields.resource.table.filters.enabled', true);
    }

    public static function isTenantEnabled(): bool
    {
        return config('custom-fields.tenant_aware', false);
    }

    public static function isValuesEncryptionFeatureEnabled(): bool
    {
        return config('custom-fields.features.encryption.enabled', false);
    }

    /**
     * Check if the option colors feature is enabled.
     */
    public static function isSelectOptionColorsFeatureEnabled(): bool
    {
        return config('custom-fields.features.select_option_colors.enabled', false);
    }

    /**
     * Determine the text color (black or white) based on background color for optimal contrast.
     *
     * @param string $backgroundColor The background color in hex format (e.g., '#FF5500')
     * @return string The text color in hex format ('#000000' for black or '#FFFFFF' for white)
     */
    public static function getTextColor(string $backgroundColor): string
    {
        // Strip the leading # if present
        $backgroundColor = ltrim($backgroundColor, '#');
        
        // Convert hex to RGB
        $r = hexdec(substr($backgroundColor, 0, 2));
        $g = hexdec(substr($backgroundColor, 2, 2));
        $b = hexdec(substr($backgroundColor, 4, 2));
        
        // Calculate luminance (perceived brightness)
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        
        // Return black for light colors, white for dark colors
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }
}
