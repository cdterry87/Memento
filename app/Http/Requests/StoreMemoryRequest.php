<?php

namespace App\Http\Requests;

use App\Models\Memory;
use App\Models\Mood;
use App\Models\Reason;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * A check-in for the date in the URL. Moods and reasons are sent by slug.
 * Retired options are refused, except those already on this entry, so an
 * old entry can still be saved unchanged.
 */
class StoreMemoryRequest extends FormRequest
{
    private ?Memory $existingMemory = null;

    private bool $existingMemoryLoaded = false;

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
        $existing = $this->existingMemory();

        return [
            'mood' => [
                'required',
                'string',
                Rule::in(Mood::pickable()->pluck('slug')->push($existing?->mood->slug)->filter()),
            ],
            'reasons' => ['present', 'array'],
            'reasons.*' => [
                'string',
                'distinct',
                Rule::in(Reason::pickable()->pluck('slug')->merge($existing?->reasons->pluck('slug') ?? [])),
            ],
            'body' => ['nullable', 'string', 'max:255'],
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
            'mood.required' => 'Pick a mood for the day.',
            'body.max' => 'Keep it to 255 characters or fewer.',
        ];
    }

    public function checkInDate(): CarbonImmutable
    {
        /** @var CarbonImmutable $date */
        $date = $this->route('date');

        return $date;
    }

    public function mood(): Mood
    {
        return Mood::where('slug', $this->string('mood')->toString())->sole();
    }

    /**
     * @return Collection<int, Reason>
     */
    public function reasons(): Collection
    {
        return Reason::whereIn('slug', $this->array('reasons'))->get();
    }

    public function body(): ?string
    {
        $body = $this->input('body');

        return is_string($body) && $body !== '' ? $body : null;
    }

    private function existingMemory(): ?Memory
    {
        if (! $this->existingMemoryLoaded) {
            /** @var User $user */
            $user = $this->user();

            $this->existingMemory = $user->memories()
                ->with(['mood', 'reasons'])
                ->where('date', $this->checkInDate()->toDateString())
                ->first();
            $this->existingMemoryLoaded = true;
        }

        return $this->existingMemory;
    }
}
