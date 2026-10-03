<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'code',
        'secret',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Keep full technical detail in protected server logs only.
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if (config('app.debug')) {
                return null;
            }

            // Validation exceptions are already deliberately user-safe and
            // should retain their field-level feedback.
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return null;
            }

            $status = $this->safeStatus($e);
            $message = match ($status) {
                401 => 'Authentication is required to continue.',
                403 => 'You are not authorised to perform this action.',
                404 => 'The requested resource could not be found.',
                419 => 'Your session has expired. Please refresh the page and try again.',
                429 => 'Too many requests were received. Please try again shortly.',
                503 => 'The service is temporarily unavailable. Please try again later.',
                default => 'The request could not be completed. The incident has been logged.',
            };

            if ($request->expectsJson()) {
                return new JsonResponse(['message' => $message], $status);
            }

            if (view()->exists('errors.'.$status)) {
                return response()->view('errors.'.$status, ['message' => $message], $status);
            }

            return response()->view('errors.generic', [
                'status' => $status,
                'message' => $message,
            ], $status);
        });
    }

    private function safeStatus(Throwable $e): int
    {
        if ($e instanceof TokenMismatchException) {
            return 419;
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof QueryException) {
            return 500;
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            return in_array($status, [400, 401, 403, 404, 405, 409, 419, 422, 429, 503], true)
                ? $status
                : 500;
        }

        return 500;
    }
}
