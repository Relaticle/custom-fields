<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components;

use Filament\Forms\Components\Select;
use Relaticle\CustomFields\Enums\CustomFieldType;

class TypeField extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->native(false)
            ->allowHtml()
            ->options(fn (): array => collect(CustomFieldType::options())->mapWithKeys(fn ($name, $value) => [$value => $this->getHtmlOption($name, $value)])->toArray());
    }

    public function getHtmlOption($name, $value)
    {
        return view('custom-fields::filament.forms.type-field')
            ->with('label', $name)
            ->with('value', $value)
            ->with('icon', CustomFieldType::tryFrom($value)->getIcon())
            ->with('selected', $this->getState())
            ->render();
    }
}
