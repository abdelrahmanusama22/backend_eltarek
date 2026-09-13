<?php

use App\Http\Middleware\AddRequestId;
use App\Http\Middleware\Idempotency;
use App\Http\Middleware\LogApiRequest;
use App\Support\ApiErrorResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [AddRequestId::class, LogApiRequest::class]);
        $middleware->alias(['idempotency' => Idempotency::class]);

        // API-only app: never redirect unauthenticated requests to a login
        // page — always answer 401 JSON.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->render(function (ValidationException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiErrorResponse::make($request, 'validation_failed', 'Validation failed.', 422, $exception->errors())
                : null;
        });
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiErrorResponse::make($request, 'unauthenticated', 'Authentication is required.', 401)
                : null;
        });
        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiErrorResponse::make($request, 'not_found', 'The requested resource was not found.', 404)
                : null;
        });
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            $status = $exception->getStatusCode();
            $code = match ($status) {
                403 => 'forbidden', 404 => 'not_found', 429 => 'rate_limited',
                default => $status >= 500 ? 'server_error' : 'http_error',
            };

            return ApiErrorResponse::make($request, $code, $exception->getMessage() ?: 'Request failed.', $status);
        });
        $exceptions->render(function (Throwable $exception, Request $request) {
            return $request->is('api/*')
                ? ApiErrorResponse::make($request, 'server_error', 'An unexpected server error occurred.', 500)
                : null;
        });
    })->create();
