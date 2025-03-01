<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Contracts;

use Relaticle\CustomFields\Data\CustomFieldData;

interface CustomsFieldsMigrators
{
    public function setTenantId(int | string | null $tenantId = null): void;

    public function find(string $model, string $code): ?CustomsFieldsMigrators;

    public function new(string $model, CustomFieldData $fieldData): CustomsFieldsMigrators;

    public function options(array $options): CustomsFieldsMigrators;

    public function lookupType(string $model): CustomsFieldsMigrators;

    public function create(): void;

    public function update(array $data): void;

    public function delete(): void;

    public function activate(): void;

    public function deactivate(): void;
}
