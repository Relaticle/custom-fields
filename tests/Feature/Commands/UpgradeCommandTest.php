<?php

declare(strict_types=1);

use Relaticle\CustomFields\Tests\Fixtures\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('runs validate-schema and clear-caches in dry-run mode without errors', function (): void {
    $this->artisan('custom-fields:upgrade', ['--dry-run' => true])
        ->expectsOutputToContain('DRY RUN COMPLETE')
        ->assertSuccessful();
});

it('runs validate-schema and clear-caches when forced', function (): void {
    $this->artisan('custom-fields:upgrade', ['--force' => true])
        ->expectsOutputToContain('UPGRADE COMPLETE')
        ->assertSuccessful();
});

it('skips clear-caches when requested', function (): void {
    $this->artisan('custom-fields:upgrade', [
        '--force' => true,
        '--skip' => 'clear-caches',
    ])
        ->expectsOutputToContain('Skipping: clear-caches')
        ->assertSuccessful();
});
