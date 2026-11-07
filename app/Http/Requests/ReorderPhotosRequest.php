<?php

namespace App\Http\Requests;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A new order for the day's photos: every one of them, each exactly once.
 */
class ReorderPhotosRequest extends FormRequest
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
            'photos' => ['required', 'array'],
            'photos.*' => ['required', 'string', 'distinct'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var User $user */
                $user = $this->user();
                /** @var CarbonImmutable $date */
                $date = $this->route('date');

                $expected = $user->photos()
                    ->where('memories.date', $date->toDateString())
                    ->pluck('memory_photos.ulid')
                    ->sort()
                    ->values()
                    ->all();

                if (collect($this->photos())->sort()->values()->all() !== $expected) {
                    $validator->errors()->add('photos', 'The photos changed. Refresh the page and try again.');
                }
            },
        ];
    }

    /**
     * @return list<string>
     */
    public function photos(): array
    {
        return array_values($this->array('photos'));
    }
}
