<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Forms\RecordSelectInput;

use Closure;
use Filament\Forms\Components\Concerns\HasNestedRecursiveValidationRules;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Contracts\HasNestedRecursiveValidationRules as HasNestedRecursiveValidationRulesContract;
use Filament\Forms\Components\Field;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Renderless;
use Relaticle\CustomFields\Data\AvatarConfiguration;
use Relaticle\CustomFields\Data\EntityConfigurationData;
use Relaticle\CustomFields\Enums\UiSurface;
use Relaticle\CustomFields\Facades\Entities;
use Relaticle\CustomFields\Filament\Integration\Support\RecordChips;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldRelationship;
use Relaticle\CustomFields\QueryBuilders\EntitySearchQuery;
use Relaticle\CustomFields\Services\Relationships\CardinalityGuard;
use Relaticle\CustomFields\Support\ViewFlavor;

/**
 * A custom Filament form field for selecting records from other entities
 * with search, avatars, and single/multiple mode support.
 *
 * Single value: Shows a searchable select dropdown
 * Multiple values: Shows pills + add button with searchable dropdown
 */
class RecordSelectInputComponent extends Field implements HasNestedRecursiveValidationRulesContract
{
    use HasExtraAlpineAttributes;
    use HasNestedRecursiveValidationRules;
    use HasPlaceholder;

    protected string $view = 'custom-fields::forms.record-select-input';

    protected bool|Closure $allowMultiple = false;

    protected int|Closure $maxValues = 1;

    protected string|Closure|null $lookupType = null;

    protected string|Closure|null $addLabel = null;

    protected string|Closure|null $emptyStateLabel = null;

    protected int|Closure $maxVisiblePills = 3;

    protected ?CustomField $customField = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->view(ViewFlavor::view(UiSurface::RecordPicker) ?? $this->view);

        $this->default([]);

        $this->afterStateHydrated(static function (RecordSelectInputComponent $component, mixed $state): void {
            if (is_array($state)) {
                return;
            }

            if ($state === null) {
                $component->state([]);

                return;
            }

            // Convert single value to array
            $component->state([$state]);
        });

        $this->dehydrateStateUsing(static function (RecordSelectInputComponent $component, mixed $state): array {
            if (! is_array($state)) {
                return $state !== null ? [$state] : [];
            }

            // The map form carries the confirmation the writer needs for a one-to-one steal,
            // so it travels whole; only its ids are cleaned.
            if (array_key_exists('ids', $state)) {
                return [
                    'ids' => self::filledIds($state['ids']),
                    'replace' => ($state['replace'] ?? false) === true,
                ];
            }

            return self::filledIds($state);
        });
    }

    /**
     * @return array<int, mixed>
     */
    private static function filledIds(mixed $ids): array
    {
        return is_array($ids)
            ? array_values(array_filter($ids, fn (mixed $value): bool => filled($value)))
            : [];
    }

    /**
     * The field the picker writes, so it can ask the same guard the writer asks before it
     * offers to move a record away from whoever holds it.
     */
    public function customField(?CustomField $customField): static
    {
        $this->customField = $customField;

        return $this;
    }

    public function getCustomField(): ?CustomField
    {
        return $this->customField;
    }

    public function allowMultiple(bool|Closure $allow = true): static
    {
        $this->allowMultiple = $allow;

        return $this;
    }

    public function getAllowMultiple(): bool
    {
        return $this->evaluate($this->allowMultiple);
    }

    public function maxValues(int|Closure $max): static
    {
        $this->maxValues = $max;

        return $this;
    }

    public function getMaxValues(): int
    {
        return $this->evaluate($this->maxValues);
    }

    public function lookupType(string|Closure|null $type): static
    {
        $this->lookupType = $type;

        return $this;
    }

    public function getLookupType(): ?string
    {
        return $this->evaluate($this->lookupType);
    }

    public function addLabel(string|Closure|null $label): static
    {
        $this->addLabel = $label;

        return $this;
    }

    public function getAddLabel(): string
    {
        return $this->evaluate($this->addLabel) ?? __('custom-fields::custom-fields.common.add');
    }

    public function emptyStateLabel(string|Closure|null $label): static
    {
        $this->emptyStateLabel = $label;

        return $this;
    }

    public function getEmptyStateLabel(): string
    {
        return $this->evaluate($this->emptyStateLabel) ?? $this->getPlaceholder() ?? __('custom-fields::custom-fields.record.empty_state_label');
    }

    public function maxVisiblePills(int|Closure $max): static
    {
        $this->maxVisiblePills = $max;

        return $this;
    }

    public function getMaxVisiblePills(): int
    {
        return $this->evaluate($this->maxVisiblePills);
    }

    /**
     * Characters required before the field issues a filtered lookup query.
     *
     * The view reads the same value, so raising it cannot leave the client
     * asking for a filtered search the server answers with an unfiltered page.
     */
    public function getMinSearchLength(): int
    {
        return (int) config('custom-fields.selects.record.min_search_length', 2);
    }

    /**
     * Get entity configuration for the lookup type.
     */
    public function getEntityConfiguration(): ?EntityConfigurationData
    {
        $lookupType = $this->getLookupType();

        if ($lookupType === null) {
            return null;
        }

        return Entities::getEntity($lookupType);
    }

    /**
     * Prepare entity query with common attributes.
     *
     * @return array{entity: EntityConfigurationData, model: Model, query: Builder<Model>, keyName: string, titleAttribute: string, avatarConfig: ?AvatarConfiguration}|null
     */
    private function prepareEntityQuery(): ?array
    {
        $entity = $this->getEntityConfiguration();

        if (! $entity instanceof EntityConfigurationData) {
            return null;
        }

        $model = $entity->createModelInstance();

        return [
            'entity' => $entity,
            'model' => $model,
            'query' => $model->newQuery(),
            'keyName' => $model->getKeyName(),
            'titleAttribute' => $entity->getPrimaryAttribute(),
            'avatarConfig' => $entity->getAvatarConfiguration(),
        ];
    }

    /**
     * Apply a deterministic order to a lookup query.
     *
     * Without one, LIMIT returns arbitrary rows and the initial page can change
     * between renders. The default is the model key: it is backed by the primary
     * key index, so ordering costs no more than the unordered query it replaces.
     * Measured on a 50k-row table, ordering by an unindexed column instead costs
     * roughly 176x the time and 205x the buffers, because every render sorts the
     * whole tenant.
     *
     * A configured column is trusted and the model key is appended to it, so rows
     * sharing a value still come back in a fixed sequence. Column existence is
     * resolved without a schema query: a runtime Schema::hasColumn() call would be
     * a per-request round trip. The one exception is the documented 'updated_at',
     * which falls back to the key on a model that opts out of timestamps.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    private function applyLookupOrder(Builder $query, Model $model): Builder
    {
        $column = config('custom-fields.selects.record.order_column');
        $direction = (string) config('custom-fields.selects.record.order_direction', 'desc');
        $key = $model->getQualifiedKeyName();

        if (! is_string($column) || $column === '') {
            return $query->orderBy($key, $direction);
        }

        if ($column === 'updated_at' && ! $model->usesTimestamps()) {
            return $query->orderBy($key, $direction);
        }

        return $query
            ->orderBy($query->qualifyColumn($column), $direction)
            ->orderBy($key, $direction);
    }

    private function lookupLimit(): int
    {
        return (int) config('custom-fields.selects.record.limit', 50);
    }

    /**
     * Search for records matching the query.
     *
     * @return array<string, array{id: string, label: string, avatar: ?string, avatarShape: string, provenance: ?string}>
     */
    public function searchRecords(string $search): array
    {
        $prepared = $this->prepareEntityQuery();

        if ($prepared === null) {
            return [];
        }

        ['entity' => $entity, 'model' => $model, 'query' => $query, 'keyName' => $keyName, 'titleAttribute' => $titleAttribute, 'avatarConfig' => $avatarConfig] = $prepared;
        $searchAttributes = $entity->getSearchAttributes();

        if ($searchAttributes === []) {
            $searchAttributes = [$titleAttribute];
        }

        $query = app(EntitySearchQuery::class)->apply($query, $search, $searchAttributes, $entity->getResourceClass());

        $records = $this->applyLookupOrder($query, $model)
            ->limit($this->lookupLimit())
            ->get();

        return $this->formatRecordsForJs($records, $keyName, $titleAttribute, $avatarConfig);
    }

    /**
     * Get records by their IDs.
     *
     * @param  array<string>  $ids
     * @return array<string, array{id: string, label: string, avatar: ?string, avatarShape: string, provenance: ?string}>
     */
    public function getRecordsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $prepared = $this->prepareEntityQuery();

        if ($prepared === null) {
            return [];
        }

        ['query' => $query, 'keyName' => $keyName, 'titleAttribute' => $titleAttribute, 'avatarConfig' => $avatarConfig] = $prepared;

        $records = $query->whereIn($keyName, $ids)->get()
            ->sortBy(fn (Model $record): int|false => array_search($record->getKey(), $ids, true));

        return $this->formatRecordsForJs($records, $keyName, $titleAttribute, $avatarConfig, $this->provenance());
    }

    /**
     * Where each selected link came from, for the chip's hover. Candidates in the dropdown are
     * not linked yet, so only the selected records carry it.
     *
     * @return array<string, string>
     */
    private function provenance(): array
    {
        $customField = $this->getCustomField();

        // A component built outside a schema, as the import path does, has no record to read
        // provenance from and asking for one would fail before it could say so.
        if (! $customField instanceof CustomField || ! isset($this->container)) {
            return [];
        }

        $record = $this->getRecord();

        if (! $record instanceof Model) {
            return [];
        }

        $record->loadMissing(['outgoingLinks.createdBy', 'incomingLinks.createdBy']);

        return app(RecordChips::class)->provenance($record, $customField);
    }

    /**
     * The page that creates a record of the target entity, or null when the host registered no
     * resource with one: the picker offers create-new only where it can land somewhere.
     */
    public function getCreateUrl(): ?string
    {
        $entity = $this->getEntityConfiguration();

        return $entity instanceof EntityConfigurationData
            ? app(RecordChips::class)->createUrl($entity)
            : null;
    }

    /**
     * Whether taking a record could take it away from another holder. A relationship whose far
     * end holds many never can, so the picker skips the round trip that asks.
     */
    public function checksHolderConflicts(): bool
    {
        $customField = $this->getCustomField();
        $definition = $customField?->relationshipDefinition();

        if (! $customField instanceof CustomField || ! $definition instanceof CustomFieldRelationship) {
            return false;
        }

        $write = $definition->writeDirectionFor($customField);

        return app(CardinalityGuard::class)->endHoldsOne(
            $definition,
            $write === CustomFieldRelationship::DIRECTION_FROM
                ? CustomFieldRelationship::DIRECTION_TO
                : CustomFieldRelationship::DIRECTION_FROM,
        );
    }

    public function getCreateLabel(): ?string
    {
        $entity = $this->getEntityConfiguration();

        return $entity instanceof EntityConfigurationData
            ? __('custom-fields::custom-fields.record.create_new', ['entity' => $entity->getLabelSingular()])
            : null;
    }

    /**
     * Get initial options (first 50 records).
     *
     * @return array<string, array{id: string, label: string, avatar: ?string, avatarShape: string, provenance: ?string}>
     */
    public function getInitialOptions(): array
    {
        $prepared = $this->prepareEntityQuery();

        if ($prepared === null) {
            return [];
        }

        ['model' => $model, 'query' => $query, 'keyName' => $keyName, 'titleAttribute' => $titleAttribute, 'avatarConfig' => $avatarConfig] = $prepared;

        $records = $this->applyLookupOrder($query, $model)
            ->limit($this->lookupLimit())
            ->get();

        return $this->formatRecordsForJs($records, $keyName, $titleAttribute, $avatarConfig);
    }

    /**
     * Format records for JavaScript consumption.
     *
     * @param  iterable<Model>  $records
     * @param  array<string, string>  $provenance
     * @return array<string, array{id: string, label: string, avatar: ?string, avatarShape: string, provenance: ?string}>
     */
    private function formatRecordsForJs(
        iterable $records,
        string $keyName,
        string $titleAttribute,
        ?AvatarConfiguration $avatarConfig,
        array $provenance = []
    ): array {
        $result = [];

        foreach ($records as $record) {
            $id = (string) $record->getAttribute($keyName);
            $result[$id] = [
                'id' => $id,
                'label' => $record->getAttribute($titleAttribute) ?? '',
                'avatar' => $this->getAvatarUrl($record, $avatarConfig),
                'avatarShape' => $avatarConfig?->getCssClass() ?? 'rounded-full',
                'provenance' => $provenance[$id] ?? null,
            ];
        }

        return $result;
    }

    private function getAvatarUrl(Model $record, ?AvatarConfiguration $avatarConfig): ?string
    {
        if (! $avatarConfig instanceof AvatarConfiguration || ! $avatarConfig->hasAttribute()) {
            return null;
        }

        return $record->getAttribute($avatarConfig->attribute);
    }

    /**
     * What the writer would refuse for one candidate, so the picker can confirm the move
     * inline instead of failing on save. The guard is the single source of that sentence.
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function holderConflictFor(string $recordId): ?string
    {
        $customField = $this->getCustomField();
        $definition = $customField?->relationshipDefinition();

        if (! $customField instanceof CustomField || ! $definition instanceof CustomFieldRelationship) {
            return null;
        }

        $violations = app(CardinalityGuard::class)->violations(
            $definition,
            $definition->writeDirectionFor($customField),
            $this->getRecord()?->getKey(),
            [$recordId],
        );

        return $violations[0] ?? null;
    }

    /**
     * Search records via Livewire call, from Alpine when the user types in the search box.
     *
     * @return array<int, array{id: string, label: string, avatar: ?string, avatarShape: string, provenance: ?string}>
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getSearchResultsForJs(string $search): array
    {
        // Below the minimum, show the unfiltered first page rather than nothing.
        // Returning [] renders as "no results", which reads as broken for a
        // one-character search, and is wrong for single-character CJK names.
        if (mb_strlen($search) < $this->getMinSearchLength()) {
            return array_values($this->getInitialOptions());
        }

        return array_values($this->searchRecords($search));
    }
}
