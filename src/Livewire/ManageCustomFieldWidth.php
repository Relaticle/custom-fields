<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Livewire;

use Livewire\Component;

class ManageCustomFieldWidth extends Component
{
    public $selectedWidth = 100;

    public $widthOptions = [
        25, 33, 50, 66, 75, 100,
    ];

    public $widthMap = [
        '25' => 'col-span-3',
        '33' => 'col-span-4',
        '50' => 'col-span-6',
        '66' => 'col-span-8',
        '75' => 'col-span-9',
        '100' => 'col-span-12',
    ];

    public $fieldId;

    public function mount($selectedWidth, $fieldId): void
    {
        $this->selectedWidth = $selectedWidth;
        $this->fieldId = $fieldId;
    }

    public function render()
    {
        return view('custom-fields::livewire.manage-custom-field-width');
    }
}
