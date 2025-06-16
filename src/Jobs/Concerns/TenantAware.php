<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Jobs\Concerns;

use Relaticle\CustomFields\Services\TenantContextService;
use Relaticle\CustomFields\Support\Utils;

trait TenantAware
{
    /**
     * The tenant ID for this job.
     */
    public null|int|string $tenantId = null;

    /**
     * Set the tenant context when dispatching the job.
     */
    public function withTenant(null|int|string $tenantId = null): static
    {
        $this->tenantId = $tenantId ?? TenantContextService::getCurrentTenantId();

        return $this;
    }

    /**
     * Handle the job with tenant context.
     */
    public function handleWithTenantContext(): void
    {
        if (Utils::isTenantEnabled() && $this->tenantId !== null) {
            TenantContextService::withTenant($this->tenantId, function (): void {
                $this->handle();
            });
        } else {
            $this->handle();
        }
    }

    /**
     * Automatically set tenant context when job is being dispatched.
     */
    public function __construct()
    {
        if (Utils::isTenantEnabled()) {
            $this->tenantId = TenantContextService::getCurrentTenantId();
        }
    }
}
