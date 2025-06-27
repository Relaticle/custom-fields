<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts;

use Relaticle\CustomFields\Data\CustomFieldData;
use Relaticle\CustomFields\Models\CustomField;

interface CustomsFieldsMigrators
{
    public function setTenantId(int|string|null $tenantId = null): void;

    /**
     * @param  class-string  $model
     */
    public function find(string $model, string $code): ?CustomsFieldsMigrators;

    /**
     * @param  class-string  $model
     */
    public function new(string $model, CustomFieldData $fieldData): CustomsFieldsMigrators;

    /**
     * @param  array<int|string, mixed>  $options
     */
    public function options(array $options): CustomsFieldsMigrators;

    /**
     * @param  class-string  $model
     */
    public function lookupType(string $model): CustomsFieldsMigrators;

    public function create(): CustomField;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): void;

    public function delete(): void;

    public function activate(): void;

    public function deactivate(): void;
}
