<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components;

use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Cache;
use Relaticle\CustomFields\Enums\CustomFieldType;

class TypeField extends Select
{
    /**
     * Set up the component with a custom configuration.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->native(false)
            ->searchable()
            ->allowHtml()
            ->getSearchResultsUsing(fn($search) => $this->getFilteredOptions($search))
            ->options(fn() => $this->getAllFormattedOptions());
    }

    /**
     * Get filtered options based on search query.
     */
    protected function getFilteredOptions(?string $search = null): array
    {
        return CustomFieldType::optionsForSelect()
            ->when(!empty($search), fn($query) => $query->filter(fn(array $data) => stripos($data['label'], $search) !== false))
            ->mapWithKeys(
                fn(array $data): array => [
                    $data['value'] => $this->getHtmlOption($data)
                ]
            )
            ->toArray();
    }

    /**
     * Get all formatted options.
     */
    protected function getAllFormattedOptions(): array
    {
        return CustomFieldType::optionsForSelect()
            ->mapWithKeys(
                fn(array $data): array => [
                    $data['value'] => $this->getHtmlOption($data)
                ]
            )
            ->toArray();
    }

    /**
     * Render an HTML option for the select field.
     *
     * @param array $data
     * @return string The rendered HTML for the option
     * @throws \Throwable
     */
    public function getHtmlOption(array $data): string
    {
        return Cache::remember('custom-fields-type-field-view-' . $data['value'], 60, function () use ($data) {
            return view('custom-fields::filament.forms.type-field')
                ->with([
                    'label' => $data['label'],
                    'value' => $data['value'],
                    'icon' => $data['icon'],
                    'selected' => $this->getState(),
                ])
                ->render();
        });
    }
}
