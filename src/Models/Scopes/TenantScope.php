<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Relaticle\CustomFields\Services\TenantContextService;
use Relaticle\CustomFields\Support\Utils;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! Utils::isTenantEnabled()) {
            return;
        }

        $tenantId = TenantContextService::getCurrentTenantId();

        if ($tenantId === null) {
            return;
        }

        $builder->where(
            config('custom-fields.column_names.tenant_foreign_key'),
            $tenantId
        );
    }
}
