<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Livewire;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Concerns\InteractsWithRecord;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\View\View;
use Livewire\Component;
use Relaticle\CustomFields\Filament\FormSchemas\FieldForm;
use Relaticle\CustomFields\Models\CustomField;

class ManageCustomField extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithRecord;

    public CustomField $field;

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
            ->model(CustomField::class)
            ->record($this->field)
            ->form(FieldForm::schema())
            ->fillForm($this->field->toArray())
            ->action(fn (array $data) => $this->field->update($data))
            ->slideOver();
    }

    public function activateAction(): Action
    {
        return Action::make('activate')
            ->icon('heroicon-o-archive-box')
            ->model(CustomField::class)
            ->record($this->field)
            ->visible(fn (CustomField $record): bool => ! $record->isActive())
            ->action(fn () => $this->field->activate());
    }

    public function deactivateAction(): Action
    {
        return Action::make('deactivate')
            ->icon('heroicon-o-archive-box-x-mark')
            ->model(CustomField::class)
            ->record($this->field)
            ->visible(fn (CustomField $record): bool => $record->isActive())
            ->action(fn () => $this->field->deactivate());
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->requiresConfirmation()
            ->icon('heroicon-o-trash')
            ->model(CustomField::class)
            ->record($this->field)
            ->visible(fn (CustomField $record): bool => ! $record->isActive() && ! $record->isSystemDefined())
            ->action(fn () => $this->field->delete() && $this->dispatch('field-deleted'));
    }

    public function setWidth(int $fieldId, int $width): void
    {
        $this->dispatch('field-width-updated', $fieldId, $width);
    }

    public function render(): View
    {
        return view('custom-fields::livewire.manage-custom-field');
    }
}
