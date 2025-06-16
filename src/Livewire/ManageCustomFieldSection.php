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
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Relaticle\CustomFields\CustomFields;
use Relaticle\CustomFields\Filament\FormSchemas\FieldForm;
use Relaticle\CustomFields\Filament\FormSchemas\SectionForm;
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
        return $this->section->fields()->withDeactivated()->orderBy('sort_order')->get();
    }

    #[On('field-width-updated')]
    public function fieldWidthUpdated(int|string $fieldId, int $width): void
    {
        // Update the width
        $model = CustomFields::newCustomFieldModel();
        $model->where($model->getKeyName(), $fieldId)->update(['width' => $width]);

        // Re-fetch the fields
        $this->section->refresh();
    }

    #[On('field-deleted')]
    public function fieldDeleted(): void
    {
        $this->section->refresh();
    }

    public function updateFieldsOrder(int|string $sectionId, array $fields): void
    {
        $model = CustomFields::newCustomFieldModel();
        foreach ($fields as $index => $field) {
            $model->query()
                ->where($model->getKeyName(), $field)
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
            ->model(CustomFields::sectionModel())
            ->record($this->section)
            ->schema(SectionForm::entityType($this->entityType)->schema())
            ->fillForm($this->section->toArray())
            ->action(fn (array $data) => $this->section->update($data))
            ->modalWidth(Width::ExtraLarge);
    }

    public function activateAction(): Action
    {
        return Action::make('activate')
            ->icon('heroicon-o-archive-box')
            ->model(CustomFields::sectionModel())
            ->record($this->section)
            ->visible(fn (CustomFieldSection $record): bool => ! $record->isActive())
            ->action(fn () => $this->section->activate());
    }

    public function deactivateAction(): Action
    {
        return Action::make('deactivate')
            ->icon('heroicon-o-archive-box-x-mark')
            ->model(CustomFields::sectionModel())
            ->record($this->section)
            ->visible(fn (CustomFieldSection $record): bool => $record->isActive())
            ->action(fn () => $this->section->deactivate());
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->requiresConfirmation()
            ->icon('heroicon-o-trash')
            ->model(CustomFields::sectionModel())
            ->record($this->section)
            ->visible(fn (CustomFieldSection $record): bool => ! $record->isActive() && ! $record->isSystemDefined())
            ->action(fn () => $this->section->delete() && $this->dispatch('section-deleted'));
    }

    public function createFieldAction(): Action
    {
        return Action::make('createField')
            ->size(Size::ExtraSmall)
            ->label(__('custom-fields::custom-fields.field.form.add_field'))
            ->model(CustomFields::customFieldModel())
            ->schema(FieldForm::schema(withOptionsRelationship: false))
            ->fillForm([
                'entity_type' => $this->entityType,
            ])
            ->mutateDataUsing(function (array $data): array {
                if (Utils::isTenantEnabled()) {
                    $data[config('custom-fields.column_names.tenant_foreign_key')] = Filament::getTenant()?->getKey();
                }

                return [
                    ...$data,
                    'entity_type' => $this->entityType,
                    'custom_field_section_id' => $this->section->getKey(),
                ];
            })
            ->action(function (array $data) {
                $options = collect($data['options'] ?? [])->filter()
                    ->map(function ($option) {
                        $data = [
                            'name' => $option,
                        ];

                        if (Utils::isTenantEnabled()) {
                            $data[config('custom-fields.column_names.tenant_foreign_key')] = Filament::getTenant()?->getKey();
                        }

                        return $data;
                    })
                    ->values();

                unset($data['options']);

                $customField = CustomFields::newCustomFieldModel()->create($data);

                $customField->options()->createMany($options);
            })
            ->modalWidth(Width::ScreenLarge)
            ->slideOver();
    }

    public function render()
    {
        return view('custom-fields::livewire.manage-custom-field-section');
    }
}
