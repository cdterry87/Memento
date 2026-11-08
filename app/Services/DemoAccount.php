<?php

namespace App\Services;

use App\Enums\Theme;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\Mood;
use App\Models\Reason;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Creates the demo account, or restores it to a fresh state (spec section
 * 6.9). It only ever touches the one account marked `is_demo`.
 *
 * Entries are generated relative to today in the demo's time zone and cover
 * a little over two years, so "on this day" and the recap always have
 * something to show. Today is left empty for visitors to try the check-in.
 * The data is random but repeatable for a given date.
 */
class DemoAccount
{
    /** How far back the entries go. */
    public const MONTHS_OF_ENTRIES = 27;

    /** The chance of an entry on any day outside a gap. */
    private const CHECK_IN_CHANCE = 0.8;

    /** The chance that a gap of a few days starts on a given day. */
    private const GAP_CHANCE = 0.03;

    /** The chance that a good day gets a photo. */
    private const PHOTO_CHANCE = 0.14;

    /** The chance that an entry has a message. */
    private const MESSAGE_CHANCE = 0.65;

    /** Where the sample photos come from: the license-free quote backgrounds. */
    public const SAMPLE_PHOTO_DIRECTORY = 'inspirational-quote-backgrounds';

    /**
     * How often each mood score comes up. Mostly good days, as in real journals.
     *
     * @var array<int, int>
     */
    private const SCORE_WEIGHTS = [1 => 4, 2 => 11, 3 => 22, 4 => 33, 5 => 30];

    /**
     * Reasons that tend to go with good days, and with harder ones, so the
     * recap's "What lifted you" has a story to tell.
     *
     * @var list<string>
     */
    private const LIFTING_REASONS = ['family', 'friends', 'travel', 'hobbies', 'entertainment', 'health', 'relationship'];

    /** @var list<string> */
    private const HEAVY_REASONS = ['work', 'finances', 'school', 'health', 'other'];

    /**
     * Messages by mood score band. Short and ordinary, like real entries.
     *
     * @var array<string, list<string>>
     */
    private const MESSAGES = [
        'low' => [
            'Long day. Glad it is over.',
            'Everything took twice as long as it should have.',
            'Not my best day. Tomorrow is a new one.',
            'Felt run down all afternoon.',
            'Stressful meeting, but I got through it.',
            'Rain, traffic, and a headache.',
            'Bills to sort out. Took a walk afterwards and it helped a bit.',
            'Couldn\'t sleep well. Early night tonight.',
        ],
        'mid' => [
            'A quiet one. Laundry, groceries, and an early night.',
            'Nothing special, and that was fine.',
            'Busy at work, nice dinner at home.',
            'Tried a new recipe. It was okay.',
            'Caught up on emails and made a dent in the to-do list.',
            'Watched a movie and went to bed early.',
            'Slow morning, productive afternoon.',
            'Called Mom. She says hi.',
        ],
        'high' => [
            'Pancakes and a long walk by the river.',
            'Dinner with friends. Laughed until my face hurt.',
            'Finally finished the painting I started last month!',
            'Beach day. Perfect weather.',
            'Hiked to the lookout. The view was worth every step.',
            'Game night with the family. I won, for once.',
            'Got good news at work today.',
            'Picnic in the park and a sunset on the way home.',
            'Ran my fastest 5K yet.',
            'Coffee with an old friend I hadn\'t seen in years.',
            'Planted tomatoes and basil in the garden.',
            'Road trip to the mountains. Windows down the whole way.',
            'A lazy Sunday with a good book.',
            'Concert tonight. Still buzzing.',
        ],
    ];

    /** @var array<string, ProcessedImage> */
    private array $processed = [];

    public function __construct(
        private ImageProcessor $images,
        private ?string $samplePhotoDirectory = null,
    ) {}

    /**
     * Whether the demo account's credentials are set.
     */
    public static function configured(): bool
    {
        return filled(config('memento.demo.email')) && filled(config('memento.demo.password'));
    }

    /**
     * The demo account, if it has been created.
     */
    public static function user(): ?User
    {
        return User::query()->where('is_demo', true)->first();
    }

    /**
     * Create the demo account, or restore its details, and replace its
     * entries and photos with freshly generated ones.
     *
     * @throws RuntimeException when the credentials are missing, or a real account already uses the email.
     */
    public function reset(): User
    {
        if (! self::configured()) {
            throw new RuntimeException('Set DEMO_EMAIL and DEMO_PASSWORD to create the demo account.');
        }

        /** @var array{name: string, email: string, password: string, timezone: string} $config */
        $config = config('memento.demo');

        $user = DB::transaction(fn () => $this->restoreAccount($config));
        $this->removeEntries($user);
        $this->createEntries($user, CarbonImmutable::now($user->timezone)->startOfDay());

        return $user;
    }

    /**
     * @param  array{name: string, email: string, password: string, timezone: string}  $config
     */
    private function restoreAccount(array $config): User
    {
        $user = self::user();

        if ($user === null && User::query()->where('email', $config['email'])->exists()) {
            throw new RuntimeException("A regular account already uses {$config['email']}, so it can't become the demo account.");
        }

        $user ??= new User;

        $user->forceFill([
            'name' => $config['name'],
            'email' => $config['email'],
            'password' => $config['password'],
            'timezone' => $config['timezone'],
            'theme' => Theme::System,
            'show_quotes' => true,
            'reminder_emails' => false,
            'reminder_time' => '20:00:00',
            'on_this_day_emails' => false,
            'is_demo' => true,
            'email_verified_at' => now(),
            // Ends "remember me" sign-ins from before the reset.
            'remember_token' => Str::random(60),
        ])->save();

        return $user;
    }

    /**
     * Deletes the demo user's entries and every photo file they had.
     */
    private function removeEntries(User $user): void
    {
        $paths = $user->photos()
            ->get(['memory_photos.path', 'memory_photos.thumbnail_path'])
            ->flatMap(fn (MemoryPhoto $photo) => [$photo->path, $photo->thumbnail_path])
            ->all();

        $user->memories()->delete();

        if ($paths !== []) {
            Storage::disk('media')->delete($paths);
        }
    }

    private function createEntries(User $user, CarbonImmutable $today): void
    {
        $random = new Randomizer(new Mt19937(crc32($today->toDateString())));
        $moodsByScore = Mood::pickable()->get()->groupBy('score');
        $reasons = Reason::pickable()->get()->keyBy('slug');
        $samplePhotos = $this->samplePhotos();
        $gapDaysLeft = 0;

        $day = $today->subMonths(self::MONTHS_OF_ENTRIES);

        while ($day->lessThan($today)) {
            if ($gapDaysLeft > 0) {
                $gapDaysLeft--;
            } elseif ($this->chance($random, self::GAP_CHANCE)) {
                $gapDaysLeft = $random->getInt(2, 6);
            } elseif ($this->chance($random, self::CHECK_IN_CHANCE)) {
                $this->createEntry($user, $day, $random, $moodsByScore, $reasons, $samplePhotos);
            }

            $day = $day->addDay();
        }
    }

    /**
     * @param  SupportCollection<int, Collection<int, Mood>>  $moodsByScore
     * @param  Collection<string, Reason>  $reasons
     * @param  list<string>  $samplePhotos
     */
    private function createEntry(
        User $user,
        CarbonImmutable $day,
        Randomizer $random,
        SupportCollection $moodsByScore,
        Collection $reasons,
        array $samplePhotos,
    ): void {
        $score = $this->weightedPick($random, self::SCORE_WEIGHTS);
        /** @var list<Mood> $moods */
        $moods = $moodsByScore->get($score, $moodsByScore->flatten(1))->values()->all();
        $mood = $moods[$random->getInt(0, count($moods) - 1)];

        $band = match (true) {
            $score <= 2 => 'low',
            $score === 3 => 'mid',
            default => 'high',
        };

        $memory = $user->memories()->create([
            'mood_id' => $mood->id,
            'date' => $day->toDateString(),
            'body' => $this->chance($random, self::MESSAGE_CHANCE)
                ? self::MESSAGES[$band][$random->getInt(0, count(self::MESSAGES[$band]) - 1)]
                : null,
        ]);

        $memory->reasons()->attach($this->pickReasons($random, $reasons, $band)->pluck('id'));

        if ($band === 'high' && $samplePhotos !== [] && $this->chance($random, self::PHOTO_CHANCE)) {
            foreach (range(1, $random->getInt(1, 2)) as $position) {
                $this->attachPhoto($memory, $samplePhotos[$random->getInt(0, count($samplePhotos) - 1)], $position);
            }
        }
    }

    /**
     * One to three reasons, leaning towards the ones that fit the mood.
     *
     * @param  Collection<string, Reason>  $reasons
     * @return Collection<int, Reason>
     */
    private function pickReasons(Randomizer $random, Collection $reasons, string $band): Collection
    {
        $leaning = match ($band) {
            'high' => self::LIFTING_REASONS,
            'low' => self::HEAVY_REASONS,
            default => $reasons->keys()->all(),
        };

        $slugs = [];

        foreach (range(1, $random->getInt(1, 3)) as $_) {
            $pool = $this->chance($random, 0.8) ? $leaning : $reasons->keys()->all();
            $slugs[] = $pool[$random->getInt(0, count($pool) - 1)];
        }

        /** @var Collection<int, Reason> $picked */
        $picked = new Collection;

        foreach (array_unique($slugs) as $slug) {
            $reason = $reasons->get($slug);

            if ($reason !== null) {
                $picked->push($reason);
            }
        }

        return $picked;
    }

    /**
     * The sample images, each processed once and kept in memory, so every
     * demo photo is the same size and has no metadata, like a real upload.
     *
     * @return list<string>
     */
    private function samplePhotos(): array
    {
        $directory = $this->samplePhotoDirectory ?? public_path(self::SAMPLE_PHOTO_DIRECTORY);

        if (! File::isDirectory($directory)) {
            return [];
        }

        return array_values(collect(File::files($directory))
            ->filter(fn (SplFileInfo $file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'webp'], true))
            ->map(fn (SplFileInfo $file) => $file->getPathname())
            ->sort()
            ->all());
    }

    private function attachPhoto(Memory $memory, string $source, int $position): void
    {
        $image = $this->processed[$source] ??= $this->images->process($source);

        $name = Str::random(40);
        $path = "photos/{$name}.jpg";
        $thumbnailPath = "photos/{$name}_thumb.jpg";

        Storage::disk('media')->put($path, $image->full);
        Storage::disk('media')->put($thumbnailPath, $image->thumbnail);

        $memory->photos()->create([
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'width' => $image->width,
            'height' => $image->height,
            'size_bytes' => strlen($image->full),
            'position' => $position,
        ]);
    }

    private function chance(Randomizer $random, float $probability): bool
    {
        return $random->getFloat(0, 1) < $probability;
    }

    /**
     * @param  non-empty-array<int, int>  $weights
     */
    private function weightedPick(Randomizer $random, array $weights): int
    {
        $roll = $random->getInt(1, array_sum($weights));

        foreach ($weights as $value => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $value;
            }
        }

        return max(array_keys($weights));
    }
}
