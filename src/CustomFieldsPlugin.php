<?php

declare(strict_types=1);

namespace Relaticle\CustomFields;

use Closure;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Relaticle\CustomFields\Filament\Pages\CustomFieldsPage;
use Relaticle\CustomFields\Http\Middleware\SetTenantContextMiddleware;
use Relaticle\CustomFields\Services\TenantContextService;
use Relaticle\CustomFields\Support\Utils;

class CustomFieldsPlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool|Closure $authorizeUsing = true;

    public function getId(): string
    {
        return 'custom-fields';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                CustomFieldsPage::class,
            ])
            ->tenantMiddleware([SetTenantContextMiddleware::class], true);
    }

    public function boot(Panel $panel): void
    {
        if (Utils::isTenantEnabled()) {
            Action::configureUsing(
                fn (Action $action): Action => $action->before(
                    function (Action $action): Action {
                        TenantContextService::setFromFilamentTenant();

                        return $action;
                    }
                )
            );
        }
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function authorize(bool|Closure $callback = true): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function isAuthorized(): bool
    {
        return $this->evaluate($this->authorizeUsing) === true;
    }
}
