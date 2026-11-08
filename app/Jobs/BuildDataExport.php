<?php

namespace App\Jobs;

use App\Models\DataExport;
use App\Models\Memory;
use App\Models\MemoryPhoto;
use App\Models\Reason;
use App\Models\User;
use App\Notifications\DataExportReady;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Builds a ZIP of everything one user has kept (spec section 6.7): a JSON
 * file with their account and every entry, and each photo at full size.
 * The ZIP goes to the private `media` disk, and the user is emailed a link
 * that works for 24 hours.
 */
class BuildDataExport implements ShouldQueue
{
    use Queueable;

    /**
     * Building a large journal can take a while.
     */
    public int $timeout = 600;

    public int $tries = 2;

    public function __construct(public DataExport $export) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->export->user;
        $workspace = storage_path('app/tmp/exports/'.$this->export->ulid);
        $zipPath = "{$workspace}.zip";

        File::ensureDirectoryExists($workspace);

        try {
            $this->writeZip($user, $workspace, $zipPath);

            $path = "exports/{$this->export->ulid}.zip";
            $stream = fopen($zipPath, 'rb');

            if ($stream === false) {
                throw new RuntimeException("The export at {$zipPath} could not be read.");
            }

            Storage::disk('media')->writeStream($path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $this->export->update([
                'path' => $path,
                'size_bytes' => File::size($zipPath),
                'completed_at' => now(),
                'expires_at' => now()->addHours(DataExport::EXPIRES_AFTER_HOURS),
            ]);
        } finally {
            File::deleteDirectory($workspace);
            File::delete($zipPath);
        }

        $user->notify(new DataExportReady($this->export));
    }

    /**
     * Marks the export as failed, so the account page offers to try again.
     */
    public function failed(?Throwable $exception): void
    {
        $this->export->update(['failed_at' => now()]);
    }

    /**
     * Photos are copied to the workspace first, because ZipArchive reads
     * added files only when the archive is closed.
     */
    private function writeZip(User $user, string $workspace, string $zipPath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("The export at {$zipPath} could not be created.");
        }

        $entries = [];

        $user->memories()
            ->with(['mood', 'reasons', 'photos'])
            ->orderBy('date')
            ->chunk(200, function ($memories) use (&$entries, $zip, $workspace) {
                foreach ($memories as $memory) {
                    $entries[] = $this->entry($memory, $zip, $workspace);
                }
            });

        $zip->addFromString('memento.json', (string) json_encode([
            'exported_at' => now()->toIso8601String(),
            'account' => $this->account($user),
            'entries' => $entries,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $zip->addFromString('README.txt', $this->readme(count($entries)));

        if (! $zip->close()) {
            throw new RuntimeException("The export at {$zipPath} could not be written.");
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function account(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'timezone' => $user->timezone,
            'theme' => $user->theme->value,
            'show_quotes' => $user->show_quotes,
            'reminder_emails' => $user->reminder_emails,
            'reminder_time' => substr($user->reminder_time, 0, 5),
            'on_this_day_emails' => $user->on_this_day_emails,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(Memory $memory, ZipArchive $zip, string $workspace): array
    {
        $date = $memory->date->toDateString();

        return [
            'date' => $date,
            'mood' => [
                'slug' => $memory->mood->slug,
                'label' => $memory->mood->label,
                'emoji' => $memory->mood->emoji,
                'score' => $memory->mood->score,
            ],
            'reasons' => $memory->reasons->map(fn (Reason $reason) => [
                'slug' => $reason->slug,
                'label' => $reason->label,
                'emoji' => $reason->emoji,
            ])->all(),
            'message' => $memory->body,
            'photos' => $memory->photos->values()->map(
                fn (MemoryPhoto $photo, int $index) => $this->addPhoto($photo, "photos/{$date}-".($index + 1), $zip, $workspace),
            )->all(),
            'created_at' => $memory->created_at?->toIso8601String(),
            'updated_at' => $memory->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Adds one photo to the archive and returns its path inside it.
     */
    private function addPhoto(MemoryPhoto $photo, string $name, ZipArchive $zip, string $workspace): string
    {
        $extension = Str::afterLast($photo->path, '.') ?: 'jpg';
        $archivePath = "{$name}.{$extension}";
        $localPath = $workspace.'/'.str_replace('/', '_', $archivePath);

        $source = Storage::disk('media')->readStream($photo->path);
        $target = fopen($localPath, 'wb');

        if ($source === null || $target === false) {
            throw new RuntimeException("The photo {$photo->ulid} could not be copied into the export.");
        }

        stream_copy_to_stream($source, $target);
        fclose($target);
        fclose($source);

        $zip->addFile($localPath, $archivePath);

        return $archivePath;
    }

    private function readme(int $count): string
    {
        $entries = $count.' '.Str::plural('entry', $count);
        $exportedOn = now()->toDayDateTimeString();

        return <<<TEXT
            Your Memento export

            memento.json holds your account settings and every entry you've kept
            ({$entries}): the date, mood, reasons, message, and photos for each day.

            The photos folder holds each photo at full size, named by its date.

            Exported on {$exportedOn} (UTC).

            TEXT;
    }
}
