<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Filament\Integration\Components\Tables\Filters;

use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter as FilamentSelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Relaticle\CustomFields\Data\AvatarConfiguration;
use Relaticle\CustomFields\Facades\Entities;
use Relaticle\CustomFields\Filament\Integration\Base\AbstractTableFilter;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldRelationship;
use Relaticle\CustomFields\QueryBuilders\EntitySearchQuery;
use Relaticle\CustomFields\QueryBuilders\RecordLinkQuery;
use Throwable;

final class RecordFilter extends AbstractTableFilter
{
    /**
     * @throws Throwable
     */
    public function make(CustomField $customField, ?Model $record = null, ?string $through = null): FilamentSelectFilter
    {
        $filter = FilamentSelectFilter::make($customField->getFieldName())
            ->multiple()
            ->label($customField->name)
            ->searchable()
            ->native(false)
            ->modifyFormFieldUsing(fn (Select $field): Select => $field->allowHtml());

        $filter = $this->configureLookup($filter, $customField->targetEntityType());

        $filter->query(function (array $data, Builder $query) use ($customField): Builder {
            if (empty($data['values'])) {
                return $query;
            }

            $definition = $customField->relationshipDefinition();

            if (! $definition instanceof CustomFieldRelationship) {
                return $this->whereStoredValue($query, $customField, $data['values']);
            }

            return app(RecordLinkQuery::class)->whereLinkedTo(
                $query,
                $definition,
                $definition->readDirectionFor($customField),
                $data['values'],
            );
        });

        return $filter;
    }

    /**
     * A record field the upgrade step has not migrated yet still keeps its ids in
     * json_value, and 3.x stored them there for both cardinalities.
     *
     * @param  Builder<Model>  $query
     * @param  array<int, mixed>  $values
     * @return Builder<Model>
     */
    private function whereStoredValue(Builder $query, CustomField $customField, array $values): Builder
    {
        return $query->whereHas('customFieldValues', function (Builder $related) use ($customField, $values): void {
            $related->where('custom_field_id', $customField->getKey());

            $related->where(function (Builder $anyValue) use ($values): void {
                foreach ($values as $value) {
                    $anyValue->orWhereJsonContains('json_value', $value);
                }
            });
        });
    }

    /**
     * @throws Throwable
     */
    private function configureLookup(FilamentSelectFilter $filter, ?string $lookupType): FilamentSelectFilter
    {
        if ($lookupType === null) {
            return $filter;
        }

        $entity = Entities::getEntity($lookupType);

        if ($entity === null) {
            throw new InvalidArgumentException('No entity found for lookup type: '.$lookupType);
        }

        $entityInstance = $entity->createModelInstance();
        $recordTitleAttribute = $entity->getPrimaryAttribute();
        $searchAttributes = $entity->getSearchAttributes();
        $avatarConfig = $entity->getAvatarConfiguration();

        if ($searchAttributes === []) {
            $searchAttributes = [$recordTitleAttribute];
        }

        return $filter
            ->getSearchResultsUsing(function (string $search) use ($entityInstance, $recordTitleAttribute, $searchAttributes, $avatarConfig): array {
                $query = app(EntitySearchQuery::class)->apply($entityInstance->query(), $search, $searchAttributes);

                $records = $query->limit(50)->get();

                return $this->formatOptionsWithAvatars($records, $recordTitleAttribute, $avatarConfig);
            })
            ->getOptionLabelUsing(function (mixed $value) use ($entityInstance, $recordTitleAttribute, $avatarConfig): ?string {
                $record = $entityInstance::query()->find($value);
                if ($record === null) {
                    return null;
                }

                return $this->formatOptionWithAvatar($record, $recordTitleAttribute, $avatarConfig);
            })
            ->getOptionLabelsUsing(function (array $values) use ($entityInstance, $recordTitleAttribute, $avatarConfig): array {
                $records = $entityInstance::query()
                    ->whereIn('id', $values)
                    ->get();

                return $this->formatOptionsWithAvatars($records, $recordTitleAttribute, $avatarConfig);
            });
    }

    /**
     * @param  iterable<Model>  $records
     * @return array<string, string>
     */
    private function formatOptionsWithAvatars(
        iterable $records,
        string $titleAttribute,
        ?AvatarConfiguration $avatarConfig,
    ): array {
        $options = [];

        foreach ($records as $record) {
            $key = $record->getKey();
            $options[$key] = $this->formatOptionWithAvatar($record, $titleAttribute, $avatarConfig);
        }

        return $options;
    }

    private function formatOptionWithAvatar(
        Model $record,
        string $titleAttribute,
        ?AvatarConfiguration $avatarConfig,
    ): string {
        $name = $record->getAttribute($titleAttribute) ?? '';
        $avatarUrl = $this->getAvatarUrl($record, $avatarConfig);
        $shapeClass = $avatarConfig?->getCssClass() ?? 'rounded-full';

        if ($avatarUrl !== null) {
            return sprintf(
                '<div class="flex items-center gap-2"><img src="%s" alt="" class="h-5 w-5 %s object-cover" /><span>%s</span></div>',
                e($avatarUrl),
                $shapeClass,
                e($name)
            );
        }

        return e($name);
    }

    private function getAvatarUrl(Model $record, ?AvatarConfiguration $avatarConfig): ?string
    {
        if (! $avatarConfig instanceof AvatarConfiguration || ! $avatarConfig->hasAttribute()) {
            return null;
        }

        return $record->getAttribute($avatarConfig->attribute);
    }
}
