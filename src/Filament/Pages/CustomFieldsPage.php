<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Override;
use Relaticle\CustomFields\CustomFields as CustomFieldsModel;
use Relaticle\CustomFields\CustomFieldsPlugin;
use Relaticle\CustomFields\Enums\CustomFieldSectionType;
use Relaticle\CustomFields\Filament\Schemas\SectionForm;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Services\EntityTypeService;
use Relaticle\CustomFields\Support\Utils;

class CustomFieldsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-m-document-text';

    protected string $view = 'custom-fields::filament.pages.custom-fields-next';

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = true;

    #[Url(history: true, keep: true)]
    public $currentEntityType;

    public function mount(): void
    {
        if (! $this->currentEntityType) {
            $this->setCurrentEntityType(EntityTypeService::getDefaultOption());
        }
    }

    #[Computed]
    public function sections(): Collection
    {
        return CustomFieldsModel::newSectionModel()->query()
            ->withDeactivated()
            ->forEntityType($this->currentEntityType)
            ->with([
                'fields' => function ($query): void {
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
            ->size(Size::ExtraSmall)
            ->label(__('custom-fields::custom-fields.section.form.add_section'))
            ->icon('heroicon-s-plus')
            ->color('gray')
            ->button()
            ->outlined()
            ->extraAttributes([
                'class' => 'flex justify-center items-center rounded-lg border-gray-300 hover:border-gray-400 border-dashed',
            ])
            ->schema(SectionForm::entityType($this->currentEntityType)->schema())
            ->action(fn (array $data): CustomFieldSection => $this->storeSection($data))
            ->modalWidth(Width::TwoExtraLarge);
    }

    public function updateSectionsOrder($sections): void
    {
        $sectionModel = CustomFieldsModel::newSectionModel();

        foreach ($sections as $index => $section) {
            $sectionModel->query()
                ->withDeactivated()
                ->where($sectionModel->getKeyName(), $section)
                ->update([
                    'sort_order' => $index,
                ]);
        }
    }

    private function storeSection(array $data): CustomFieldSection
    {
        if (Utils::isTenantEnabled()) {
            $data[config('custom-fields.column_names.tenant_foreign_key')] = Filament::getTenant()?->getKey();
        }

        $data['type'] ??= CustomFieldSectionType::SECTION->value;
        $data['entity_type'] = $this->currentEntityType;

        return CustomFieldsModel::newSectionModel()->create($data);
    }

    #[On('section-deleted')]
    public function sectionDeleted(): void
    {
        $this->sections = $this->sections->filter(fn ($section) => $section->exists);
    }

    #[Override]
    public static function getCluster(): ?string
    {
        return Utils::getResourceCluster() ?? static::$cluster;
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return Utils::isResourceNavigationRegistered();
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return Utils::isResourceNavigationGroupEnabled()
            ? __('custom-fields::custom-fields.nav.group')
            : '';
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return __('custom-fields::custom-fields.nav.label');
    }

    #[Override]
    public static function getNavigationIcon(): string
    {
        return __('custom-fields::custom-fields.nav.icon');
    }

    #[Override]
    public function getHeading(): string
    {
        return __('custom-fields::custom-fields.heading.title');
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        return Utils::getResourceNavigationSort();
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return Utils::getResourceSlug();
    }

    public static function canAccess(): bool
    {
        return CustomFieldsPlugin::get()->isAuthorized();
    }
}
