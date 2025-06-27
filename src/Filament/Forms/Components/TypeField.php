<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Forms\Components;

use Override;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Cache;
use Relaticle\CustomFields\Enums\CustomFieldType;
use Throwable;

class TypeField extends Select
{
    /**
     * Set up the component with a custom configuration.
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->native(false)
            ->allowHtml()
            ->gridContainer()
            ->options(fn (): array => $this->getAllFormattedOptions());
    }

    /**
     * Get filtered options based on search query.
     *
     * @return array<string, string>
     */
    protected function getFilteredOptions(?string $search = null): array
    {
        // If search is null or empty string, return all options
        if ($search === null || trim($search) === '' || strlen($search) < 2) {
            return $this->getAllFormattedOptions();
        }

        return CustomFieldType::optionsForSelect()
            ->filter(fn (array $data): bool => stripos($data['label'], $search) !== false)
            ->mapWithKeys(
                fn (array $data): array => [
                    $data['value'] => $this->getHtmlOption($data),
                ]
            )
            ->toArray();
    }

    /**
     * Get all formatted options.
     *
     * @return array<string, string>
     */
    protected function getAllFormattedOptions(): array
    {
        return CustomFieldType::optionsForSelect()
            ->mapWithKeys(
                fn (array $data): array => [
                    $data['value'] => $this->getHtmlOption($data),
                ]
            )
            ->toArray();
    }

    /**
     * Render an HTML option for the select field.
     *
     * @param  array{label: string, value: string, icon: string}  $data
     * @return string The rendered HTML for the option
     *
     * @throws Throwable
     */
    public function getHtmlOption(array $data): string
    {
        $cacheKey = "custom-fields-type-field-view-{$data['value']}";

        return Cache::remember(
            key: $cacheKey,
            ttl: 60,
            callback: fn (): string => view('custom-fields::filament.forms.type-field')
                ->with([
                    'label' => $data['label'],
                    'value' => $data['value'],
                    'icon' => $data['icon'],
                    'selected' => $this->getState(),
                ])
                ->render()
        );
    }
}
