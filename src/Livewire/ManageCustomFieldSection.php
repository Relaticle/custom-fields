<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Livewire;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\ActionSize;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Relaticle\CustomFields\Filament\FormSchemas\FieldForm;
use Relaticle\CustomFields\Filament\FormSchemas\SectionForm;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Support\Utils;

class ManageCustomFieldSection extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public string $entityType;

    public CustomFieldSection $section;

    #[Computed]
    public function fields()
    {
        return $this->section->fields()->orderBy('sort_order')->withDeactivated()->get();
    }

    #[On('field-width-updated')]
    public function fieldWidthUpdated(int $fieldId, int $width): void
    {
        // Update the width
        CustomField::where('id', $fieldId)->update(['width' => $width]);

        // Re-fetch the fields
        $this->section->refresh();
    }

    #[On('field-deleted')]
    public function fieldDeleted(): void
    {
        $this->section->refresh();
    }

    public function updateFieldsOrder($sectionId, $fields): void
    {
        foreach ($fields as $index => $field) {
            CustomField::query()
                ->withDeactivated()
                ->where('id', $field)
                ->update([
                    'custom_field_section_id' => $sectionId,
                    'sort_order' => $index,
                ]);
        }
    }

    public function actions(): ActionGroup
    {
        return ActionGroup::make([
            $this->editAction(),
            $this->activateAction(),
            $this->deactivateAction(),
            $this->deleteAction(),
        ]);
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->icon('heroicon-o-pencil')
            ->model(CustomFieldSection::class)
            ->record($this->section)
            ->form(SectionForm::entityType($this->entityType)->schema())
            ->fillForm($this->section->toArray())
            ->action(fn (array $data) => $this->section->update($data))
            ->modalWidth('max-w-2xl');
    }

    public function activateAction(): Action
    {
        return Action::make('activate')
            ->icon('heroicon-o-archive-box')
            ->model(CustomFieldSection::class)
            ->record($this->section)
            ->visible(fn (CustomFieldSection $record): bool => ! $record->isActive())
            ->action(fn () => $this->section->activate());
    }

    public function deactivateAction(): Action
    {
        return Action::make('deactivate')
            ->icon('heroicon-o-archive-box-x-mark')
            ->model(CustomFieldSection::class)
            ->record($this->section)
            ->visible(fn (CustomFieldSection $record): bool => $record->isActive())
            ->action(fn () => $this->section->deactivate());
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->requiresConfirmation()
            ->icon('heroicon-o-trash')
            ->model(CustomFieldSection::class)
            ->record($this->section)
            ->visible(fn (CustomFieldSection $record): bool => ! $record->isActive() && ! $record->isSystemDefined())
            ->action(fn () => $this->section->delete() && $this->dispatch('section-deleted'));
    }

    public function createFieldAction(): Action
    {
        return Action::make('createField')
            ->size(ActionSize::ExtraSmall)
            ->label(__('custom-fields::custom-fields.field.form.add_field'))
            ->model(CustomField::class)
            ->form(FieldForm::schema(withOptionsRelationship: false))
            ->fillForm([
                'entity_type' => $this->entityType,
            ])
            ->mutateFormDataUsing(function (array $data): array {
                if (Utils::isTenantEnabled()) {
                    $data[config('custom-fields.column_names.tenant_foreign_key')] = Filament::getTenant()?->id;
                }

                return [
                    ...$data,
                    'entity_type' => $this->entityType,
                    'custom_field_section_id' => $this->section->id,
                ];
            })
            ->action(function (array $data) {
                $options = collect($data['options'] ?? [])->filter()
                    ->map(function ($option) {
                        $data = [
                            'name' => $option,
                        ];

                        if (Utils::isTenantEnabled()) {
                            $data[config('custom-fields.column_names.tenant_foreign_key')] = Filament::getTenant()?->id;
                        }

                        return $data;
                    })
                    ->values();

                unset($data['options']);

                $customField = CustomField::create($data);

                $customField->options()->createMany($options);
            })
            ->slideOver();
    }

    public function render()
    {
        return view('custom-fields::livewire.manage-custom-field-section');
    }
}
