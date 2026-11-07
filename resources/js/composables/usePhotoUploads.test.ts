import { flushPromises } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const inertia = vi.hoisted(() => ({
    page: { props: { memory: { date: '2026-11-07' } as { date: string } | null } },
    reload: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => inertia.page,
    router: { reload: inertia.reload },
}));

/** A stand-in for XMLHttpRequest that each test answers by hand. */
class FakeRequest {
    static sent: FakeRequest[] = [];

    method = '';
    url = '';
    headers: Record<string, string> = {};
    body: FormData | null = null;
    status = 0;
    responseText = '';
    upload: { onprogress: ((event: ProgressEvent) => void) | null } = { onprogress: null };
    onload: (() => void) | null = null;
    onerror: (() => void) | null = null;

    open(method: string, url: string): void {
        this.method = method;
        this.url = url;
    }

    setRequestHeader(name: string, value: string): void {
        this.headers[name] = value;
    }

    send(body: FormData): void {
        this.body = body;
        FakeRequest.sent.push(this);
    }

    progress(loaded: number, total: number): void {
        this.upload.onprogress?.({ lengthComputable: true, loaded, total } as ProgressEvent);
    }

    respond(status: number, body: unknown = {}): void {
        this.status = status;
        this.responseText = JSON.stringify(body);
        this.onload?.();
    }

    fail(): void {
        this.onerror?.();
    }
}

function photo(name = 'beach.jpg', type = 'image/jpeg', size = 1000): File {
    const file = new File(['x'], name, { type });
    Object.defineProperty(file, 'size', { value: size });

    return file;
}

async function freshQueue() {
    vi.resetModules();
    const module = await import('./usePhotoUploads');

    return { ...module, ...module.usePhotoUploads() };
}

describe('usePhotoUploads', () => {
    beforeEach(() => {
        FakeRequest.sent = [];
        inertia.reload.mockClear();
        inertia.page.props.memory = { date: '2026-11-07' };
        vi.stubGlobal('XMLHttpRequest', FakeRequest);
        URL.createObjectURL = () => 'blob:preview';
        URL.revokeObjectURL = vi.fn();
        document.cookie = 'XSRF-TOKEN=token%3D123';
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('uploads each photo in its own request, one at a time', async () => {
        const queue = await freshQueue();

        queue.enqueue('2026-11-07', [photo('one.jpg'), photo('two.jpg')]);

        expect(FakeRequest.sent).toHaveLength(1);

        const [first] = FakeRequest.sent;
        expect(first.method).toBe('POST');
        expect(first.url).toBe('/days/2026-11-07/photos');
        expect(first.headers['X-XSRF-TOKEN']).toBe('token=123');
        expect((first.body!.get('photo') as File).name).toBe('one.jpg');

        first.respond(201);
        await flushPromises();

        expect(FakeRequest.sent).toHaveLength(2);
        expect((FakeRequest.sent[1].body!.get('photo') as File).name).toBe('two.jpg');
    });

    it('shows progress while uploading', async () => {
        const queue = await freshQueue();

        queue.enqueue('2026-11-07', [photo()]);
        FakeRequest.sent[0].progress(50, 200);

        expect(queue.uploads[0]).toMatchObject({ status: 'uploading', progress: 25 });
    });

    it('drops a finished upload and refreshes the entry on screen', async () => {
        const queue = await freshQueue();

        queue.enqueue('2026-11-07', [photo()]);
        FakeRequest.sent[0].respond(201);
        await flushPromises();

        expect(queue.uploads).toHaveLength(0);
        expect(inertia.reload).toHaveBeenCalledWith({ only: ['memory'] });
    });

    it("doesn't refresh a page showing another day", async () => {
        const queue = await freshQueue();
        inertia.page.props.memory = { date: '2026-11-01' };

        queue.enqueue('2026-11-07', [photo()]);
        FakeRequest.sent[0].respond(201);
        await flushPromises();

        expect(inertia.reload).not.toHaveBeenCalled();
    });

    it('keeps a failed upload with the reason, and moves on to the next', async () => {
        const queue = await freshQueue();

        queue.enqueue('2026-11-07', [photo('one.jpg'), photo('two.jpg')]);
        FakeRequest.sent[0].respond(422, { errors: { photo: ['A day can have up to 4 photos.'] } });
        await flushPromises();

        expect(queue.uploads[0]).toMatchObject({
            status: 'failed',
            error: 'A day can have up to 4 photos.',
        });
        expect(FakeRequest.sent).toHaveLength(2);
    });

    it('retries a photo that failed on a bad connection', async () => {
        const queue = await freshQueue();

        queue.enqueue('2026-11-07', [photo()]);
        FakeRequest.sent[0].fail();
        await flushPromises();

        expect(queue.uploads[0].error).toBe(
            "Couldn't upload. Check your connection and try again.",
        );

        queue.retry(queue.uploads[0]);
        FakeRequest.sent[1].respond(201);
        await flushPromises();

        expect(queue.uploads).toHaveLength(0);
    });

    it('lists only the uploads for a given day', async () => {
        const queue = await freshQueue();

        queue.enqueue('2026-11-07', [photo()]);
        queue.enqueue('2026-11-01', [photo(), photo()]);

        expect(queue.pendingFor('2026-11-01').value).toHaveLength(2);
    });

    it('explains photos that can’t be added', async () => {
        const { photoProblem } = await freshQueue();

        expect(photoProblem(photo('ok.webp', 'image/webp'))).toBeNull();
        expect(photoProblem(photo('anim.gif', 'image/gif'))).toBe(
            "anim.gif isn't a JPEG, PNG, or WebP photo.",
        );
        expect(photoProblem(photo('big.jpg', 'image/jpeg', 11 * 1024 * 1024))).toBe(
            'big.jpg is over 10 MB.',
        );
    });
});
