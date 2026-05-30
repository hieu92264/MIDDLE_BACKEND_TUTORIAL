<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use UnitEnum;

class ExportPostmanCollectionCommand extends Command
{
    protected $signature = 'postman:export
        {--collection= : Collection display name}
        {--base-url=http://127.0.0.1:8000 : Base URL used in the generated environment}
        {--output=postman : Output directory}';

    protected $description = 'Export Laravel API routes to Postman collection and environment files';

    public function handle(): int
    {
        $outputDirectory = $this->resolveOutputDirectory();
        $collectionName = $this->resolveCollectionName();
        $apiPrefix = trim((string) config('app.api_prefix', 'api'), '/');
        $gitBranch = $this->detectGitBranch();
        $exportedAt = now()->toISOString();

        $routes = collect(Route::getRoutes())
            ->filter(fn (IlluminateRoute $route): bool => $this->shouldExportRoute($route, $apiPrefix))
            ->flatMap(fn (IlluminateRoute $route): array => $this->transformRouteToItems($route))
            ->sortBy([
                ['folder', 'asc'],
                ['request.name', 'asc'],
                ['request.method', 'asc'],
            ])
            ->values();

        if ($routes->isEmpty()) {
            $this->warn('No API routes matched the configured API prefix.');

            return self::FAILURE;
        }

        $collectionItems = $routes
            ->groupBy('folder')
            ->map(fn ($items, $folder): array => [
                'name' => $folder,
                'item' => $items->pluck('request')->values()->all(),
            ])
            ->values()
            ->all();

        File::ensureDirectoryExists($outputDirectory.DIRECTORY_SEPARATOR.'collections');
        File::ensureDirectoryExists($outputDirectory.DIRECTORY_SEPARATOR.'environments');

        $collectionFile = $outputDirectory.DIRECTORY_SEPARATOR.'collections'.DIRECTORY_SEPARATOR.$this->slugifyFileName($collectionName).'.postman_collection.json';
        $environmentFile = $outputDirectory.DIRECTORY_SEPARATOR.'environments'.DIRECTORY_SEPARATOR.'local.postman_environment.json';

        $collectionPayload = [
            'info' => [
                '_postman_id' => $this->makeUuid($collectionName.'|collection'),
                'name' => $collectionName,
                'description' => implode("\n", array_filter([
                    'Generated from Laravel routes.',
                    $gitBranch ? 'Branch: '.$gitBranch : null,
                    'Exported at: '.$exportedAt,
                ])),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => (string) $this->option('base-url'),
                ],
                [
                    'key' => 'api_prefix',
                    'value' => $apiPrefix,
                ],
                [
                    'key' => 'git_branch',
                    'value' => $gitBranch,
                ],
            ],
            'item' => $collectionItems,
        ];

        $environmentPayload = [
            'id' => $this->makeUuid($collectionName.'|environment'),
            'name' => trim($collectionName.' Local'.($gitBranch ? ' ('.$gitBranch.')' : '')),
            'values' => [
                [
                    'key' => 'base_url',
                    'value' => (string) $this->option('base-url'),
                    'type' => 'default',
                    'enabled' => true,
                ],
                [
                    'key' => 'api_prefix',
                    'value' => $apiPrefix,
                    'type' => 'default',
                    'enabled' => true,
                ],
                [
                    'key' => 'jwt_token',
                    'value' => '',
                    'type' => 'secret',
                    'enabled' => true,
                ],
                [
                    'key' => 'git_branch',
                    'value' => $gitBranch,
                    'type' => 'default',
                    'enabled' => true,
                ],
            ],
            '_postman_variable_scope' => 'environment',
            '_postman_exported_at' => $exportedAt,
            '_postman_exported_using' => 'php artisan postman:export',
        ];

        File::put($collectionFile, $this->encodeJson($collectionPayload));
        File::put($environmentFile, $this->encodeJson($environmentPayload));

        $this->info('Postman files generated successfully.');
        $this->line('Collection: '.$this->relativePath($collectionFile));
        $this->line('Environment: '.$this->relativePath($environmentFile));

        return self::SUCCESS;
    }

    protected function shouldExportRoute(IlluminateRoute $route, string $apiPrefix): bool
    {
        $uri = trim($route->uri(), '/');

        if ($uri === '') {
            return false;
        }

        $normalizedPrefix = trim($apiPrefix, '/');

        return $uri === $normalizedPrefix || Str::startsWith($uri, $normalizedPrefix.'/');
    }

    /**
     * @return array<int, array{folder: string, request: array<string, mixed>}>
     */
    protected function transformRouteToItems(IlluminateRoute $route): array
    {
        $items = [];

        foreach ($this->normalizeMethods($route->methods()) as $method) {
            $folder = $this->resolveFolderName($route);
            $requiresAuth = $this->routeRequiresAuth($route);
            $body = $this->buildRequestBody($route);
            $events = $this->buildRequestEvents($route);
            $rawUrl = '{{base_url}}/'.$this->normalizeUriForPostman($route->uri());
            $headers = [
                [
                    'key' => 'Accept',
                    'value' => 'application/json',
                    'type' => 'text',
                ],
            ];

            if ($body !== null) {
                $headers[] = [
                    'key' => 'Content-Type',
                    'value' => 'application/json',
                    'type' => 'text',
                ];
            }

            $request = [
                'name' => $this->resolveRequestName($route, $method),
                'event' => $events,
                'request' => array_filter([
                    'method' => $method,
                    'header' => $headers,
                    'auth' => $requiresAuth ? [
                        'type' => 'bearer',
                        'bearer' => [
                            [
                                'key' => 'token',
                                'value' => '{{jwt_token}}',
                                'type' => 'string',
                            ],
                        ],
                    ] : null,
                    'body' => $body,
                    'url' => [
                        'raw' => $rawUrl,
                        'host' => ['{{base_url}}'],
                        'path' => explode('/', trim($this->normalizeUriForPostman($route->uri()), '/')),
                    ],
                    'description' => $this->buildRequestDescription($route),
                ], fn ($value): bool => $value !== null),
            ];

            $items[] = [
                'folder' => $folder,
                'request' => array_filter($request, fn ($value): bool => $value !== null),
            ];
        }

        return $items;
    }

    /**
     * @param  array<int, string>  $methods
     * @return array<int, string>
     */
    protected function normalizeMethods(array $methods): array
    {
        $filtered = collect($methods)
            ->reject(fn (string $method): bool => in_array($method, ['HEAD', 'OPTIONS'], true))
            ->values()
            ->all();

        return $filtered === [] ? ['GET'] : $filtered;
    }

    protected function resolveFolderName(IlluminateRoute $route): string
    {
        $controllerClass = $this->resolveControllerClass($route);

        if ($controllerClass !== null && Str::contains($controllerClass, '\\Modules\\')) {
            $segments = explode('\\', $controllerClass);
            $moduleIndex = array_search('Modules', $segments, true);

            if ($moduleIndex !== false && isset($segments[$moduleIndex + 1])) {
                return $segments[$moduleIndex + 1];
            }
        }

        $apiPrefix = trim((string) config('app.api_prefix', 'api'), '/');
        $relativeUri = trim(Str::after($route->uri(), $apiPrefix), '/');
        $firstSegment = Str::before($relativeUri, '/');

        return $firstSegment !== '' ? Str::headline(str_replace(['-', '_'], ' ', $firstSegment)) : 'General';
    }

    protected function resolveRequestName(IlluminateRoute $route, string $method): string
    {
        $actionMethod = $this->resolveControllerMethod($route);

        if ($actionMethod !== null && $actionMethod !== '__invoke') {
            return Str::headline($actionMethod);
        }

        $segment = Str::afterLast($route->uri(), '/');
        $segment = trim($segment, '{}');

        if ($segment === '' || Str::contains($segment, '{')) {
            return Str::headline(strtolower($method).' '.Str::beforeLast(trim($route->uri(), '/'), '/'));
        }

        return Str::headline(str_replace(['-', '_'], ' ', $segment));
    }

    protected function routeRequiresAuth(IlluminateRoute $route): bool
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if ($middleware === 'auth' || Str::startsWith($middleware, 'auth:')) {
                return true;
            }
        }

        return false;
    }

    protected function buildRequestBody(IlluminateRoute $route): ?array
    {
        if (! in_array($this->primaryMethod($route), ['POST', 'PUT', 'PATCH'], true)) {
            return null;
        }

        $rules = $this->extractFormRequestRules($route);

        if ($rules === []) {
            return null;
        }

        return [
            'mode' => 'raw',
            'raw' => $this->encodeJson($this->samplePayloadFromRules($rules)),
            'options' => [
                'raw' => [
                    'language' => 'json',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractFormRequestRules(IlluminateRoute $route): array
    {
        $controllerClass = $this->resolveControllerClass($route);
        $controllerMethod = $this->resolveControllerMethod($route);

        if ($controllerClass === null || $controllerMethod === null || ! class_exists($controllerClass) || ! method_exists($controllerClass, $controllerMethod)) {
            return [];
        }

        $reflectionMethod = new ReflectionMethod($controllerClass, $controllerMethod);

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $className = $type->getName();

            if (! is_subclass_of($className, FormRequest::class)) {
                continue;
            }

            /** @var FormRequest $request */
            $request = new $className();

            return $request->rules();
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function samplePayloadFromRules(array $rules): array
    {
        $payload = [];

        foreach ($rules as $field => $fieldRules) {
            $normalizedRules = $this->normalizeRuleTokens($fieldRules);
            $value = $this->sampleValueFromRules($field, $normalizedRules);

            if (str_contains($field, '.*.')) {
                $normalizedField = str_replace('.*.', '.0.', $field);
                Arr::set($payload, $normalizedField, $value);
            } else {
                Arr::set($payload, $field, $value);
            }

            if (in_array('confirmed', $normalizedRules, true)) {
                Arr::set($payload, $field.'_confirmation', $value);
            }
        }

        return $payload;
    }

    /**
     * @param  mixed  $rules
     * @return array<int, string>
     */
    protected function normalizeRuleTokens(mixed $rules): array
    {
        $tokens = [];

        foreach (Arr::wrap($rules) as $rule) {
            if ($rule instanceof ValidationRule) {
                $tokens[] = $rule::class;

                continue;
            }

            if ($rule instanceof UnitEnum) {
                $tokens[] = $rule->name;

                continue;
            }

            if (is_object($rule)) {
                if (method_exists($rule, '__toString')) {
                    $tokens[] = (string) $rule;
                }

                if (method_exists($rule, 'cases')) {
                    $tokens[] = $rule::class;
                }

                continue;
            }

            foreach (explode('|', (string) $rule) as $token) {
                $token = trim($token);

                if ($token !== '') {
                    $tokens[] = $token;
                }
            }
        }

        return $tokens;
    }

    /**
     * @param  array<int, string>  $rules
     */
    protected function sampleValueFromRules(string $field, array $rules): mixed
    {
        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'in:')) {
                return Str::before(Str::after($rule, 'in:'), ',');
            }
        }

        if ($this->hasRule($rules, 'email')) {
            return 'user@example.com';
        }

        if ($this->hasRule($rules, 'boolean')) {
            return true;
        }

        if ($this->hasRule($rules, 'integer')) {
            return 1;
        }

        if ($this->hasRule($rules, 'numeric')) {
            return 1;
        }

        if ($this->hasRule($rules, 'array')) {
            return [];
        }

        if ($this->hasRule($rules, 'date')) {
            return now()->toDateString();
        }

        if ($this->hasRule($rules, 'file') || $this->hasRule($rules, 'image')) {
            return '/absolute/path/to/file';
        }

        if ($this->hasRule($rules, 'confirmed')) {
            return 'secret123';
        }

        if (Str::contains($field, 'password')) {
            return 'secret123';
        }

        if (Str::contains($field, 'email')) {
            return 'user@example.com';
        }

        return match (true) {
            Str::contains($field, 'username') => 'demo_user',
            Str::contains($field, 'name') => 'demo',
            default => 'string',
        };
    }

    /**
     * @param  array<int, string>  $rules
     */
    protected function hasRule(array $rules, string $needle): bool
    {
        foreach ($rules as $rule) {
            if ($rule === $needle || Str::startsWith($rule, $needle.':')) {
                return true;
            }
        }

        return false;
    }

    protected function buildRequestDescription(IlluminateRoute $route): string
    {
        $lines = [
            'URI: /'.trim($route->uri(), '/'),
            'Middleware: '.implode(', ', $route->gatherMiddleware()),
        ];

        if ($action = $route->getActionName()) {
            $lines[] = 'Action: '.$action;
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    protected function buildRequestEvents(IlluminateRoute $route): ?array
    {
        $actionMethod = Str::lower((string) $this->resolveControllerMethod($route));

        return match ($actionMethod) {
            'login', 'register', 'refresh' => [$this->makeTestEvent([
                'let payload = null;',
                'try {',
                '    payload = pm.response.json();',
                '} catch (error) {',
                "    console.warn('Response is not valid JSON.', error);",
                '}',
                '',
                'const token = payload && payload.metadata ? payload.metadata.access_token : null;',
                '',
                'if (token) {',
                "    pm.environment.set('jwt_token', token);",
                '}',
            ])],
            'logout' => [$this->makeTestEvent([
                "pm.environment.set('jwt_token', '');",
            ])],
            default => null,
        };
    }

    /**
     * @param  array<int, string>  $scriptLines
     * @return array<string, mixed>
     */
    protected function makeTestEvent(array $scriptLines): array
    {
        return [
            'listen' => 'test',
            'script' => [
                'type' => 'text/javascript',
                'exec' => $scriptLines,
            ],
        ];
    }

    protected function primaryMethod(IlluminateRoute $route): string
    {
        return $this->normalizeMethods($route->methods())[0];
    }

    protected function resolveControllerClass(IlluminateRoute $route): ?string
    {
        $action = $route->getActionName();

        if ($action === 'Closure' || ! str_contains($action, '@')) {
            return class_exists($action) ? $action : null;
        }

        return Str::before($action, '@');
    }

    protected function resolveControllerMethod(IlluminateRoute $route): ?string
    {
        $action = $route->getActionName();

        if ($action === 'Closure') {
            return null;
        }

        if (str_contains($action, '@')) {
            return Str::after($action, '@');
        }

        if (class_exists($action) && (new ReflectionClass($action))->hasMethod('__invoke')) {
            return '__invoke';
        }

        return null;
    }

    protected function normalizeUriForPostman(string $uri): string
    {
        return preg_replace('/\{([^}]+)\}/', ':$1', trim($uri, '/')) ?? trim($uri, '/');
    }

    protected function resolveCollectionName(): string
    {
        $option = trim((string) $this->option('collection'));

        if ($option !== '') {
            return $option;
        }

        return trim((string) config('app.name', 'Laravel').' API');
    }

    protected function slugifyFileName(string $name): string
    {
        return Str::slug($name) ?: 'postman-collection';
    }

    protected function detectGitBranch(): string
    {
        $headFile = base_path('.git/HEAD');

        if (! File::exists($headFile)) {
            return '';
        }

        $head = trim((string) File::get($headFile));

        if (! Str::startsWith($head, 'ref: ')) {
            return '';
        }

        return Str::afterLast($head, '/');
    }

    protected function makeUuid(string $seed): string
    {
        $hash = md5($seed);

        return sprintf(
            '%s-%s-4%s-%s%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 13, 3),
            dechex((hexdec(substr($hash, 16, 1)) & 0x3) | 0x8),
            substr($hash, 17, 3),
            substr($hash, 20, 12),
        );
    }

    protected function encodeJson(mixed $payload): string
    {
        return (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function relativePath(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }

    protected function resolveOutputDirectory(): string
    {
        $output = trim((string) $this->option('output'));

        if ($output === '') {
            return base_path('postman');
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $output) === 1 || Str::startsWith($output, ['\\\\', '//', '/'])) {
            return $output;
        }

        return base_path(trim($output, '\\/'));
    }
}
