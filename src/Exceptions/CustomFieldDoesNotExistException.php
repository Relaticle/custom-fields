<?php

namespace Relaticle\CustomFields\Exceptions;

use Exception;

class CustomFieldDoesNotExistException extends Exception
{
    public static function whenUpdating(string $code): self
    {
        return new self(sprintf('Could not update custom field `%s` because it does not exist', $code));
    }

    public static function whenDeleting(string $code): self
    {
        return new self(sprintf('Could not delete custom field `%s` because it does not exist', $code));
    }

    public static function whenActivating(string $code): self
    {
        return new self(sprintf('Could not activate custom field `%s` because it does not exist', $code));
    }

    public static function whenDeactivating(string $code): self
    {
        return new self(sprintf('Could not deactivate custom field `%s` because it does not exist', $code));
    }
}
