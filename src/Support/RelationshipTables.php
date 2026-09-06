<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Support;

use Illuminate\Support\Facades\Schema;

/**
 * The flag gates the relationship migrations, not what a record or field does once they
 * have run, and schema never changes mid-request, so this is worth memoising once per caller.
 */
final class RelationshipTables
{
    public static function exist(): bool
    {
        return once(fn (): bool => Schema::hasTable((string) config('custom-fields.database.table_names.custom_field_links')));
    }
}
