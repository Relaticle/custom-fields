<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column as BaseColumn;
use Filament\Tables\Columns\TextColumn as BaseTextColumn;
use Illuminate\Support\Collection;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\ValueResolver\LookupMultiValueResolver;
use Relaticle\CustomFields\Support\Utils;

final readonly class MultiValueColumn implements ColumnInterface
{
    public function __construct(public LookupMultiValueResolver $valueResolver) {}

    public function make(CustomField $customField): BaseColumn
    {
        $column = BaseTextColumn::make("custom_fields.$customField->code")
            ->label($customField->name)
            ->sortable(false)
            ->searchable(false);
            
        if (Utils::isOptionColorsFeatureEnabled() && $customField->settings->enable_option_colors && !$customField->lookup_type) {
            $column->formatStateUsing(function ($state) use ($customField): string {
                if (empty($state)) {
                    return '';
                }
                
                $values = $this->valueResolver->resolve(null, $customField, $state);
                
                if (is_array($values)) {
                    $options = $customField->options->pluck('settings.color', 'id');
                    $html = '';
                    
                    foreach ($values as $key => $value) {
                        $optionId = $key;
                        $color = $options[$optionId] ?? null;
                        
                        if ($color) {
                            $textColor = Utils::getTextColor($color);
                            $html .= "<span style='background-color: {$color}; color: {$textColor}; padding: 2px 6px; border-radius: 4px; margin-right: 4px;'>{$value}</span>";
                        } else {
                            $html .= "<span style='background-color: #e5e7eb; color: #374151; padding: 2px 6px; border-radius: 4px; margin-right: 4px;'>{$value}</span>";
                        }
                    }
                    
                    return $html;
                }
                
                return $values;
            })
            ->html();
        } else {
            $column->getStateUsing(fn ($record) => $this->valueResolver->resolve($record, $customField));
        }
        
        return $column;
    }
}
