<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ValidateFormInput
{
    /** Run after session / CSRF middleware so HTML forms retain normal error bags. */
    public function handle(Request $request, Closure $next): Response
    {
        $errors = $request->attributes->get('leading_whitespace_errors', []);
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $next($request);
    }
}
