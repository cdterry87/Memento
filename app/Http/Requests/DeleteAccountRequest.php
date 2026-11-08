<?php

namespace App\Http\Requests;

use App\Support\DemoRestriction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Deleting an account can't be undone, so it needs the current password.
 */
class DeleteAccountRequest extends FormRequest
{
    /**
     * The error bag the account page reads, so other forms keep their errors.
     *
     * @var string
     */
    protected $errorBag = 'deleteAccount';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Everyone shares the demo account, so no visitor can delete it.
     */
    protected function prepareForValidation(): void
    {
        DemoRestriction::refuse($this->user(), 'password', 'The demo account can\'t be deleted.', $this->errorBag);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password:web'],
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
            'password.required' => 'Enter your password to delete your account.',
            'password.current_password' => "That password isn't right.",
        ];
    }
}
