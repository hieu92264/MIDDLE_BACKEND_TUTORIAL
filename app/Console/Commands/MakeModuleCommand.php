<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InteractsWithModules;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    use InteractsWithModules;

    protected $signature = 'make:module {name : Module name}';

    protected $description = 'Create a new module directory structure';

    public function handle(): int
    {
        $module = $this->normalizeModuleName((string) $this->argument('name'));

        if (! $this->ensureValidName($module, 'module') || ! $this->ensureModuleDoesNotExist($module)) {
            return self::FAILURE;
        }

        $modulePath = $this->modulePath($module);
        $directories = [
            'Controllers',
            'DTOs',
            'Interfaces',
            'Middlewares',
            'Models',
            'Repositories',
            'Requests',
            'Routes',
            'Services',
        ];

        $this->ensureDirectory($modulePath);

        foreach ($directories as $directory) {
            $fullPath = $modulePath.DIRECTORY_SEPARATOR.$directory;

            $this->ensureDirectory($fullPath);

            if ($directory !== 'Routes') {
                $this->touchGitkeep($fullPath);
            }
        }

        $this->putStub(
            'routes/api.stub',
            $modulePath.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'api.php',
            ['{{ moduleKebab }}' => Str::kebab($module)]
        );

        $this->info("Module [{$module}] created successfully.");

        return self::SUCCESS;
    }
}
