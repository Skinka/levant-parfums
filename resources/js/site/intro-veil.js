// Intro veil — shows the LEVANT wordmark behind a curtain on the first page
// load of a session, then removes the node when the CSS animation finishes.
//
// The veil is always rendered server-side; this script removes it immediately
// when sessionStorage says it was already shown, or after the animation ends
// otherwise.

const STORAGE_KEY = 'levant_intro_shown';
const REMOVE_AFTER_MS = 1500;

function init() {
    const veil = document.querySelector('.intro-veil');
    if (!veil) return;

    if (sessionStorage.getItem(STORAGE_KEY)) {
        veil.remove();
        return;
    }

    sessionStorage.setItem(STORAGE_KEY, '1');
    window.setTimeout(() => veil.remove(), REMOVE_AFTER_MS);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
