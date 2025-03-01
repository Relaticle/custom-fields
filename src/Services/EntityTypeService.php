<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

class EntityTypeService extends AbstractOptionsService
{
    protected static string $allowedConfigKey = 'custom-fields.allowed_entity_resources';

    protected static string $disallowedConfigKey = 'custom-fields.disallowed_entity_resources';
}
