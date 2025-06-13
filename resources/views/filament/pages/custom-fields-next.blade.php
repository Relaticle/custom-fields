<x-filament-panels::page>
    <x-filament::tabs label="Content tabs" contained>
        @foreach ($this->entityTypes as $key => $label)
            <x-filament::tabs.item active="{{ $key === $this->currentEntityType }}"
                                   wire:click="setCurrentEntityType('{{ addslashes($key) }}')">
                {{ $label }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    <div class="custom-fields-component">
        <div
            x-sortable
            wire:end.stop="updateSectionsOrder($event.target.sortable.toArray())"
            class="flex flex-col gap-y-6"
            x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('custom-fields', 'relaticle/custom-fields'))]"
        >
            @foreach ($this->sections as $section)
                @livewire('manage-custom-field-section', ['entityType' => $this->currentEntityType, 'section' => $section], key($section->id . str()->random(16)))
            @endforeach

            @if(!count($this->sections))
{{--                <x-filament-schemas::grid default="12">--}}
{{--                    <x-filament-tables::empty-state--}}
{{--                        icon="heroicon-o-rectangle-group"--}}
{{--                        heading="No sections yet"--}}
{{--                        description="Start by creating a new section to organize your custom fields."--}}
{{--                    />--}}
                    --Empty state for no sections yet--
{{--                </x-filament-schemas::grid>--}}
            @endempty

            {{ $this->createSectionAction }}
        </div>
    </div>
</x-filament-panels::page>
