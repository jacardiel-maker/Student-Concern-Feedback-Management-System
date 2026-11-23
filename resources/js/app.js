const dismissToast = (toast) => {
    if (!toast || toast.dataset.dismissing === 'true') {
        return;
    }

    toast.dataset.dismissing = 'true';
    toast.classList.add('toast-exit');
    window.setTimeout(() => toast.remove(), 220);
};

const initToasts = () => {
    document.querySelectorAll('[data-toast]').forEach((toast) => {
        const timer = window.setTimeout(() => dismissToast(toast), 4000);
        toast.querySelector('[data-toast-dismiss]')?.addEventListener('click', () => {
            window.clearTimeout(timer);
            dismissToast(toast);
        });
    });
};

const initSubmitLoading = () => {
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const button = event.submitter instanceof HTMLElement
                ? event.submitter
                : form.querySelector('button[type="submit"], input[type="submit"]');

            if (!button || button.hasAttribute('data-no-loading')) {
                return;
            }

            button.classList.add('btn-submitting');
            button.setAttribute('aria-busy', 'true');
            button.setAttribute('disabled', 'disabled');
        });
    });
};

const initAnonymousHint = () => {
    const toggle = document.querySelector('[data-anonymous-toggle]');
    const hint = document.querySelector('[data-anonymous-hint]');

    if (!toggle || !hint) {
        return;
    }

    let hasReacted = false;

    toggle.addEventListener('change', () => {
        if (!toggle.checked || hasReacted) {
            return;
        }

        hasReacted = true;
        hint.classList.add('hint-highlight', 'hint-shake');
    });
};

const initWorkflowStepper = () => {
    const stepper = document.querySelector('[data-workflow-stepper][data-advancing="true"]');
    const fill = stepper?.querySelector('.workflow-fill');

    if (!stepper || !fill) {
        return;
    }

    const target = `${stepper.dataset.targetProgress}%`;

    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
            fill.style.width = target;
        });
    });
};

const initTicketSkeleton = () => {
    const wrapper = document.querySelector('[data-ticket-list-wrap]');

    if (!wrapper || !wrapper.querySelector('[data-ticket-skeleton]') || !wrapper.querySelector('[data-ticket-list]')) {
        return;
    }

    window.requestAnimationFrame(() => wrapper.classList.add('skeleton-ready'));
};

const initCharacterCounter = () => {
    const input = document.querySelector('[data-character-counter-input]');
    const output = document.querySelector('[data-character-counter-output]');

    if (!input || !output) {
        return;
    }

    const maximum = Number(input.getAttribute('maxlength') || 5000);
    const label = output.dataset.counterLabel || 'characters';
    const formatter = new Intl.NumberFormat();

    const update = () => {
        const length = Array.from(input.value).length;
        output.textContent = `${formatter.format(length)} / ${formatter.format(maximum)} ${label}`;
    };

    input.addEventListener('input', update);
    update();
};

const initSimilarConcerns = () => {
    const subject = document.getElementById('subject');
    const panel = document.querySelector('[data-similar-concerns]');
    const list = panel?.querySelector('[data-similar-concerns-list]');

    if (!subject || !panel || !list || !panel.dataset.url) {
        return;
    }

    let debounceTimer;
    let activeRequest;

    const render = (suggestions) => {
        list.replaceChildren();

        if (!Array.isArray(suggestions) || suggestions.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'text-xs text-slate-500';
            empty.textContent = panel.dataset.noSuggestions || 'No similar concerns yet.';
            list.append(empty);

            return;
        }

        suggestions.forEach((suggestion) => {
            const item = document.createElement('li');
            const link = document.createElement('a');
            const status = document.createElement('span');

            link.href = suggestion.url;
            link.className = 'font-medium text-spruce-700 hover:underline';
            link.textContent = `${suggestion.ticket_no} — ${suggestion.subject}`;

            status.className = 'text-xs text-slate-500';
            status.textContent = suggestion.status;

            item.className = 'flex flex-wrap items-baseline justify-between gap-2';
            item.append(link, status);
            list.append(item);
        });
    };

    subject.addEventListener('input', () => {
        const term = subject.value.trim();

        window.clearTimeout(debounceTimer);
        activeRequest?.abort();

        if (term.length < 3) {
            panel.classList.add('hidden');

            return;
        }

        debounceTimer = window.setTimeout(async () => {
            activeRequest = new AbortController();

            try {
                const response = await fetch(`${panel.dataset.url}?subject=${encodeURIComponent(term)}`, {
                    headers: { Accept: 'application/json' },
                    signal: activeRequest.signal,
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                render(payload.suggestions);
                panel.classList.remove('hidden');
            } catch (error) {
                if (error.name !== 'AbortError') {
                    panel.classList.add('hidden');
                }
            }
        }, 300);
    });
};

const initConcernTemplates = () => {
    const subject = document.getElementById('subject');
    const description = document.getElementById('description');

    if (!subject || !description) {
        return;
    }

    document.querySelectorAll('[data-concern-template]').forEach((button) => {
        button.addEventListener('click', () => {
            subject.value = button.dataset.templateSubject || '';
            description.value = button.dataset.templateDescription || '';
            subject.dispatchEvent(new Event('input', { bubbles: true }));
            description.dispatchEvent(new Event('input', { bubbles: true }));
            description.focus();
        });
    });
};

const initConfirmations = () => {
    const modal = document.querySelector('[data-confirm-modal]');

    if (!modal) {
        return;
    }

    const title = modal.querySelector('[data-confirm-title]');
    const body = modal.querySelector('[data-confirm-body]');
    const accept = modal.querySelector('[data-confirm-accept]');
    const cancel = modal.querySelector('[data-confirm-cancel]');
    let pendingForm = null;
    let previousFocus = null;

    const close = () => {
        modal.classList.replace('flex', 'hidden');
        modal.setAttribute('aria-hidden', 'true');
        pendingForm = null;
        previousFocus?.focus();
    };

    const open = (form) => {
        pendingForm = form;
        previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        title.textContent = form.dataset.confirmTitle || '';
        body.textContent = form.dataset.confirmBody || '';
        modal.classList.replace('hidden', 'flex');
        modal.setAttribute('aria-hidden', 'false');
        accept.focus();
    };

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmBypass === 'true') {
                delete form.dataset.confirmBypass;

                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            open(form);
        }, true);
    });

    accept.addEventListener('click', () => {
        if (!pendingForm) {
            return;
        }

        const form = pendingForm;
        pendingForm = null;
        modal.classList.replace('flex', 'hidden');
        modal.setAttribute('aria-hidden', 'true');
        form.dataset.confirmBypass = 'true';
        form.requestSubmit();
    });

    cancel.addEventListener('click', close);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            close();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            close();
        }
    });
};

const initPasswordStrength = () => {
    const input = document.getElementById('password');
    const wrapper = document.querySelector('[data-password-strength]');

    if (!input || !wrapper) {
        return;
    }

    const label = wrapper.querySelector('[data-password-strength-label]');
    const bars = Array.from(wrapper.querySelectorAll('[data-strength-bar]'));
    const colors = ['#cbd5e1', '#b25a45', '#c8811a', '#8fb7a1', '#14554e'];
    const labels = {
        0: wrapper.dataset.labelEmpty || 'Enter a password',
        1: wrapper.dataset.labelWeak || 'Weak',
        2: wrapper.dataset.labelFair || 'Fair',
        3: wrapper.dataset.labelGood || 'Good',
        4: wrapper.dataset.labelStrong || 'Strong',
    };

    const score = (value) => {
        if (value.length < 8) {
            return 1;
        }

        let points = 1;

        if (value.length >= 12) {
            points++;
        }

        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) {
            points++;
        }

        if (/\d/.test(value)) {
            points++;
        }

        if (/[^A-Za-z0-9]/.test(value)) {
            points++;
        }

        return Math.min(4, points);
    };

    const update = () => {
        const level = input.value.length === 0 ? 0 : score(input.value);
        label.textContent = labels[level];
        label.style.color = colors[level];
        wrapper.setAttribute('data-strength-level', String(level));
        bars.forEach((bar, index) => {
            bar.style.backgroundColor = index < level ? colors[level] : colors[0];
        });
    };

    input.addEventListener('input', update);
    update();
};

const initSessionTimeoutWarning = () => {
    const modal = document.querySelector('[data-session-timeout-modal]');

    if (!modal) {
        return;
    }

    const lifetime = Number(document.querySelector('meta[name="session-lifetime"]')?.content || 0);

    if (!Number.isFinite(lifetime) || lifetime <= 0) {
        return;
    }

    const warningDelay = Math.max(0, (lifetime - 120) * 1000);
    const stayButton = modal.querySelector('[data-session-stay]');
    let warningTimer;

    const show = () => {
        modal.classList.replace('hidden', 'flex');
        modal.setAttribute('aria-hidden', 'false');
        stayButton.focus();
    };

    const hide = () => {
        modal.classList.replace('flex', 'hidden');
        modal.setAttribute('aria-hidden', 'true');
    };

    warningTimer = window.setTimeout(show, warningDelay);

    stayButton.addEventListener('click', async () => {
        stayButton.disabled = true;
        stayButton.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(modal.dataset.keepAliveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': modal.dataset.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.redirected && response.url.includes('/login')) {
                window.location.assign(response.url);

                return;
            }

            if (!response.ok) {
                throw new Error('Session keep-alive failed.');
            }

            window.clearTimeout(warningTimer);
            hide();

            if (warningDelay > 0) {
                warningTimer = window.setTimeout(show, warningDelay);
            }
        } catch (error) {
            stayButton.disabled = false;
            stayButton.removeAttribute('aria-busy');

            return;
        }

        stayButton.disabled = false;
        stayButton.removeAttribute('aria-busy');
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initToasts();
    initConfirmations();
    initSubmitLoading();
    initAnonymousHint();
    initWorkflowStepper();
    initTicketSkeleton();
    initCharacterCounter();
    initSimilarConcerns();
    initConcernTemplates();
    initPasswordStrength();
    initSessionTimeoutWarning();
});
