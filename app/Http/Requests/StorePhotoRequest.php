<?php

namespace App\Http\Requests;

use App\Support\DemoRestriction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * One photo for the day in the URL. The type is checked from the file's
 * content, not its name. The per-day limit is enforced by StorePhoto, where
 * it can't be raced.
 */
class StorePhotoRequest extends FormRequest
{
    /**
     * Largest accepted upload, in kilobytes.
     */
    public const MAX_KILOBYTES = 10 * 1024;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * The demo account can't upload, so visitors can't put files in the bucket.
     */
    protected function prepareForValidation(): void
    {
        DemoRestriction::refuse($this->user(), 'photo', 'Adding photos is turned off in the demo.');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                File::image()->types(['jpeg', 'png', 'webp'])->max(self::MAX_KILOBYTES),
                // Guards against tiny files that decode to huge images.
                'dimensions:max_width=12000,max_height=12000',
            ],
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
            'photo.required' => 'Choose a photo to add.',
            'photo.image' => 'Photos must be JPEG, PNG, or WebP images.',
            'photo.mimes' => 'Photos must be JPEG, PNG, or WebP images.',
            'photo.max' => 'Photos can be up to 10 MB each.',
            'photo.dimensions' => 'That photo is too large to process.',
        ];
    }
}
