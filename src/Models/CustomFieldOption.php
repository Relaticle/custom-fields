<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Models;

use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Relaticle\CustomFields\Database\Factories\CustomFieldOptionFactory;
use Relaticle\CustomFields\Models\Scopes\SortOrderScope;
use Relaticle\CustomFields\Models\Scopes\TenantScope;

#[ScopedBy([TenantScope::class, SortOrderScope::class])]
final class CustomFieldOption extends Model
{
    /** @use HasFactory<CustomFieldOptionFactory> */
    use HasFactory;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        if (! isset($this->table)) {
            $this->setTable(config('custom-fields.table_names.custom_field_options'));
        }

        parent::__construct($attributes);
    }

    /**
     * @return BelongsTo<CustomField, CustomFieldOption>
     */
    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }
}
