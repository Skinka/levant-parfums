// Scroll-reveal: adds `.in` class to .reveal / .reveal-stagger elements once they
// enter the viewport. CSS handles the animation; this only flips the class.
//
// Uses IntersectionObserver — cheaper than the design's rAF poll loop. On the
// off chance a browser lacks IO support, every element is revealed immediately
// so nothing stays invisible.

const SELECTOR = '.reveal:not(.in), .reveal-stagger:not(.in)';

function revealAllNow(root = document) {
    root.querySelectorAll(SELECTOR).forEach((el) => el.classList.add('in'));
}

function observe(root = document) {
    if (typeof IntersectionObserver === 'undefined') {
        revealAllNow(root);
        return;
    }

    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    io.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.01 }
    );

    root.querySelectorAll(SELECTOR).forEach((el) => io.observe(el));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => observe());
} else {
    observe();
}
