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
            'show_quotes' => ['sometimes', 'boolean'],
            'reminder_emails' => ['sometimes', 'boolean'],
            'on_this_day_emails' => ['sometimes', 'boolean'],
            'reminder_time' => ['sometimes', 'string', 'regex:/^([01]\d|2[0-3]):00$/'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reminder_time.regex' => 'Choose an hour for your reminder.',
        ];
    }
}
