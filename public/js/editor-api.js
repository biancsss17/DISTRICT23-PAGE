const editorData = JSON.parse(document.querySelector('#editor-data').textContent);
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

export const uploadLimits = editorData.uploadLimits;

export async function request(path, method = 'POST', data) {
    const formData = data instanceof FormData;
    const response = await fetch('/admin/editor/' + path, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            ...(!formData && data ? { 'Content-Type': 'application/json' } : {}),
        },
        body: formData ? data : data ? JSON.stringify(data) : undefined,
    });

    let result;
    try {
        result = await response.json();
    } catch {
        throw new Error('Session expired or server unavailable. Reload and sign in again.');
    }

    if (!response.ok) {
        throw new Error(
            result.errors
                ? Object.values(result.errors).flat().join(' ')
                : result.message || 'Request failed.',
        );
    }

    return result;
}

export function validateUpload(file) {
    const extension = file.name.split('.').pop().toLowerCase();
    const video =
        file.type.startsWith('video/') ||
        (!file.type.startsWith('image/') && uploadLimits.videoExtensions.includes(extension));
    const limit = video ? uploadLimits.videoMb : uploadLimits.photoMb;

    if (file.size > limit * 1024 * 1024) {
        throw new Error(`${video ? 'Videos' : 'Photos'} must be ${limit} MB or smaller.`);
    }
}
