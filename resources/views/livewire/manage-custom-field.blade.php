<div
        id="{{ $field->id }}"
        class="fi-section fi-compact fi-section-header {{ $field->width->getSpanValue() }}"
        x-sortable-item="{{ $field->id }}"
        compact
>
        <div class="flex justify-between">
            <div class="flex items-center gap-x-2 w-full" x-sortable-handle>
                <x-filament::icon-button
                        icon="heroicon-m-bars-3"
                        color="gray"
                />

                <x-filament::icon
                        :icon="$field->type->getIcon()"
                        class="h-5 w-5 text-gray-500 dark:text-gray-400"
                        :aria-label="$field->name"
                />

                {{ $this->editAction()->icon(false)->label($field->name)->link() }}

                @if(!$field->isActive())
                    <x-filament::badge color="warning" size="sm">
                        {{ __('custom-fields::custom-fields.common.inactive') }}
                    </x-filament::badge>
                @endif
            </div>

            <div class="flex items-center gap-x-1 px-2 py-0.5">

                <livewire:manage-custom-field-width
                        :selected-width="$field->width"
                        :field-id="$field->id"
                        wire:key="manage-custom-field-width-{{ $field->id }}"
                />

                {{ $this->actions() }}
            </div>
        </div>
    <x-filament-actions::modals/>
</div>

