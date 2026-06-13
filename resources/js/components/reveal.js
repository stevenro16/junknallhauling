// ---------------------------------------------------------------------------
// Scroll reveal — fades/slides elements into view as they enter the viewport.
//
// Tag any element with `data-reveal` (optionally `="up|down|left|right|scale"`)
// and it starts hidden, then eases in once scrolled into view. Add
// `data-reveal-delay="120"` (milliseconds) to stagger grouped items.
//
// Safeguards:
//   - prefers-reduced-motion: everything is shown immediately, no animation.
//   - No IntersectionObserver support: everything is shown immediately.
//   - Each element animates once, then is unobserved.
// ---------------------------------------------------------------------------

function initReveal() {
    const els = document.querySelectorAll('[data-reveal]');
    if (!els.length) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduceMotion || !('IntersectionObserver' in window)) {
        els.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const io = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                const delay = el.dataset.revealDelay;
                if (delay) el.style.transitionDelay = `${parseInt(delay, 10)}ms`;
                el.classList.add('is-visible');
                observer.unobserve(el);
            });
        },
        {
            threshold: 0.12,
            // start a touch before fully in view so it feels responsive
            rootMargin: '0px 0px -8% 0px',
        }
    );

    els.forEach((el) => io.observe(el));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReveal);
} else {
    initReveal();
}
