<?php

namespace Relaticle\CustomFields\Exceptions;

use Exception;

class FieldTypeNotOptionableException extends Exception
{
    public function __construct()
    {
        parent::__construct('This field type is not optionable.');
    }
}
