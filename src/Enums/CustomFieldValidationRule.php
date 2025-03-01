<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Enums;

use Filament\Support\Contracts\HasLabel;

enum CustomFieldValidationRule: string implements HasLabel
{
    case ACCEPTED = 'accepted';
    case ACCEPTED_IF = 'accepted_if';
    case ACTIVE_URL = 'active_url';
    case AFTER = 'after';
    case AFTER_OR_EQUAL = 'after_or_equal';
    case ALPHA = 'alpha';
    case ALPHA_DASH = 'alpha_dash';
    case ALPHA_NUM = 'alpha_num';
    case ARRAY = 'array';
    case ASCII = 'ascii';
    case BEFORE = 'before';
    case BEFORE_OR_EQUAL = 'before_or_equal';
    case BETWEEN = 'between';
    case BOOLEAN = 'boolean';
    case CONFIRMED = 'confirmed';
    case CURRENT_PASSWORD = 'current_password';
    case DATE = 'date';
    case DATE_EQUALS = 'date_equals';
    case DATE_FORMAT = 'date_format';
    case DECIMAL = 'decimal';
    case DECLINED = 'declined';
    case DECLINED_IF = 'declined_if';
    case DIFFERENT = 'different';
    case DIGITS = 'digits';
    case DIGITS_BETWEEN = 'digits_between';
    case DIMENSIONS = 'dimensions';
    case DISTINCT = 'distinct';
    case DOESNT_START_WITH = 'doesnt_start_with';
    case DOESNT_END_WITH = 'doesnt_end_with';
    case EMAIL = 'email';
    case ENDS_WITH = 'ends_with';
    case ENUM = 'enum';
    case EXCLUDE = 'exclude';
    case EXCLUDE_IF = 'exclude_if';
    case EXCLUDE_UNLESS = 'exclude_unless';
    case EXISTS = 'exists';
    case FILE = 'file';
    case FILLED = 'filled';
    case GT = 'gt';
    case GTE = 'gte';
    case IMAGE = 'image';
    case IN = 'in';
    case IN_ARRAY = 'in_array';
    case INTEGER = 'integer';
    case IP = 'ip';
    case IPV4 = 'ipv4';
    case IPV6 = 'ipv6';
    case JSON = 'json';
    case LT = 'lt';
    case LTE = 'lte';
    case MAC_ADDRESS = 'mac_address';
    case MAX = 'max';
    case MAX_DIGITS = 'max_digits';
    case MIMES = 'mimes';
    case MIMETYPES = 'mimetypes';
    case MIN = 'min';
    case MIN_DIGITS = 'min_digits';
    case MULTIPLE_OF = 'multiple_of';
    case NOT_IN = 'not_in';
    case NOT_REGEX = 'not_regex';
    case NUMERIC = 'numeric';
    case PASSWORD = 'password';
    case PRESENT = 'present';
    case PROHIBITED = 'prohibited';
    case PROHIBITED_IF = 'prohibited_if';
    case PROHIBITED_UNLESS = 'prohibited_unless';
    case PROHIBITS = 'prohibits';
    case REGEX = 'regex';
    case REQUIRED = 'required';
    case REQUIRED_IF = 'required_if';
    case REQUIRED_UNLESS = 'required_unless';
    case REQUIRED_WITH = 'required_with';
    case REQUIRED_WITH_ALL = 'required_with_all';
    case REQUIRED_WITHOUT = 'required_without';
    case REQUIRED_WITHOUT_ALL = 'required_without_all';
    case SAME = 'same';
    case SIZE = 'size';
    case STARTS_WITH = 'starts_with';
    case STRING = 'string';
    case TIMEZONE = 'timezone';
    case UNIQUE = 'unique';
    case UPPERCASE = 'uppercase';
    case URL = 'url';
    case UUID = 'uuid';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'name')
        );
    }

    /**
     * Get the count of allowed parameters for a given validation rule.
     */
    public function allowedParameterCount(): int
    {
        return match ($this) {
            // Rules with unlimited parameters
            self::ACCEPTED_IF, self::DECLINED_IF, self::DIFFERENT, self::ENDS_WITH,
            self::IN, self::NOT_IN, self::REQUIRED_IF, self::REQUIRED_WITH, self::REQUIRED_WITH_ALL,
            self::REQUIRED_WITHOUT, self::REQUIRED_WITHOUT_ALL, self::STARTS_WITH,
            self::EXCLUDE_IF, self::EXCLUDE_UNLESS, self::PROHIBITS => -1,

            // Rules with exactly two parameters
            self::BETWEEN, self::DECIMAL, self::REQUIRED_UNLESS, self::PROHIBITED_IF,
            self::PROHIBITED_UNLESS, self::DIGITS_BETWEEN => 2,

            // Rules with one parameter
            self::SIZE, self::MAX, self::MIN, self::DIGITS, self::DATE_EQUALS,
            self::DATE_FORMAT, self::AFTER, self::AFTER_OR_EQUAL, self::BEFORE,
            self::BEFORE_OR_EQUAL, self::EXISTS, self::UNIQUE, self::GT, self::GTE,
            self::LT, self::LTE, self::MAX_DIGITS, self::MIN_DIGITS, self::MULTIPLE_OF => 1,

            // Default case for any unspecified rules
            default => 0
        };
    }

    /**
     * Check if the validation rule has any parameters.
     */
    public function hasParameter(): bool
    {
        $allowedCount = $this->allowedParameterCount();

        return $allowedCount > 0 || $allowedCount === -1;
    }

    public function getLabel(): string
    {
        return __('custom-fields::custom-fields.validation.labels.' . $this->name);
    }

    public function getDescription(): string
    {
        return __('custom-fields::custom-fields.validation.descriptions.' . $this->name);
    }

    public static function hasParameterForRule(?string $rule): bool
    {
        if ($rule === null) {
            return false;
        }

        return self::tryFrom($rule)?->hasParameter() ?? false;
    }

    public static function getAllowedParametersCountForRule(?string $rule): int
    {
        if ($rule === null) {
            return 0;
        }

        // If we get -1 as the allowed parameter count, it means that the rule allows any number of parameters.
        // Otherwise, we return the allowed parameter count.
        $allowedCount = self::tryFrom($rule)?->allowedParameterCount();

        return $allowedCount === -1 ? 30 : $allowedCount ?? 0;
    }

    public static function getDescriptionForRule(?string $rule): string
    {
        if ($rule === null) {
            return __('custom-fields::custom-fields.validation.select_rule_description');
        }

        return self::tryFrom($rule)?->getDescription() ?? __('custom-fields::custom-fields.validation.select_rule_description');
    }

    /**
     * Get the label for a given validation rule.
     *
     * @param  string  $rule  The validation rule.
     * @param  array<string, string>  $parameters  The parameters to be passed to the validation rule.
     * @return string The label for the given validation rule.
     */
    public static function getLabelForRule(string $rule, array $parameters = []): string
    {
        $enum = self::tryFrom($rule);

        if (! $enum instanceof CustomFieldValidationRule) {
            return '';
        }

        $label = $enum->getLabel();
        if ($parameters !== []) {
            $values = implode(', ', array_column($parameters, 'value'));
            $label .= ' (' . $values . ')';
        }

        return $label;
    }
}
