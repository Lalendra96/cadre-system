<?php

declare(strict_types=1);

namespace App\Http\Requests\ServiceLetters;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceLetterCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:1500'],
        ];
    }
}
