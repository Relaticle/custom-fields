<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Tables\Columns;

use Closure;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Tables\Columns\Column;
use Illuminate\Contracts\Container\BindingResolutionException;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Support\Utils;

final class CustomFieldsColumn
{
    use EvaluatesClosures;

    private HasCustomFields $instance;

    private bool | Closure $isToggleable = true;

    private bool | Closure $isToggledHiddenByDefault = true;

    public function make(string $model): static
    {
        $this->instance = app($model);
        
        return $this;
    }

    /**
     * @return array<int, Column>
     */
    public function all(): array
    {
        if (Utils::isTableColumnsEnabled() === false) {
            return [];
        }

        $fieldColumnFactory = app(FieldColumnFactory::class);

        return $this->instance
            ->customFields()
            ->visibleInList()
            ->with('options')
            ->get()
            ->map(
                fn (CustomField $customField): Column => $fieldColumnFactory
                    ->create($customField)
                    ->toggleable(
                        condition: Utils::isTableColumnsToggleableEnabled() && $this->isToggleable(),
                        isToggledHiddenByDefault: $customField->settings
                            ->list_toggleable_hidden && $this->isToggledHiddenByDefault()
                    )
            )
            ->toArray();
    }

    /**
     * @return array<int, Column>
     *
     * @throws BindingResolutionException
     */
    public static function forRelationManager(
        RelationManager $relationManager
    ): array {
        $model = $relationManager->getRelationship()->getModel();

        if (! $model instanceof HasCustomFields) {
            return [];
        }

        return (new static)->make($model::class)->all();
    }

    public function toggleable(bool | Closure $condition = true, bool | Closure $isToggledHiddenByDefault = false): static
    {
        $this->isToggleable = $condition;
        $this->toggledHiddenByDefault($isToggledHiddenByDefault);

        return $this;
    }

    public function toggledHiddenByDefault(bool | Closure $condition = true): static
    {
        $this->isToggledHiddenByDefault = $condition;

        return $this;
    }

    public function isToggleable(): bool
    {
        return (bool) $this->evaluate($this->isToggleable);
    }

    public function isToggledHiddenByDefault(): bool
    {
        return (bool) $this->evaluate($this->isToggledHiddenByDefault);
    }
}
