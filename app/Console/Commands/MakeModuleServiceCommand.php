<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InteractsWithModules;
use Illuminate\Console\Command;

class MakeModuleServiceCommand extends Command
{
    use InteractsWithModules;

    protected $signature = 'module:service
                            {name : Service name}
                            {module : Module name}
                            {--force : Overwrite the service and interface if they already exist}';

    protected $description = 'Create a service and service interface inside an existing module';

    public function handle(): int
    {
        $module = $this->normalizeModuleName((string) $this->argument('module'));
        $service = $this->normalizeClassName((string) $this->argument('name'), 'Service');

        if (! $this->ensureValidName($module, 'module') || ! $this->ensureValidName($service, 'service')) {
            return self::FAILURE;
        }

        $modulePath = $this->ensureModuleExists($module);

        if ($modulePath === null) {
            return self::FAILURE;
        }

        $interface = $service.'Interface';
        $servicePath = $modulePath.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.$service.'.php';
        $interfacePath = $modulePath.DIRECTORY_SEPARATOR.'Interfaces'.DIRECTORY_SEPARATOR.$interface.'.php';

        if (! $this->ensureFilesCanBeCreated([$servicePath, $interfacePath], (bool) $this->option('force'))) {
            return self::FAILURE;
        }

        $this->putStub('service-interface.stub', $interfacePath, [
            '{{ namespace }}' => $this->moduleNamespace($module, 'Interfaces'),
            '{{ interface }}' => $interface,
        ]);

        $this->putStub('service.stub', $servicePath, [
            '{{ namespace }}' => $this->moduleNamespace($module, 'Services'),
            '{{ class }}' => $service,
            '{{ interface }}' => $interface,
            '{{ interfaceNamespace }}' => $this->moduleNamespace($module, 'Interfaces'),
        ]);

        $this->info("Service [{$service}] created successfully in module [{$module}].");

        return self::SUCCESS;
    }
}
