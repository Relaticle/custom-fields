<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Infolists;

use Filament\Infolists\Components\Entry;
use Relaticle\CustomFields\Integration\AbstractComponentFactory;
use Relaticle\CustomFields\Models\CustomField;

/**
 * @extends AbstractComponentFactory<FieldInfolistsComponentInterface, Entry>
 */
final class FieldInfolistsFactory extends AbstractComponentFactory
{
    public function create(CustomField $customField): Entry
    {
        /** @var FieldInfolistsComponentInterface */
        $component = $this->createComponent($customField, 'infolist_entry', FieldInfolistsComponentInterface::class);

        return $component->make($customField)
            ->columnSpan($customField->width->getSpanValue())
            ->inlineLabel(false);
    }
}
