<?php

namespace App\Actions;

use App\Models\DataExport;
use App\Models\MemoryPhoto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes an account and everything in it (spec section 6.7). Database
 * cascades remove the rows, but they don't fire model events, so every
 * photo and export file is collected first and deleted here explicitly.
 */
class DeleteAccount
{
    public function handle(User $user): void
    {
        $paths = [
            ...$user->photos()
                ->get(['memory_photos.path', 'memory_photos.thumbnail_path'])
                ->flatMap(fn (MemoryPhoto $photo) => [$photo->path, $photo->thumbnail_path])
                ->all(),
            ...$user->dataExports()
                ->whereNotNull('path')
                ->get(['path'])
                ->map(fn (DataExport $export) => (string) $export->path)
                ->all(),
        ];

        DB::transaction(function () use ($user) {
            if (config('session.driver') === 'database') {
                DB::table((string) config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            $user->delete();
        });

        if ($paths !== []) {
            Storage::disk('media')->delete($paths);
        }
    }
}
