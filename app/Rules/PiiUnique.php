<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Employee;
use App\Models\Intern;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PiiUnique implements ValidationRule
{
    public function __construct(
        private readonly string $modelClass,
        private readonly string $field,
        private readonly ?int $ignoreId = null,
    ) {
        if (! in_array($modelClass, [Employee::class, Intern::class], true)) {
            throw new \InvalidArgumentException('PiiUnique only supports protected personnel models.');
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || trim((string) $value) === '') {
            return;
        }

        $query = ($this->modelClass)::query()->wherePiiEquals($this->field, (string) $value);
        if ($this->ignoreId !== null) {
            $query->whereKeyNot($this->ignoreId);
        }

        if ($query->exists()) {
            $fail('The :attribute has already been registered.');
        }
    }
}
