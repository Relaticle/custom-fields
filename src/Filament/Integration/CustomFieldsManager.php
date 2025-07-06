<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration;

use Relaticle\CustomFields\Filament\Integration\Builders\FormBuilder;
use Relaticle\CustomFields\Filament\Integration\Builders\InfolistBuilder;
use Relaticle\CustomFields\Filament\Integration\Builders\TableBuilder;

/**
 * ABOUTME: Main entry point for the Custom Fields API providing builder methods
 * ABOUTME: Creates and returns configured builders for forms, tables, and infolists
 */
class CustomFieldsManager
{
    /**
     * Create a new form builder instance
     *
     * @return FormBuilder
     */
    public static function form(): FormBuilder
    {
        return FormBuilder::make();
    }

    /**
     * Create a new table builder instance
     *
     * @return TableBuilder
     */
    public static function table(): TableBuilder
    {
        return TableBuilder::make();
    }

    /**
     * Create a new infolist builder instance
     *
     * @return InfolistBuilder
     */
    public static function infolist(): InfolistBuilder
    {
        return InfolistBuilder::make();
    }

    /**
     * Alias for form() method for backward compatibility
     *
     * @return FormBuilder
     */
    public static function forms(): FormBuilder
    {
        return static::form();
    }

    /**
     * Alias for table() method for backward compatibility
     *
     * @return TableBuilder
     */
    public static function tables(): TableBuilder
    {
        return static::table();
    }

    /**
     * Alias for infolist() method for backward compatibility
     *
     * @return InfolistBuilder
     */
    public static function infolists(): InfolistBuilder
    {
        return static::infolist();
    }
}