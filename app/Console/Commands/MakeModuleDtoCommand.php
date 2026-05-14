<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InteractsWithModules;
use Illuminate\Console\Command;

class MakeModuleDtoCommand extends Command
{
    use InteractsWithModules;

    protected $signature = 'module:dto
                            {name : DTO class name}
                            {module : Module name}
                            {--force : Overwrite the DTO if it already exists}';

    protected $description = 'Create a DTO inside an existing module';

    public function handle(): int
    {
        $module = $this->normalizeModuleName((string) $this->argument('module'));
        $dto = $this->normalizeClassName((string) $this->argument('name'));

        if (! $this->ensureValidName($module, 'module') || ! $this->ensureValidName($dto, 'dto')) {
            return self::FAILURE;
        }

        $modulePath = $this->ensureModuleExists($module);

        if ($modulePath === null) {
            return self::FAILURE;
        }

        $destinationPath = $modulePath.DIRECTORY_SEPARATOR.'DTOs'.DIRECTORY_SEPARATOR.$dto.'.php';

        if (! $this->ensureFilesCanBeCreated([$destinationPath], (bool) $this->option('force'))) {
            return self::FAILURE;
        }

        $this->putStub('dto.stub', $destinationPath, [
            '{{ namespace }}' => $this->moduleNamespace($module, 'DTOs'),
            '{{ class }}' => $dto,
        ]);

        $this->info("DTO [{$dto}] created successfully in module [{$module}].");

        return self::SUCCESS;
    }
}
