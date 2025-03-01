<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Columns;

use Closure;
use Filament\Support\Components\Component;
use Filament\Tables\Columns\Column as BaseColumn;
use Filament\Tables\Columns\TextColumn as BaseTextColumn;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Queries\ColumnSearchableQuery;
use Relaticle\CustomFields\Support\FieldTypeUtils;

class DateTimeColumn extends Component implements ColumnInterface
{
    protected ?Closure $locale = null;

    public function make(CustomField $customField): BaseColumn
    {
        $static = BaseTextColumn::make("custom_fields.$customField->code");

        self::configure();

        $static
            ->sortable(
                condition: ! $customField->settings->encrypted,
                query: function (Builder $query, string $direction) use ($customField): Builder {
                    $table = $query->getModel()->getTable();
                    $key = $query->getModel()->getKeyName();

                    return $query->orderBy(
                        $customField->values()
                            ->select($customField->getValueColumn())
                            ->whereColumn('custom_field_values.entity_id', "$table.$key")
                            ->limit(1),
                        $direction
                    );
                }
            )
            ->searchable(
                condition: $customField->settings->searchable,
                query: fn (Builder $query, string $search) => (new ColumnSearchableQuery)->builder($query, $customField, $search),
            )
            ->label($customField->name)
            ->getStateUsing(function ($record) use ($customField) {
                $value = $record->getCustomFieldValue($customField);

                if ($this->locale) {
                    $value = $this->locale->call($this, $value);
                }

                if ($value && $customField->type === CustomFieldType::DATE_TIME) {
                    return $value->format(FieldTypeUtils::getDateTimeFormat());
                }

                if ($value && $customField->type === CustomFieldType::DATE) {
                    return $value->format(FieldTypeUtils::getDateFormat());
                }

                return $value;
            });

        return $static;
    }

    /**
     * @return $this
     */
    public function localize(Closure $locale): static
    {
        $this->locale = $locale;

        return $this;
    }
}
