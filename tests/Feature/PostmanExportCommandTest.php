<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostmanExportCommandTest extends TestCase
{
    protected string $outputDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputDirectory = storage_path('framework/testing/postman/'.Str::random(12));

        File::deleteDirectory($this->outputDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->outputDirectory);

        parent::tearDown();
    }

    public function test_it_exports_postman_collection_and_environment_files_from_routes(): void
    {
        $this->artisan('postman:export', [
            '--output' => $this->outputDirectory,
            '--base-url' => 'http://127.0.0.1:8000',
        ])
            ->expectsOutput('Postman files generated successfully.')
            ->assertSuccessful();

        $collectionFile = $this->outputDirectory.'/collections/laravel-api.postman_collection.json';
        $environmentFile = $this->outputDirectory.'/environments/local.postman_environment.json';

        $this->assertFileExists($collectionFile);
        $this->assertFileExists($environmentFile);

        $collection = json_decode((string) File::get($collectionFile), true, 512, JSON_THROW_ON_ERROR);
        $environment = json_decode((string) File::get($environmentFile), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Laravel API', $collection['info']['name']);
        $this->assertSame('http://127.0.0.1:8000', collect($environment['values'])->firstWhere('key', 'base_url')['value']);

        $authFolder = collect($collection['item'])->firstWhere('name', 'Auth');

        $this->assertNotNull($authFolder);

        $loginRequest = collect($authFolder['item'])->firstWhere('name', 'Login');
        $registerRequest = collect($authFolder['item'])->firstWhere('name', 'Register');
        $logoutRequest = collect($authFolder['item'])->firstWhere('name', 'Logout');

        $this->assertSame('POST', $loginRequest['request']['method']);
        $this->assertSame('{{base_url}}/api/v1/auth/login', $loginRequest['request']['url']['raw']);
        $this->assertArrayNotHasKey('auth', $loginRequest['request']);

        $registerBody = json_decode($registerRequest['request']['body']['raw'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('demo_user', $registerBody['username']);
        $this->assertSame('user@example.com', $registerBody['email']);
        $this->assertSame('secret123', $registerBody['password']);
        $this->assertSame('secret123', $registerBody['password_confirmation']);

        $this->assertSame('bearer', $logoutRequest['request']['auth']['type']);
        $this->assertSame('{{jwt_token}}', $logoutRequest['request']['auth']['bearer'][0]['value']);
    }
}
