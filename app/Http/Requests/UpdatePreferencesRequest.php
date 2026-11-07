<?php

namespace App\Http\Requests;

use App\Enums\Theme;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Account preferences. Each field is optional, so the interface can save a
 * single setting, such as the theme, as soon as it changes.
 */
class UpdatePreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'theme' => ['sometimes', Rule::enum(Theme::class)],
            'timezone' => ['sometimes', 'string', 'timezone:all_with_bc'],
        ];
    }
}
