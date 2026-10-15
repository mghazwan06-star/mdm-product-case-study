<?php

use App\Http\PlainErrors;
use App\Models\Asset;
use App\Models\Employee;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Plain-language API errors.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $model = $e->getPrevious() instanceof ModelNotFoundException ? $e->getPrevious()->getModel() : null;

            $message = match ($model) {
                Asset::class => "This laptop doesn't exist. It may have been deleted.",
                Employee::class => "This person doesn't exist. They may have been deleted.",
                default => "We couldn't find what you asked for.",
            };

            return response()->json(['message' => $message], 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = $request->is('api/sync')
                ? 'To run a sync, press Sync Now on the website (or send a POST request to /api/sync).'
                : "This action isn't available. WorkWize copies data from Jamf, so records can be deleted here but not edited.";

            return response()->json(['message' => $message], 405);
        });

        // Anything else unexpected: a plain message, never technical details (still logged).
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof HttpExceptionInterface || $e instanceof ValidationException) {
                return null;
            }

            return PlainErrors::render($e, $request);
        });
    })->create();
