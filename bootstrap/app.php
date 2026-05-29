<?php

use App\Core\Helpers\LocaleHelper;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            $apiPrefix = trim((string) config('app.api_prefix', 'api'), '/');

            foreach ([__DIR__.'/../routes/api.php', ...glob(__DIR__.'/../app/Http/Modules/*/Routes/api.php')] as $apiRoute) {
                if (realpath($apiRoute) !== false) {
                    Route::middleware('api')->prefix($apiPrefix)->group($apiRoute);
                }
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', SetLocale::class);
        $middleware->appendToGroup('api', SetLocale::class);
    })
    ->withCommands()
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (HttpResponse $response, Throwable $exception, Request $request) {
            LocaleHelper::apply($request);

            $apiPrefix = trim((string) config('app.api_prefix', 'api'), '/');
            $isApiRequest = $request->is($apiPrefix) || $request->is($apiPrefix.'/*');

            if (! $isApiRequest) {
                return $response;
            }

            $statusCode = match (true) {
                $exception instanceof ValidationException => HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
                $exception instanceof AuthenticationException => HttpResponse::HTTP_UNAUTHORIZED,
                $exception instanceof AuthorizationException => HttpResponse::HTTP_FORBIDDEN,
                $exception instanceof ModelNotFoundException => HttpResponse::HTTP_NOT_FOUND,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => $response->getStatusCode() && $response->getStatusCode() !== HttpResponse::HTTP_OK
                    ? $response->getStatusCode()
                    : HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
            };

            $message = match (true) {
                $exception instanceof ValidationException => __('global.errors.validation_failed'),
                $exception instanceof AuthenticationException => __('global.errors.unauthenticated'),
                $exception instanceof AuthorizationException => __('global.errors.forbidden'),
                $exception instanceof ModelNotFoundException => __('global.errors.model_not_found'),
                $statusCode === HttpResponse::HTTP_NOT_FOUND => __('global.errors.route_not_found'),
                $statusCode === HttpResponse::HTTP_METHOD_NOT_ALLOWED => __('global.errors.method_not_allowed'),
                $statusCode >= HttpResponse::HTTP_INTERNAL_SERVER_ERROR => __('global.errors.server_error'),
                default => $exception->getMessage() ?: __('global.errors.generic'),
            };

            $metadata = $exception instanceof ValidationException ? $exception->errors() : null;

            if (($metadata === null) && (app()->hasDebugModeEnabled() || config('app.debug'))) {
                $metadata = [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ];
            }

            $payload = [
                'message' => $message,
                'statusCode' => $statusCode,
                'metadata' => $metadata,
                'path' => $request->getPathInfo(),
                'timestamp' => now()->toISOString(),
            ];

            if (app()->hasDebugModeEnabled() || config('app.debug')) {
                $payload['stack'] = $exception->getTraceAsString();
            }

            return response()
                ->json($payload, $statusCode)
                ->header('Content-Language', app()->currentLocale());
        });
    })->create();
