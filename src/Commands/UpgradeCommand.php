<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Commands;

use Illuminate\Console\Command;
use Illuminate\Pipeline\Pipeline;
use Relaticle\CustomFields\Commands\Upgrade\UpdateDatabaseSchema;
use Relaticle\CustomFields\Commands\Upgrade\UpdateExistingData;
use Throwable;

class UpgradeCommand extends Command
{
    protected $signature = 'custom-fields:upgrade {--dry-run : Simulate the upgrade without making any changes}';

    protected $description = 'Upgrade the Custom Fields Filament Plugin to version 1.0';

    public function handle(): int
    {
        $this->info('Welcome to the Custom Fields Upgrade Command!');
        $this->info('This command will upgrade the Custom Fields Filament Plugin to version 1.0.');
        $this->newLine();

        if ($this->isDryRun()) {
            $this->warn('Running in Dry Run mode. No changes will be made.');
        }

        if (! $this->confirm('Do you wish to continue?', true)) {
            $this->info('Upgrade cancelled by the user.');

            return self::SUCCESS;
        }

        $this->newLine();

        try {
            app(Pipeline::class)
                ->send($this)
                ->through([
                    UpdateDatabaseSchema::class,
                    UpdateExistingData::class,
                ])
                ->thenReturn();

            $this->info('Upgrade completed successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('An error occurred during the upgrade process:');
            $this->error($e->getMessage());

            \Log::error('Custom Fields Upgrade Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    public function isDryRun(): bool
    {
        return $this->option('dry-run');
    }
}
