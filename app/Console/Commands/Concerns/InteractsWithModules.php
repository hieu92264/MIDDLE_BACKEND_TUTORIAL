<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait InteractsWithModules
{
    protected function modulesBasePath(): string
    {
        return rtrim((string) config('modules.base_path', app_path('Http/Modules')), '\\/');
    }

    protected function stubsBasePath(): string
    {
        return rtrim((string) config('modules.stub_path', base_path('stubs/modules')), '\\/');
    }

    protected function normalizeModuleName(string $name): string
    {
        return Str::studly(str_replace(['/', '\\', '.php'], ' ', trim($name)));
    }

    protected function normalizeClassName(string $name, string $suffix = ''): string
    {
        $class = Str::studly(str_replace(['/', '\\', '.php'], ' ', trim($name)));

        if ($class === '') {
            return '';
        }

        if ($suffix !== '' && ! Str::endsWith($class, $suffix)) {
            $class .= $suffix;
        }

        return $class;
    }

    protected function normalizeRepositoryName(string $name): string
    {
        $class = $this->normalizeClassName($name);

        if (Str::endsWith($class, 'Repo')) {
            return Str::replaceEnd('Repo', 'Repository', $class);
        }

        if (! Str::endsWith($class, 'Repository')) {
            $class .= 'Repository';
        }

        return $class;
    }

    protected function modulePath(string $module): string
    {
        return $this->modulesBasePath().DIRECTORY_SEPARATOR.$module;
    }

    protected function findExistingModulePath(string $module): ?string
    {
        $modulePath = $this->modulePath($module);

        if (File::isDirectory($modulePath)) {
            return $modulePath;
        }

        $modulesBasePath = $this->modulesBasePath();

        if (! File::isDirectory($modulesBasePath)) {
            return null;
        }

        foreach (File::directories($modulesBasePath) as $directory) {
            if (mb_strtolower(basename($directory)) === mb_strtolower($module)) {
                return $directory;
            }
        }

        return null;
    }

    protected function ensureValidName(string $name, string $label): bool
    {
        if ($name !== '') {
            return true;
        }

        $this->error(ucfirst($label).' name is invalid.');

        return false;
    }

    protected function ensureModuleExists(string $module): ?string
    {
        $modulePath = $this->findExistingModulePath($module);

        if ($modulePath !== null) {
            return $modulePath;
        }

        $this->error("Module [{$module}] does not exist.");

        return null;
    }

    protected function ensureModuleDoesNotExist(string $module): bool
    {
        if ($this->findExistingModulePath($module) === null) {
            return true;
        }

        $this->error("Module [{$module}] already exists.");

        return false;
    }

    protected function ensureFilesCanBeCreated(array $paths, bool $force = false): bool
    {
        if ($force) {
            return true;
        }

        foreach ($paths as $path) {
            if (File::exists($path)) {
                $this->error("File [{$path}] already exists. Use --force to overwrite.");

                return false;
            }
        }

        return true;
    }

    protected function ensureDirectory(string $path): void
    {
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    protected function putStub(string $stubRelativePath, string $destinationPath, array $replacements = []): void
    {
        $stubPath = $this->stubsBasePath().DIRECTORY_SEPARATOR.$stubRelativePath;

        $contents = File::get($stubPath);
        $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);

        $this->ensureDirectory(dirname($destinationPath));

        File::put($destinationPath, $contents);
    }

    protected function touchGitkeep(string $directory): void
    {
        $gitkeepPath = $directory.DIRECTORY_SEPARATOR.'.gitkeep';

        if (! File::exists($gitkeepPath)) {
            File::put($gitkeepPath, '');
        }
    }

    protected function moduleNamespace(string $module, string $segment): string
    {
        return "App\\Http\\Modules\\{$module}\\{$segment}";
    }
}
