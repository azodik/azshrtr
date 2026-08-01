document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-nav-toggle]');
    const panel = document.querySelector('[data-nav-panel]');

    if (toggle && panel) {
        toggle.addEventListener('click', () => {
            const open = panel.getAttribute('data-open') === 'true';
            panel.setAttribute('data-open', open ? 'false' : 'true');
            toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
            panel.classList.toggle('hidden', open);
        });
    }

    initHeroShortener();
});

/**
 * @typedef {{
 *   short_url: string,
 *   destination_url: string,
 *   expires_at: string|null,
 *   claim_url: string,
 *   qr_svg: string,
 * }} ShortenPayload
 */

function initHeroShortener() {
    const form = document.querySelector('[data-shorten-form]');
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const result = document.querySelector('[data-shorten-result]');
    const errorEl = document.querySelector('[data-shorten-error]');
    const submitBtn = form.querySelector('[data-shorten-submit]');
    const qrEl = document.querySelector('[data-shorten-qr]');
    const urlEl = document.querySelector('[data-shorten-url]');
    const claimEl = document.querySelector('[data-shorten-claim]');
    const copyBtn = document.querySelector('[data-shorten-copy]');
    const countdownEl = document.querySelector('[data-shorten-countdown]');
    const idleLabel =
        submitBtn instanceof HTMLButtonElement ? submitBtn.textContent || 'Shorten' : 'Shorten';

    /** @type {number|null} */
    let countdownTimer = null;

    function showError(message) {
        if (!(errorEl instanceof HTMLElement)) {
            return;
        }
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');
    }

    function clearError() {
        if (!(errorEl instanceof HTMLElement)) {
            return;
        }
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
    }

    function stopCountdown() {
        if (countdownTimer !== null) {
            window.clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    /**
     * @param {string|null|undefined} expiresAt
     */
    function startCountdown(expiresAt) {
        stopCountdown();
        if (!(countdownEl instanceof HTMLElement) || !expiresAt) {
            if (countdownEl instanceof HTMLElement) {
                countdownEl.textContent = '';
            }
            return;
        }

        const expiresMs = Date.parse(expiresAt);

        const tick = () => {
            const ms = expiresMs - Date.now();
            if (Number.isNaN(expiresMs) || ms <= 0) {
                countdownEl.textContent = 'Expired — claim window closed.';
                stopCountdown();
                return;
            }
            const m = Math.floor(ms / 60000);
            const s = Math.floor((ms % 60000) / 1000);
            countdownEl.textContent =
                'Expires in ' +
                String(m).padStart(2, '0') +
                ':' +
                String(s).padStart(2, '0') +
                ' unless claimed.';
        };

        tick();
        countdownTimer = window.setInterval(tick, 1000);
    }

    /**
     * @param {ShortenPayload} payload
     */
    function renderResult(payload) {
        if (
            !(result instanceof HTMLElement) ||
            !(qrEl instanceof HTMLElement) ||
            !(urlEl instanceof HTMLAnchorElement) ||
            !(claimEl instanceof HTMLAnchorElement) ||
            !(copyBtn instanceof HTMLButtonElement)
        ) {
            return;
        }

        qrEl.innerHTML = payload.qr_svg;
        urlEl.href = payload.short_url;
        urlEl.textContent = payload.short_url;
        claimEl.href = payload.claim_url;
        copyBtn.dataset.copy = payload.short_url;
        copyBtn.textContent = 'Copy';
        result.dataset.expiresAt = payload.expires_at ?? '';
        result.classList.remove('hidden');
        result.hidden = false;

        startCountdown(payload.expires_at);

        if (result.getBoundingClientRect().bottom > window.innerHeight) {
            result.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    }

    if (copyBtn instanceof HTMLButtonElement) {
        copyBtn.addEventListener('click', async () => {
            const text = copyBtn.dataset.copy || '';
            if (!text) {
                return;
            }
            try {
                await navigator.clipboard.writeText(text);
                copyBtn.textContent = 'Copied';
                window.setTimeout(() => {
                    copyBtn.textContent = 'Copy';
                }, 1500);
            } catch {
                // Clipboard may be denied; leave label unchanged.
            }
        });
    }

    // Session-rendered result (classic POST fallback / refresh).
    if (
        result instanceof HTMLElement &&
        !result.classList.contains('hidden') &&
        result.dataset.expiresAt
    ) {
        startCountdown(result.dataset.expiresAt);
        if (result.getBoundingClientRect().bottom > window.innerHeight) {
            result.scrollIntoView({ block: 'center' });
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();

        if (submitBtn instanceof HTMLButtonElement) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Shortening…';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
                credentials: 'same-origin',
            });

            /** @type {{ shorten?: ShortenPayload, message?: string, errors?: Record<string, string[]> }} */
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message =
                    data.errors?.url?.[0] ||
                    data.message ||
                    'Could not shorten that URL. Try again.';
                showError(message);
                return;
            }

            if (!data.shorten) {
                showError('Unexpected response from the server.');
                return;
            }

            renderResult(data.shorten);
        } catch {
            showError('Network error — check your connection and try again.');
        } finally {
            if (submitBtn instanceof HTMLButtonElement) {
                submitBtn.disabled = false;
                submitBtn.textContent = idleLabel;
            }
        }
    });
}
