<?php

namespace App\Http\Controllers;

use App\Jobs\BuildDataExport;
use App\Models\DataExport;
use App\Models\User;
use App\Support\DemoRestriction;
use App\Support\SignedMediaUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Data exports (spec section 6.7). Asking for one queues a job that builds
 * the ZIP and emails a link. Only one export is kept per user, so a new
 * request replaces the last one.
 */
class DataExportController extends Controller
{
    /**
     * Queue a new export.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        DemoRestriction::refuse($user, 'export', 'Exports are turned off in the demo.');

        if ($user->dataExports()->get()->contains(fn (DataExport $export) => $export->isPending())) {
            throw ValidationException::withMessages([
                'export' => "Your export is already being made. We'll email you when it's ready.",
            ]);
        }

        $user->dataExports()->get()->each(function (DataExport $export) {
            $export->deleteFile();
            $export->delete();
        });

        BuildDataExport::dispatch($user->dataExports()->create());

        return back()->with('status', 'export-requested');
    }

    /**
     * Download a finished export. The link is signed and expires with the
     * export, and it only works for the signed-in owner.
     */
    public function show(Request $request, string $export): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $dataExport = $user->dataExports()->where('ulid', $export)->firstOrFail();

        abort_unless($dataExport->isReady(), 404);

        return SignedMediaUrl::redirect((string) $dataExport->path, [
            'ResponseContentType' => 'application/zip',
            'ResponseContentDisposition' => 'attachment; filename="'.$dataExport->downloadName().'"',
        ]);
    }
}
