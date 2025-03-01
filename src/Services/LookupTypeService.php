<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

class LookupTypeService extends AbstractOptionsService
{
    protected static string $allowedConfigKey = 'custom-fields.allowed_lookup_resources';

    protected static string $disallowedConfigKey = 'custom-fields.disallowed_lookup_resources';
}
