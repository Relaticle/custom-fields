<x-filament::section
    :afterHeader="$this->actions()"
    x-sortable-item="{{ $section->id }}" id="{{ $section->id }}" compact collapsible persist-collapsed>

{{--    {{ $this->actions() }}--}}

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


{{--    <x-filament::grid--}}
{{--        x-sortable--}}
{{--        x-sortable-group="fields"--}}
{{--        data-section-id="{{ $section->id }}"--}}
{{--        default="12"--}}
{{--        class="gap-4"--}}
{{--        @end.stop="$wire.updateFieldsOrder($event.to.getAttribute('data-section-id'), $event.to.sortable.toArray())"--}}
{{--    >--}}
        @foreach ($this->fields as $field)
            @livewire('manage-custom-field', ['field' => $field], key($field->id . $field->width->value . str()->random(16)))
        @endforeach

        @if(!count($this->fields))
{{--            <x-filament::grid.column default="12">--}}
{{--                <x-filament-tables::empty-state--}}
{{--                    icon="heroicon-o-x-mark"--}}
{{--                    heading="No fields"--}}
{{--                    description="Add or drag fields here"--}}
{{--                />--}}
{{--            </x-filament::grid.column>--}}
            -- Empty state for no fields yet --
        @endempty
{{--    </x-filament::grid>--}}

    <x-slot name="footer">
        {{ $this->createFieldAction() }}
    </x-slot>

{{--    <x-filament-actions::modals/>--}}

</x-filament::section>
