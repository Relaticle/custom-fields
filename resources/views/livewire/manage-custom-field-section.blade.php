<x-filament::section
        :after-header="$this->actions()"
        x-sortable-item="{{ $section->id }}"
        id="{{ $section->id }}"
        compact>

    <x-slot name="heading">
        <div class="flex justify-between">
            <div class="flex items-center gap-x-1">
                <x-filament::icon-button
                        icon="heroicon-m-bars-4"
                        color="gray"
                        x-sortable-handle
                />

                {{$section->name }}

                @if(!$section->isActive())
                    <x-filament::badge color="warning" size="sm">
                        {{ __('custom-fields::custom-fields.common.inactive') }}
                    </x-filament::badge>
                @endif
            </div>
        </div>
    </x-slot>


    <div
            x-sortable
            x-sortable-group="fields"
            data-section-id="{{ $section->id }}"
            default="12"
            class="fi-sc  fi-sc-has-gap fi-grid lg:fi-grid-cols"
            style="--cols-lg: repeat(2, minmax(0, 1fr)); --cols-default: repeat(1, minmax(0, 1fr));"
            @end.stop="$wire.updateFieldsOrder($event.to.getAttribute('data-section-id'), $event.to.sortable.toArray())"
    >
        @foreach ($this->fields as $field)
            @livewire('manage-custom-field', ['field' => $field], key($field->id . $field->width->value . str()->random(16)))
        @endforeach

        @if(!count($this->fields))
            <div>
                <x-filament::icon icon="heroicon-o-x-mark"/>

                <span class="text-gray-500 dark:text-gray-400">
                    No fields added yet.
                </span>

                <span class="text-gray-500 dark:text-gray-400">
                    Add or drag fields here.
                <span>
            </div>
        @endempty
    </div>

    <x-slot name="footer">
        {{ $this->createFieldAction() }}
    </x-slot>

    <x-filament-actions::modals/>

</x-filament::section>
