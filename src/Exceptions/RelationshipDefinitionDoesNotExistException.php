<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Exceptions;

use Exception;

final class RelationshipDefinitionDoesNotExistException extends Exception
{
    public static function whenLinking(int|string $key): self
    {
        return new self(sprintf('Could not write links for relationship `%s` because it no longer exists', $key));
    }
}
