<?php

namespace App\Console\Commands;

use App\Models\User;
use DateTimeZone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Prompts\Exceptions\NonInteractiveValidationException;

use function Laravel\Prompts\password;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

/**
 * Creates an account from the console, so the owner can sign up even when
 * public registration is off, as it is in demo mode. The email address is
 * marked as verified, since whoever runs this has access to the server.
 */
#[Signature('user:create
    {--name= : The person\'s name}
    {--email= : The email address they sign in with}
    {--timezone= : Their time zone, such as America/Chicago}
    {--password= : Skips the password prompt. Avoid it where others can read your shell history}
    {--unverified : Ask them to verify their email address on first sign-in}')]
#[Description('Create a user account, even when registration is turned off')]
class CreateUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timezones = DateTimeZone::listIdentifiers();

        if (! $this->input->isInteractive()) {
            return $this->createFromOptions();
        }

        try {
            $input = $this->askForDetails($timezones);
        } catch (NonInteractiveValidationException) {
            // Prompts can't be answered without a terminal, such as when input is piped in.
            return $this->createFromOptions();
        }

        return $this->create($input);
    }

    /**
     * @param  array{name: string, email: string, timezone: string, password: string}  $input
     */
    private function create(array $input): int
    {
        $validator = Validator::make($input, $this->rules());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->forceFill([
            'name' => $input['name'],
            'email' => strtolower($input['email']),
            'password' => $input['password'],
            'timezone' => $input['timezone'],
            'email_verified_at' => $this->option('unverified') ? null : now(),
        ])->save();

        $this->components->info("Created an account for {$user->email}.");

        return self::SUCCESS;
    }

    /**
     * Each detail from its option, or asked for when it wasn't given.
     *
     * @param  list<string>  $timezones
     * @return array{name: string, email: string, timezone: string, password: string}
     */
    private function askForDetails(array $timezones): array
    {
        return [
            'name' => $this->option('name') ?: text(
                label: 'Name',
                required: true,
                validate: fn (string $value) => $this->firstError(['name' => $value], 'name'),
            ),
            'email' => $this->option('email') ?: text(
                label: 'Email',
                required: true,
                validate: fn (string $value) => $this->firstError(['email' => $value], 'email'),
            ),
            'timezone' => $this->option('timezone') ?: suggest(
                label: 'Time zone',
                options: fn (string $value) => array_values(array_filter(
                    $timezones,
                    fn (string $timezone) => str_contains(strtolower($timezone), strtolower($value)),
                )),
                placeholder: 'Start typing, such as Chicago',
                required: true,
                validate: fn (string $value) => $this->firstError(['timezone' => $value], 'timezone'),
                scroll: 10,
            ),
            'password' => $this->option('password') ?: password(
                label: 'Password',
                required: true,
                validate: fn (string $value) => $this->firstError(['password' => $value], 'password'),
                hint: 'At least 8 characters.',
            ),
        ];
    }

    /**
     * Without a terminal nothing can be asked, so every detail must be an option.
     */
    private function createFromOptions(): int
    {
        $missing = array_filter(['name', 'email', 'timezone', 'password'], fn (string $option) => blank($this->option($option)));

        if ($missing === []) {
            return $this->create([
                'name' => (string) $this->option('name'),
                'email' => (string) $this->option('email'),
                'timezone' => (string) $this->option('timezone'),
                'password' => (string) $this->option('password'),
            ]);
        }

        $this->components->error('Run this in a terminal to be asked for each detail, or pass --'.implode(', --', $missing).'.');

        return self::FAILURE;
    }

    /**
     * The same rules as registration, without the bot check.
     *
     * @return array<string, array<mixed>>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'password' => ['required', 'string', Password::default()],
        ];
    }

    /**
     * Checks one field as it is typed, so a prompt can ask again.
     *
     * @param  array<string, string>  $input
     */
    private function firstError(array $input, string $field): ?string
    {
        return Validator::make($input, [$field => $this->rules()[$field]])->errors()->first($field) ?: null;
    }
}
