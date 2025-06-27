<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Models;

use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Data\CustomFieldOptionSettingsData;
use Relaticle\CustomFields\Database\Factories\CustomFieldOptionFactory;
use Relaticle\CustomFields\Models\Scopes\SortOrderScope;
use Relaticle\CustomFields\Models\Scopes\TenantScope;

#[ScopedBy([TenantScope::class, SortOrderScope::class])]
class CustomFieldOption extends Model
{
    /** @use HasFactory<CustomFieldOptionFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'settings' => CustomFieldOptionSettingsData::class.':default',
    ];

    /**
     * The attributes that should be visible in arrays.
     * @var array<int, string>
     */
    protected $visible = [
        'id',
        'name',
        'settings',
        'sort_order',
        'custom_field_id',
    ];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        if ($this->table === null) {
            $this->setTable(config('custom-fields.table_names.custom_field_options'));
        }

        parent::__construct($attributes);
    }

    /**
     * @return BelongsTo<CustomField, $this>
     */
    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomFields::customFieldModel());
    }
}
