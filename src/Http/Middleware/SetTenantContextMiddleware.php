<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Relaticle\CustomFields\Services\TenantContextService;
use Relaticle\CustomFields\Support\Utils;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContextMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Utils::isTenantEnabled()) {
            TenantContextService::setFromFilamentTenant();
        }

        return $next($request);
    }
}
