<?php

namespace Relaticle\CustomFields\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FilamentCustomFieldCommand extends Command
{
    protected $signature = 'make:custom-fields-migration {name : The name of the migration} {path? : Path to write migration file to}';

    public $description = 'Create a new custom fields migration file';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $name = trim((string) $this->input->getArgument('name'));
        $path = trim((string) $this->input->getArgument('path'));

        // If path is still empty we get the first path from new custom-fields.migrations_paths config
        if ($path === '' || $path === '0') {
            $path = $this->resolveMigrationPaths()[0];
        }

        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        $this->files->ensureDirectoryExists($path);

        $this->files->put(
            $file = $this->getPath($name, $path),
            $this->getStub()
        );

        $this->info(sprintf('Custom fields migration [%s] created successfully.', $file));
    }

    protected function getStub(): string
    {
        return <<<EOT
<?php

use Relaticle\CustomFields\Integration\Migrations\CustomFieldsMigration;

return new class extends CustomFieldsMigration
{
    public function up(): void
    {

    }
};

EOT;
    }

    protected function ensureMigrationDoesntAlreadyExist(string $name, ?string $migrationPath = null): void
    {
        if ($migrationPath !== null && $migrationPath !== '') {
            $migrationFiles = $this->files->glob($migrationPath.'/*.php');

            foreach ($migrationFiles as $migrationFile) {
                $this->files->requireOnce($migrationFile);
            }
        }

        if (class_exists($className = Str::studly($name))) {
            throw new InvalidArgumentException("A {$className} class already exists.");
        }
    }

    protected function getPath(string $name, string $path): string
    {
        return $path.'/'.Carbon::now()->format('Y_m_d_His').'_'.Str::snake($name).'.php';
    }

    /**
     * @return array<int, string>
     */
    protected function resolveMigrationPaths(): array
    {
        $migrationPath = config('custom-fields.migrations_path');

        return ($migrationPath === null || $migrationPath === '')
            ? config('custom-fields.migrations_paths')
            : [$migrationPath];
    }
}
