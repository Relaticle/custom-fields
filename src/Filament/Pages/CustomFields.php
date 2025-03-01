<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Relaticle\CustomFields\CustomFieldsPlugin;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Filament\FormSchemas\SectionForm;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Services\EntityTypeService;
use Relaticle\CustomFields\Support\Utils;

class CustomFields extends Page
{
    protected static ?string $navigationIcon = 'heroicon-m-document-text';

    protected static string $view = 'custom-fields::filament.pages.custom-fields-next';

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = true;

    #[Url(history: true, keep: true)]
    public $currentEntityType;

    public function mount()
    {
        if (! $this->currentEntityType) {
            $this->setCurrentEntityType(EntityTypeService::getDefaultOption());
        }
    }

    #[Computed]
    public function sections(): Collection
    {
        return CustomFieldSection::query()
            ->withDeactivated()
            ->forEntityType($this->currentEntityType)
            ->with([
                'fields' => function ($query) {
                    $query->forMorphEntity($this->currentEntityType)
                        ->orderBy('sort_order');
                },
            ])
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function entityTypes(): Collection
    {
        return EntityTypeService::getOptions();
    }

    public function setCurrentEntityType($entityType): void
    {
        $this->currentEntityType = $entityType;
    }

    public function createSectionAction(): Action
    {
        return Action::make('createSection')
            ->size(ActionSize::ExtraSmall)
            ->label(__('custom-fields::custom-fields.section.form.add_section'))
            ->icon('heroicon-s-plus')
            ->color('gray')
            ->button()
            ->outlined()
            ->extraAttributes([
                'class' => 'h-36 flex justify-center items-center rounded-lg border-gray-300 hover:border-gray-400 border-dashed',
            ])
            ->form(SectionForm::entityType($this->currentEntityType)->schema())
            ->action(fn (array $data) => $this->storeSection($data))
            ->modalWidth('max-w-2xl');
    }

    public function updateSectionsOrder($sections): void
    {
        foreach ($sections as $index => $section) {
            CustomFieldSection::query()
                ->withDeactivated()
                ->where('id', $section)
                ->update([
                    'sort_order' => $index,
                ]);
        }
    }

    private function storeSection(array $data): CustomFieldSection
    {
        if (Utils::isTenantEnabled()) {
            $data[config('custom-fields.column_names.tenant_foreign_key')] = Filament::getTenant()?->id;
        }

        $data['type'] ??= CustomFieldSectionType::SECTION->value;
        $data['entity_type'] = $this->currentEntityType;

        return CustomFieldSection::create($data);
    }

    #[On('section-deleted')]
    public function sectionDeleted(): void
    {
        $this->sections = $this->sections->filter(fn ($section) => $section->exists);
    }

    public static function getCluster(): ?string
    {
        return Utils::getResourceCluster() ?? static::$cluster;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Utils::isResourceNavigationRegistered();
    }

    public static function getNavigationGroup(): ?string
    {
        return Utils::isResourceNavigationGroupEnabled()
            ? __('custom-fields::custom-fields.nav.group')
            : '';
    }

    public static function getNavigationLabel(): string
    {
        return __('custom-fields::custom-fields.nav.label');
    }

    public static function getNavigationIcon(): string
    {
        return __('custom-fields::custom-fields.nav.icon');
    }

    public static function getNavigationSort(): ?int
    {
        return Utils::getResourceNavigationSort();
    }

    public static function getSlug(): string
    {
        return Utils::getResourceSlug();
    }

    public static function canAccess(): bool
    {
        return CustomFieldsPlugin::get()->isAuthorized();
    }
}
