import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive, type ComputedRef } from 'vue';

export const MAX_PHOTOS_PER_DAY = 4;
export const MAX_PHOTO_BYTES = 10 * 1024 * 1024;
export const ACCEPTED_PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

export type UploadStatus = 'waiting' | 'uploading' | 'failed';

export interface PendingUpload {
    key: number;
    date: string;
    file: File;
    previewUrl: string;
    progress: number;
    status: UploadStatus;
    error: string | null;
}

/**
 * Photos waiting to upload, kept outside any page so uploads carry on after
 * the check-in is saved and the app moves to another screen. Files upload
 * one at a time, each in its own request, so a failure on a bad connection
 * affects only that photo and can be retried.
 */
const uploads = reactive<PendingUpload[]>([]);
let nextKey = 1;
let isRunning = false;

/** Explains a photo that can't be added, or returns null when it can. */
export function photoProblem(file: File): string | null {
    if (!ACCEPTED_PHOTO_TYPES.includes(file.type)) {
        return `${file.name} isn't a JPEG, PNG, or WebP photo.`;
    }

    if (file.size > MAX_PHOTO_BYTES) {
        return `${file.name} is over 10 MB.`;
    }

    return null;
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function errorFrom(request: XMLHttpRequest): string {
    if (request.status === 422) {
        try {
            const body = JSON.parse(request.responseText) as { errors?: Record<string, string[]> };
            const message = body.errors?.photo?.[0];

            if (message) {
                return message;
            }
        } catch {
            // Fall through to the general message.
        }
    }

    if (request.status === 429) {
        return 'Too many uploads at once. Try again in a minute.';
    }

    return "Couldn't upload. Check your connection and try again.";
}

function send(upload: PendingUpload): Promise<boolean> {
    return new Promise((resolve) => {
        const request = new XMLHttpRequest();
        const body = new FormData();
        body.append('photo', upload.file);

        request.open('POST', `/days/${upload.date}/photos`);
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('X-XSRF-TOKEN', xsrfToken());

        request.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                upload.progress = Math.round((event.loaded / event.total) * 100);
            }
        };

        request.onload = () => {
            if (request.status === 201) {
                resolve(true);

                return;
            }

            upload.error = errorFrom(request);
            resolve(false);
        };

        request.onerror = () => {
            upload.error = errorFrom(request);
            resolve(false);
        };

        request.send(body);
    });
}

function remove(upload: PendingUpload): void {
    const index = uploads.indexOf(upload);

    if (index !== -1) {
        uploads.splice(index, 1);
    }

    URL.revokeObjectURL(upload.previewUrl);
}

/** Refreshes the entry on screen when it is the day a photo was just added to. */
function refreshIfShowing(date: string, page: ReturnType<typeof usePage>): void {
    const memory = page.props.memory as { date?: string } | null | undefined;

    if (memory?.date === date) {
        router.reload({ only: ['memory'] });
    }
}

async function run(page: ReturnType<typeof usePage>): Promise<void> {
    if (isRunning) {
        return;
    }

    isRunning = true;

    try {
        let upload: PendingUpload | undefined;

        while ((upload = uploads.find((item) => item.status === 'waiting'))) {
            upload.status = 'uploading';
            upload.progress = 0;
            upload.error = null;

            if (await send(upload)) {
                remove(upload);
                refreshIfShowing(upload.date, page);
            } else {
                upload.status = 'failed';
            }
        }
    } finally {
        isRunning = false;
    }
}

function warnBeforeLeaving(event: BeforeUnloadEvent): void {
    if (uploads.some((upload) => upload.status !== 'failed')) {
        event.preventDefault();
    }
}

if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', warnBeforeLeaving);
}

export function usePhotoUploads(): {
    uploads: PendingUpload[];
    pendingFor: (date: string) => ComputedRef<PendingUpload[]>;
    enqueue: (date: string, files: File[]) => void;
    retry: (upload: PendingUpload) => void;
    discard: (upload: PendingUpload) => void;
} {
    const page = usePage();

    return {
        uploads,
        pendingFor: (date) => computed(() => uploads.filter((upload) => upload.date === date)),
        enqueue(date, files) {
            for (const file of files) {
                uploads.push({
                    key: nextKey++,
                    date,
                    file,
                    previewUrl: URL.createObjectURL(file),
                    progress: 0,
                    status: 'waiting',
                    error: null,
                });
            }

            void run(page);
        },
        retry(upload) {
            upload.status = 'waiting';
            upload.error = null;
            void run(page);
        },
        discard(upload) {
            if (upload.status !== 'uploading') {
                remove(upload);
            }
        },
    };
}
