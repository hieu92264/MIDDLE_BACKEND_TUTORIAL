<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InteractsWithModules;
use Illuminate\Console\Command;

class MakeModuleRepositoryCommand extends Command
{
    use InteractsWithModules;

    protected $signature = 'module:repo
                            {name : Repository name}
                            {module : Module name}
                            {--force : Overwrite the repository and interface if they already exist}';

    protected $description = 'Create a repository and repository interface inside an existing module';

    public function handle(): int
    {
        $module = $this->normalizeModuleName((string) $this->argument('module'));
        $repository = $this->normalizeRepositoryName((string) $this->argument('name'));

        if (! $this->ensureValidName($module, 'module') || ! $this->ensureValidName($repository, 'repository')) {
            return self::FAILURE;
        }

        $modulePath = $this->ensureModuleExists($module);

        if ($modulePath === null) {
            return self::FAILURE;
        }

        $interface = $repository.'Interface';
        $repositoryPath = $modulePath.DIRECTORY_SEPARATOR.'Repositories'.DIRECTORY_SEPARATOR.$repository.'.php';
        $interfacePath = $modulePath.DIRECTORY_SEPARATOR.'Interfaces'.DIRECTORY_SEPARATOR.$interface.'.php';

        if (! $this->ensureFilesCanBeCreated([$repositoryPath, $interfacePath], (bool) $this->option('force'))) {
            return self::FAILURE;
        }

        $this->putStub('repository-interface.stub', $interfacePath, [
            '{{ namespace }}' => $this->moduleNamespace($module, 'Interfaces'),
            '{{ interface }}' => $interface,
        ]);

        $this->putStub('repository.stub', $repositoryPath, [
            '{{ namespace }}' => $this->moduleNamespace($module, 'Repositories'),
            '{{ class }}' => $repository,
            '{{ interface }}' => $interface,
            '{{ interfaceNamespace }}' => $this->moduleNamespace($module, 'Interfaces'),
        ]);

        $this->info("Repository [{$repository}] created successfully in module [{$module}].");

        return self::SUCCESS;
    }
}
