import { translateApiMessage } from '../utils/translateApiMessage';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export async function api(url, options = {}) {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
        ...(options.headers ?? {}),
    };

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
    });

    const payload = await response.json().catch(() => ({}));

    if (response.status === 401) {
        window.location.assign('/');
        throw new Error('Unauthorized');
    }

    if (!response.ok) {
        const message = translateApiMessage(payload.message ?? payload.error ?? 'Request failed');
        const errors = payload.errors ?? {};
        const error = new Error(message);
        error.status = response.status;
        error.errors = errors;
        throw error;
    }

    return payload;
}
