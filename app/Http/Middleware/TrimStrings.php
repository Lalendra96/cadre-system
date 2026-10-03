<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /** Capture invalid input before Laravel silently trims it. */
    public function handle($request, \Closure $next)
    {
        $errors = [];
        $inspect = function (array $values, string $prefix = '') use (&$inspect, &$errors): void {
            foreach ($values as $key => $value) {
                $field = $prefix === '' ? (string) $key : $prefix.'.'.$key;
                if (in_array($field, ['_token', '_method'], true)) {
                    continue;
                }
                if (is_array($value)) {
                    $inspect($value, $field);
                } elseif (is_string($value) && preg_match('/^[\\s\\p{Z}\\x{FEFF}]/u', $value) === 1) {
                    $label = str_replace(['_', '.'], ' ', $field);
                    $errors[$field] = ucfirst($label).' must not begin with whitespace.';
                }
            }
        };
        $inspect($request->query->all());
        $inspect($request->isJson() ? $request->json()->all() : $request->request->all());
        $request->attributes->set('leading_whitespace_errors', $errors);

        return parent::handle($request, $next);
    }

    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array<int, string>
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
    ];
}
