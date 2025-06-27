<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Services;

use Throwable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;

/**
 * Service for discovering custom field types automatically.
 */
final class FieldTypeDiscoveryService
{
    /**
     * Discover field types from a given directory.
     *
     * @param  array<string>  $directories
     * @return Collection<int, FieldTypeDefinitionInterface>
     */
    public function discoverFromDirectories(array $directories): Collection
    {
        $fieldTypes = collect();

        foreach ($directories as $directory) {
            if (! File::isDirectory($directory)) {
                continue;
            }

            $files = File::allFiles($directory);

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $fieldType = $this->loadFieldTypeFromFile($file->getPathname());

                if ($fieldType instanceof FieldTypeDefinitionInterface) {
                    $fieldTypes->push($fieldType);
                }
            }
        }

        return $fieldTypes;
    }

    /**
     * Discover field types from composer packages using PSR-4 autoloading.
     *
     * @param  array<string>  $namespaces
     * @return Collection<int, FieldTypeDefinitionInterface>
     */
    public function discoverFromNamespaces(array $namespaces): Collection
    {
        $fieldTypes = collect();

        foreach ($namespaces as $namespace) {
            $directory = $this->getDirectoryFromNamespace($namespace);

            if ($directory && File::isDirectory($directory)) {
                $discovered = $this->discoverFromDirectories([$directory]);
                $fieldTypes = $fieldTypes->merge($discovered);
            }
        }

        return $fieldTypes;
    }

    /**
     * Discover field types using configured discovery rules.
     *
     * @return Collection<int, FieldTypeDefinitionInterface>
     */
    public function discoverFromConfig(): Collection
    {
        $config = config('custom-fields.field_type_discovery', []);
        $fieldTypes = collect();

        // Discover from directories
        $directories = $config['directories'] ?? [];
        if (! empty($directories)) {
            $discovered = $this->discoverFromDirectories($directories);
            $fieldTypes = $fieldTypes->merge($discovered);
        }

        // Discover from namespaces
        $namespaces = $config['namespaces'] ?? [];
        if (! empty($namespaces)) {
            $discovered = $this->discoverFromNamespaces($namespaces);
            $fieldTypes = $fieldTypes->merge($discovered);
        }

        // Load explicitly registered classes
        $classes = $config['classes'] ?? [];
        foreach ($classes as $className) {
            $fieldType = $this->loadFieldTypeFromClass($className);
            if ($fieldType instanceof FieldTypeDefinitionInterface) {
                $fieldTypes->push($fieldType);
            }
        }

        return $fieldTypes;
    }

    /**
     * Load a field type definition from a PHP file.
     */
    private function loadFieldTypeFromFile(string $filePath): ?FieldTypeDefinitionInterface
    {
        try {
            require_once $filePath;

            $classes = get_declared_classes();
            $lastClass = end($classes);

            if ($lastClass && class_exists($lastClass)) {
                return $this->loadFieldTypeFromClass($lastClass);
            }
        } catch (Throwable) {
            // Silently skip files that can't be loaded
        }

        return null;
    }

    /**
     * Load a field type definition from a class name.
     */
    private function loadFieldTypeFromClass(string $className): ?FieldTypeDefinitionInterface
    {
        try {
            if (! class_exists($className)) {
                return null;
            }

            $reflection = new ReflectionClass($className);

            if (! $reflection->implementsInterface(FieldTypeDefinitionInterface::class)) {
                return null;
            }

            if ($reflection->isAbstract() || $reflection->isInterface()) {
                return null;
            }

            return $reflection->newInstance();
        } catch (Throwable) {
            // Silently skip classes that can't be instantiated
        }

        return null;
    }

    /**
     * Get directory path from namespace using composer autoloader.
     */
    private function getDirectoryFromNamespace(string $namespace): ?string
    {
        $composerPath = base_path('vendor/composer/autoload_psr4.php');

        if (! File::exists($composerPath)) {
            return null;
        }

        $psr4Map = require $composerPath;

        // Normalize namespace
        $namespace = trim($namespace, '\\').'\\';

        if (isset($psr4Map[$namespace])) {
            $paths = is_array($psr4Map[$namespace]) ? $psr4Map[$namespace] : [$psr4Map[$namespace]];

            return $paths[0] ?? null;
        }

        // Try to find partial matches
        foreach ($psr4Map as $prefix => $paths) {
            if (str_starts_with($namespace, (string) $prefix)) {
                $relativePath = str_replace($prefix, '', $namespace);
                $basePath = is_array($paths) ? $paths[0] : $paths;

                return $basePath.str_replace('\\', '/', $relativePath);
            }
        }

        return null;
    }
}
