<?php

namespace Relaticle\CustomFields\Commands\Upgrade;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Relaticle\CustomFields\Commands\UpgradeCommand;
use Relaticle\CustomFields\Support\Utils;

class UpdateDatabaseSchema
{
    public function handle(UpgradeCommand $command, Closure $next): mixed
    {
        $isDryRun = $command->isDryRun();

        $command->info('--- Updating database schema...');
        $command->newLine();

        // Logic to update database schema
        $this->createCustomFieldSectionsTable($command, $isDryRun);
        $this->updateCustomFieldsTable($command, $isDryRun);
        $this->removeDeletedAtColumns($command, $isDryRun);

        $command->newLine();
        $command->info('Database schema update step completed.');
        $command->newLine();

        return $next($command);
    }

    private function createCustomFieldSectionsTable(Command $command, bool $isDryRun): void
    {
        $sectionsTable = config('custom-fields.table_names.custom_field_sections', 'custom_field_sections');

        if (! Schema::hasTable($sectionsTable)) {
            if ($isDryRun) {
                $command->line("Table `{$sectionsTable}` would be created.");
            } else {
                Schema::create($sectionsTable, function (Blueprint $table): void {
                    $table->id();
                    if (Utils::isTenantEnabled()) {
                        $table->foreignId(config('custom-fields.column_names.tenant_foreign_key'))->nullable()->index();
                    }
                    $table->string('code');
                    $table->string('name');
                    $table->string('type');
                    $table->string('entity_type');
                    $table->unsignedBigInteger('sort_order')->nullable();
                    $table->string('description')->nullable();
                    $table->boolean('active')->default(true);
                    $table->boolean('system_defined')->default(false);

                    $uniqueColumns = ['entity_type', 'code'];
                    if (Utils::isTenantEnabled()) {
                        $uniqueColumns[] = config('custom-fields.column_names.tenant_foreign_key');
                    }
                    $table->unique($uniqueColumns);

                    $table->timestamps();
                });
                $command->info("Table `{$sectionsTable}` created successfully.");
            }
        } else {
            $command->line("Table `{$sectionsTable}` already exists. Skipping creation.");
        }
    }

    private function updateCustomFieldsTable(Command $command, bool $isDryRun): void
    {
        $customFieldsTable = config('custom-fields.table_names.custom_fields');

        $columnsToAdd = [];
        if (! Schema::hasColumn($customFieldsTable, 'custom_field_section_id')) {
            $columnsToAdd[] = 'custom_field_section_id';
        }
        if (! Schema::hasColumn($customFieldsTable, 'width')) {
            $columnsToAdd[] = 'width';
        }

        if (! empty($columnsToAdd)) {
            if ($isDryRun) {
                foreach ($columnsToAdd as $column) {
                    $command->line("Column `{$column}` would be added to `{$customFieldsTable}` table.");
                }
            } else {
                Schema::table($customFieldsTable, function (Blueprint $table) use ($columnsToAdd): void {
                    if (in_array('custom_field_section_id', $columnsToAdd)) {
                        $table->unsignedBigInteger('custom_field_section_id')->nullable()->after('id');
                    }
                    if (in_array('width', $columnsToAdd)) {
                        $table->string('width')->nullable()->after('custom_field_section_id');
                    }
                });
                foreach ($columnsToAdd as $column) {
                    $command->info("Added `{$column}` column to `{$customFieldsTable}` table.");
                }
            }
        } else {
            $command->line("Columns `custom_field_section_id` and `width` already exist in `{$customFieldsTable}`. Skipping.");
        }
    }

    private function removeDeletedAtColumns(Command $command, bool $isDryRun): void
    {
        $tablesWithDeletedAt = [
            config('custom-fields.table_names.custom_fields'),
            config('custom-fields.table_names.custom_field_options'),
            config('custom-fields.table_names.custom_field_values'),
        ];

        foreach ($tablesWithDeletedAt as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                if ($isDryRun) {
                    $command->line("Column `deleted_at` would be removed from `{$table}` table.");
                } else {
                    Schema::table($table, function (Blueprint $table): void {
                        $table->dropSoftDeletes();
                    });
                    $command->info("Removed `deleted_at` column from `{$table}` table.");
                }
            } else {
                $command->line("Column `deleted_at` does not exist in `{$table}`. Skipping.");
            }
        }
    }
}
