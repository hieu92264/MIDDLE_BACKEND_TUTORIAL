<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModuleCommandsTest extends TestCase
{
    protected string $modulesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesPath = storage_path('framework/testing/modules/'.Str::random(12));

        File::deleteDirectory($this->modulesPath);
        File::makeDirectory($this->modulesPath, 0755, true);

        config(['modules.base_path' => $this->modulesPath]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->modulesPath);

        parent::tearDown();
    }

    public function test_make_module_creates_expected_structure(): void
    {
        $this->artisan('make:module', ['name' => 'blog'])
            ->expectsOutput('Module [Blog] created successfully.')
            ->assertSuccessful();

        $modulePath = $this->modulesPath.DIRECTORY_SEPARATOR.'Blog';

        foreach ([
            'Controllers',
            'DTOs',
            'Interfaces',
            'Middlewares',
            'Models',
            'Repositories',
            'Requests',
            'Routes',
            'Services',
        ] as $directory) {
            $this->assertDirectoryExists($modulePath.DIRECTORY_SEPARATOR.$directory);
        }

        $this->assertFileExists($modulePath.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'api.php');
        $this->assertFileExists($modulePath.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'.gitkeep');
        $this->assertStringContainsString(
            "Route::prefix('blog')",
            File::get($modulePath.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'api.php')
        );
    }

    public function test_make_module_fails_when_module_name_already_exists(): void
    {
        $this->artisan('make:module', ['name' => 'Blog'])->assertSuccessful();

        $this->artisan('make:module', ['name' => 'blog'])
            ->expectsOutput('Module [Blog] already exists.')
            ->assertExitCode(1);
    }

    public function test_module_commands_generate_files_inside_existing_module(): void
    {
        $this->artisan('make:module', ['name' => 'Catalog'])->assertSuccessful();

        $this->artisan('module:controller', [
            'name' => 'Product',
            'module' => 'Catalog',
        ])->assertSuccessful();

        $this->artisan('module:service', [
            'name' => 'Product',
            'module' => 'Catalog',
        ])->assertSuccessful();

        $this->artisan('module:repo', [
            'name' => 'Product',
            'module' => 'Catalog',
        ])->assertSuccessful();

        $this->artisan('module:dto', [
            'name' => 'ProductData',
            'module' => 'Catalog',
        ])->assertSuccessful();

        $modulePath = $this->modulesPath.DIRECTORY_SEPARATOR.'Catalog';

        $this->assertFileExists($modulePath.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'ProductController.php');
        $this->assertFileExists($modulePath.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'ProductService.php');
        $this->assertFileExists($modulePath.DIRECTORY_SEPARATOR.'Repositories'.DIRECTORY_SEPARATOR.'ProductRepository.php');
        $this->assertFileExists($modulePath.DIRECTORY_SEPARATOR.'Interfaces'.DIRECTORY_SEPARATOR.'ProductServiceInterface.php');
        $this->assertFileExists($modulePath.DIRECTORY_SEPARATOR.'Interfaces'.DIRECTORY_SEPARATOR.'ProductRepositoryInterface.php');
        $this->assertFileExists($modulePath.DIRECTORY_SEPARATOR.'DTOs'.DIRECTORY_SEPARATOR.'ProductData.php');

        $this->assertStringContainsString(
            'namespace App\Http\Modules\Catalog\Controllers;',
            File::get($modulePath.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'ProductController.php')
        );
        $this->assertStringContainsString(
            'class ProductService implements ProductServiceInterface',
            File::get($modulePath.DIRECTORY_SEPARATOR.'Services'.DIRECTORY_SEPARATOR.'ProductService.php')
        );
        $this->assertStringContainsString(
            'class ProductRepository implements ProductRepositoryInterface',
            File::get($modulePath.DIRECTORY_SEPARATOR.'Repositories'.DIRECTORY_SEPARATOR.'ProductRepository.php')
        );
        $this->assertStringContainsString(
            'readonly class ProductData',
            File::get($modulePath.DIRECTORY_SEPARATOR.'DTOs'.DIRECTORY_SEPARATOR.'ProductData.php')
        );
    }

    public function test_module_generator_fails_when_target_module_does_not_exist(): void
    {
        $this->artisan('module:service', [
            'name' => 'Product',
            'module' => 'Missing',
        ])
            ->expectsOutput('Module [Missing] does not exist.')
            ->assertExitCode(1);
    }
}
