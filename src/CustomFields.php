<?php

declare(strict_types=1);

namespace Relaticle\CustomFields;

use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldValue;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\CustomFields\Models\CustomFieldSection;

final class CustomFields
{
    /**
     * The custom field model that should be used by Custom Fields.
     */
    public static string $customFieldModel = CustomField::class;

    /**
     * The custom field value model that should be used by Custom Fields.
     */
    public static string $valueModel = CustomFieldValue::class;

    /**
     * The custom field option model that should be used by Custom Fields.
     */
    public static string $optionModel = CustomFieldOption::class;

    /**
     * The custom field section model that should be used by Custom Fields.
     */
    public static string $sectionModel = CustomFieldSection::class;

    /**
     * Get the name of the custom field model used by the application.
     */
    public static function customFieldModel(): string
    {
        return static::$customFieldModel;
    }

    /**
     * Get a new instance of the custom field model.
     */
    public static function newCustomFieldModel(): mixed
    {
        $model = self::customFieldModel();

        return new $model;
    }

    /**
     * Specify the custom field model that should be used by Custom Fields.
     */
    public static function useCustomFieldModel(string $model): static
    {
        static::$customFieldModel = $model;

        return new self();
    }

    /**
     * Get the name of the custom field value model used by the application.
     */
    public static function valueModel(): string
    {
        return static::$valueModel;
    }

    /**
     * Get a new instance of the custom field value model.
     */
    public static function newValueModel(): mixed
    {
        $model = self::valueModel();

        return new $model;
    }

    /**
     * Specify the custom field value model that should be used by Custom Fields.
     */
    public static function useValueModel(string $model): static
    {
        static::$valueModel = $model;

        return new self();
    }

    /**
     * Get the name of the custom field option model used by the application.
     */
    public static function optionModel(): string
    {
        return static::$optionModel;
    }

    /**
     * Get a new instance of the custom field option model.
     */
    public static function newOptionModel(): mixed
    {
        $model = self::optionModel();

        return new $model;
    }

    /**
     * Specify the custom field option model that should be used by Custom Fields.
     */
    public static function useOptionModel(string $model): static
    {
        static::$optionModel = $model;

        return new self();
    }

    /**
     * Get the name of the custom field section model used by the application.
     */
    public static function sectionModel(): string
    {
        return static::$sectionModel;
    }

    /**
     * Get a new instance of the custom field section model.
     */
    public static function newSectionModel(): mixed
    {
        $model = self::sectionModel();

        return new $model;
    }

    /**
     * Specify the custom field section model that should be used by Custom Fields.
     */
    public static function useSectionModel(string $model): static
    {
        static::$sectionModel = $model;

        return new self();
    }
}
