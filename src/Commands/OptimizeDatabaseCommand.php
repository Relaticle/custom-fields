<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Relaticle\CustomFields\CustomFields;

class OptimizeDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'custom-fields:optimize-database 
                            {--analyze : Only analyze table structure without making changes}
                            {--force : Force the operation to run without confirmations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize custom fields database columns for better performance and constraint compliance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Analyzing custom fields database structure...');
        
        $analyzeOnly = $this->option('analyze');
        $force = $this->option('force');

        // Get the current database driver
        $driver = DB::connection()->getDriverName();
        $this->info("Database driver: {$driver}");

        // Get table names from configuration
        $valuesTable = config('custom-fields.table_names.custom_field_values');
        
        // Check if the table exists
        if (!Schema::hasTable($valuesTable)) {
            $this->error("Custom fields values table {$valuesTable} doesn't exist!");
            return 1;
        }
        
        $this->info("Analyzing table structure for {$valuesTable}...");
        
        // Get column information based on database driver
        $columns = $this->getColumnInformation($valuesTable, $driver);
        
        // Show current column types
        $this->table(
            ['Column', 'Current Type', 'Recommended Type', 'Status'],
            $columns
        );
        
        if ($analyzeOnly) {
            $this->info('Analysis complete. Use without --analyze option to perform the optimization.');
            return 0;
        }
        
        if (!$force && !$this->confirm('Do you want to proceed with database optimization?')) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        // Perform the optimization
        $this->info('Optimizing database columns...');
        
        try {
            // Begin a transaction
            DB::beginTransaction();
            
            // Update columns
            $this->updateColumns($valuesTable, $columns, $driver);
            
            // Commit the transaction
            DB::commit();
            
            $this->info('Database optimization completed successfully!');
            $this->info('You may need to restart your application for the changes to take effect.');
            return 0;
        } catch (Exception $e) {
            DB::rollBack();
            $this->error('An error occurred during database optimization:');
            $this->error($e->getMessage());
            return 1;
        }
    }
    
    /**
     * Get column information for the target table.
     *
     * @param string $table The table name
     * @param string $driver The database driver
     * @return array Column information
     */
    private function getColumnInformation(string $table, string $driver): array
    {
        $columnInfo = [];
        
        // Get column information based on database driver
        switch ($driver) {
            case 'mysql':
                $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
                foreach ($columns as $column) {
                    // Only check value columns
                    if ($this->isValueColumn($column->Field)) {
                        $recommendedType = $this->getRecommendedType($column->Field, $driver);
                        $columnInfo[] = [
                            'column' => $column->Field,
                            'current_type' => $column->Type,
                            'recommended_type' => $recommendedType,
                            'status' => $column->Type === $recommendedType ? 'Optimal' : 'Needs Optimization',
                        ];
                    }
                }
                break;
                
            case 'pgsql':
                $columns = DB::select("
                    SELECT column_name, data_type, character_maximum_length, numeric_precision, numeric_scale
                    FROM information_schema.columns
                    WHERE table_name = '{$table}'
                ");
                
                foreach ($columns as $column) {
                    // Only check value columns
                    if ($this->isValueColumn($column->column_name)) {
                        $currentType = $column->data_type;
                        if ($column->character_maximum_length) {
                            $currentType .= "({$column->character_maximum_length})";
                        } elseif ($column->numeric_precision && $column->numeric_scale) {
                            $currentType .= "({$column->numeric_precision},{$column->numeric_scale})";
                        }
                        
                        $recommendedType = $this->getRecommendedType($column->column_name, $driver);
                        $columnInfo[] = [
                            'column' => $column->column_name,
                            'current_type' => $currentType,
                            'recommended_type' => $recommendedType,
                            'status' => $currentType === $recommendedType ? 'Optimal' : 'Needs Optimization',
                        ];
                    }
                }
                break;
                
            case 'sqlite':
                $columns = DB::select("PRAGMA table_info({$table})");
                foreach ($columns as $column) {
                    // Only check value columns
                    if ($this->isValueColumn($column->name)) {
                        $recommendedType = $this->getRecommendedType($column->name, $driver);
                        $columnInfo[] = [
                            'column' => $column->name,
                            'current_type' => $column->type,
                            'recommended_type' => $recommendedType,
                            'status' => strtolower($column->type) === strtolower($recommendedType) ? 'Optimal' : 'Needs Optimization',
                        ];
                    }
                }
                break;
        }
        
        return $columnInfo;
    }
    
    /**
     * Update columns to their recommended types.
     *
     * @param string $table The table name
     * @param array $columns Column information
     * @param string $driver Database driver
     */
    private function updateColumns(string $table, array $columns, string $driver): void
    {
        // Skip optimization if all columns are already optimal
        $needsOptimization = false;
        foreach ($columns as $column) {
            if ($column['status'] === 'Needs Optimization') {
                $needsOptimization = true;
                break;
            }
        }
        
        if (!$needsOptimization) {
            $this->info('All columns are already optimized!');
            return;
        }
        
        // Perform the optimization
        Schema::table($table, function (Blueprint $table) use ($columns, $driver) {
            foreach ($columns as $column) {
                if ($column['status'] === 'Needs Optimization') {
                    $this->info("Optimizing column {$column['column']} from {$column['current_type']} to {$column['recommended_type']}...");
                    
                    $this->modifyColumn($table, $column['column'], $driver);
                }
            }
        });
    }
    
    /**
     * Modify a column to its recommended type.
     *
     * @param Blueprint $table The table blueprint
     * @param string $columnName The column name
     * @param string $driver Database driver
     */
    private function modifyColumn(Blueprint $table, string $columnName, string $driver): void
    {
        switch ($columnName) {
            case 'string_value':
                $table->string($columnName, 255)->nullable()->change();
                break;
                
            case 'text_value':
                if ($driver === 'mysql') {
                    // MySQL
                    $table->longText($columnName)->nullable()->change();
                } elseif ($driver === 'pgsql') {
                    // PostgreSQL
                    $table->text($columnName)->nullable()->change();
                } else {
                    // SQLite
                    $table->text($columnName)->nullable()->change();
                }
                break;
                
            case 'integer_value':
                $table->bigInteger($columnName)->nullable()->change();
                break;
                
            case 'float_value':
                if ($driver === 'mysql' || $driver === 'pgsql') {
                    // MySQL & PostgreSQL
                    DB::statement("ALTER TABLE {$table->getTable()} ALTER COLUMN {$columnName} TYPE DECIMAL(30,15)");
                } else {
                    // SQLite doesn't support precision modification
                    $table->float($columnName, 30, 15)->nullable()->change();
                }
                break;
                
            case 'json_value':
                $table->json($columnName)->nullable()->change();
                break;
        }
    }
    
    /**
     * Check if a column is a value column.
     *
     * @param string $columnName The column name
     * @return bool True if it's a value column
     */
    private function isValueColumn(string $columnName): bool
    {
        return in_array($columnName, [
            'string_value',
            'text_value',
            'integer_value',
            'float_value',
            'boolean_value',
            'date_value',
            'datetime_value',
            'json_value',
        ]);
    }
    
    /**
     * Get the recommended column type based on database driver.
     *
     * @param string $columnName The column name
     * @param string $driver Database driver
     * @return string The recommended type
     */
    private function getRecommendedType(string $columnName, string $driver): string
    {
        switch ($driver) {
            case 'mysql':
                $types = [
                    'string_value' => 'varchar(255)',
                    'text_value' => 'longtext',
                    'integer_value' => 'bigint',
                    'float_value' => 'decimal(30,15)',
                    'boolean_value' => 'tinyint(1)',
                    'date_value' => 'date',
                    'datetime_value' => 'datetime',
                    'json_value' => 'json',
                ];
                break;
                
            case 'pgsql':
                $types = [
                    'string_value' => 'character varying(255)',
                    'text_value' => 'text',
                    'integer_value' => 'bigint',
                    'float_value' => 'numeric(30,15)',
                    'boolean_value' => 'boolean',
                    'date_value' => 'date',
                    'datetime_value' => 'timestamp without time zone',
                    'json_value' => 'jsonb',
                ];
                break;
                
            case 'sqlite':
                $types = [
                    'string_value' => 'varchar',
                    'text_value' => 'text',
                    'integer_value' => 'integer',
                    'float_value' => 'real',
                    'boolean_value' => 'boolean',
                    'date_value' => 'date',
                    'datetime_value' => 'datetime',
                    'json_value' => 'text', // SQLite stores JSON as text
                ];
                break;
                
            default:
                $types = [
                    'string_value' => 'varchar(255)',
                    'text_value' => 'text',
                    'integer_value' => 'bigint',
                    'float_value' => 'decimal(30,15)',
                    'boolean_value' => 'boolean',
                    'date_value' => 'date',
                    'datetime_value' => 'datetime',
                    'json_value' => 'json',
                ];
        }
        
        return $types[$columnName] ?? 'unknown';
    }
}
