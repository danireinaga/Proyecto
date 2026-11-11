@php
    $feedbackMessages = [];
    $feedbackTypes = [
        'success' => ['title' => '¡Listo!', 'icon' => '✓'],
        'error' => ['title' => 'No se pudo completar', 'icon' => '×'],
        'warning' => ['title' => 'Atención', 'icon' => '!'],
        'info' => ['title' => 'Información', 'icon' => 'i'],
    ];

    foreach ($feedbackTypes as $type => $presentation) {
        $message = session($type);
        if (is_string($message) && trim($message) !== '') {
            $feedbackMessages[] = ['type' => $type, 'message' => $message] + $presentation;
        }
    }

    $status = session('status');
    if (is_string($status) && trim($status) !== '') {
        $statusMessages = [
            'profile-updated' => 'Tu perfil fue actualizado correctamente.',
            'password-updated' => 'Tu contraseña fue actualizada correctamente.',
            'verification-link-sent' => 'Se envió un nuevo enlace de verificación a tu correo.',
        ];
        $feedbackMessages[] = [
            'type' => 'success',
            'title' => '¡Listo!',
            'icon' => '✓',
            'message' => $statusMessages[$status] ?? $status,
        ];
    }
@endphp

<div class="app-feedback-toasts" aria-live="polite" aria-atomic="false">
    @foreach ($feedbackMessages as $feedback)
        <section class="app-feedback-toast is-{{ $feedback['type'] }}"
            role="{{ $feedback['type'] === 'error' ? 'alert' : 'status' }}" data-feedback-toast>
            <span class="app-feedback-icon" aria-hidden="true">{{ $feedback['icon'] }}</span>
            <div class="app-feedback-toast-copy">
                <strong>{{ $feedback['title'] }}</strong>
                <p>{{ $feedback['message'] }}</p>
            </div>
            <button type="button" class="app-feedback-toast-close" aria-label="Cerrar aviso">&times;</button>
        </section>
    @endforeach
</div>

<div class="app-confirm-backdrop" id="app-confirm-backdrop" hidden>
    <section class="app-confirm-dialog" role="dialog" aria-modal="true"
        aria-labelledby="app-confirm-title" aria-describedby="app-confirm-message" tabindex="-1">
        <div class="app-confirm-icon" aria-hidden="true">?</div>
        <h2 id="app-confirm-title">Confirmar acción</h2>
        <p id="app-confirm-message"></p>
        <div class="app-confirm-actions">
            <button type="button" class="app-confirm-cancel">Volver</button>
            <button type="button" class="app-confirm-accept">Continuar</button>
        </div>
    </section>
</div>

<style>
    .app-feedback-toasts {
        position: fixed;
        z-index: 20000;
        top: 1rem;
        right: 1rem;
        display: grid;
        gap: .75rem;
        width: min(26rem, calc(100vw - 2rem));
        pointer-events: none;
    }

    .app-feedback-toast {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        padding: 1rem;
        border: 1px solid #e5e7eb;
        border-left: 4px solid #198754;
        border-radius: .85rem;
        background: #fff;
        box-shadow: 0 12px 35px rgba(31, 41, 55, .18);
        color: #273142;
        pointer-events: auto;
        animation: app-feedback-enter .22s ease-out;
    }

    .app-feedback-icon {
        display: grid;
        flex: 0 0 1.5rem;
        width: 1.5rem;
        height: 1.5rem;
        margin-top: .1rem;
        place-items: center;
        border-radius: 50%;
        background: #e8f5ed;
        color: #198754;
        font-size: 1rem;
        font-weight: 700;
    }

    .app-feedback-toast.is-error { border-left-color: #dc3545; }
    .app-feedback-toast.is-error .app-feedback-icon { background: #fdebed; color: #dc3545; }
    .app-feedback-toast.is-warning { border-left-color: #d99700; }
    .app-feedback-toast.is-warning .app-feedback-icon { background: #fff4d6; color: #a56c00; }
    .app-feedback-toast.is-info { border-left-color: #0d6efd; }
    .app-feedback-toast.is-info .app-feedback-icon { background: #eaf2ff; color: #0d6efd; }

    .app-feedback-toast-copy { flex: 1; min-width: 0; }
    .app-feedback-toast-copy strong { display: block; margin-bottom: .15rem; }
    .app-feedback-toast-copy p { margin: 0; color: #5b6472; overflow-wrap: anywhere; }
    .app-feedback-toast-close {
        border: 0;
        padding: 0 .1rem;
        background: transparent;
        color: #687386;
        font-size: 1.35rem;
        line-height: 1;
        cursor: pointer;
    }

    .app-confirm-backdrop {
        position: fixed;
        z-index: 20010;
        inset: 0;
        display: grid;
        place-items: center;
        padding: 1rem;
        background: rgba(15, 23, 42, .55);
        backdrop-filter: blur(3px);
    }

    .app-confirm-backdrop[hidden] { display: none; }
    .app-confirm-dialog {
        width: min(28rem, 100%);
        padding: 1.75rem;
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .3);
        text-align: center;
        animation: app-feedback-enter .18s ease-out;
    }

    .app-confirm-icon {
        display: grid;
        width: 3.25rem;
        height: 3.25rem;
        margin: 0 auto 1rem;
        place-items: center;
        border-radius: 50%;
        background: #fff4d6;
        color: #b77900;
        font-size: 1.3rem;
    }

    .app-confirm-dialog h2 { margin: 0 0 .5rem; color: #202938; font-size: 1.25rem; }
    .app-confirm-dialog p { margin: 0; color: #5b6472; line-height: 1.55; }
    .app-confirm-actions { display: flex; justify-content: center; gap: .75rem; margin-top: 1.5rem; }
    .app-confirm-actions button {
        min-width: 7rem;
        padding: .6rem 1rem;
        border: 0;
        border-radius: .6rem;
        font: inherit;
        font-weight: 600;
        cursor: pointer;
    }

    .app-confirm-cancel { background: #eef1f5; color: #354052; }
    .app-confirm-accept { background: #3b1c4a; color: #fff; }
    .app-confirm-accept.is-destructive { background: #dc3545; }
    .app-confirm-actions button:focus-visible,
    .app-feedback-toast-close:focus-visible { outline: 3px solid #8a63a0; outline-offset: 2px; }

    @keyframes app-feedback-enter {
        from { opacity: 0; transform: translateY(-8px) scale(.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @media (prefers-reduced-motion: reduce) {
        .app-feedback-toast, .app-confirm-dialog { animation: none; }
    }
</style>

<script>
    (() => {
        const backdrop = document.getElementById('app-confirm-backdrop');
        const title = document.getElementById('app-confirm-title');
        const message = document.getElementById('app-confirm-message');
        const accept = backdrop.querySelector('.app-confirm-accept');
        const cancel = backdrop.querySelector('.app-confirm-cancel');
        let pendingConfirmation = null;
        let previousFocus = null;
        let previousOverflow = '';

        window.appToast = function (options) {
            const type = ['success', 'error', 'warning', 'info'].includes(options.type) ? options.type : 'info';
            const presentations = {
                success: { title: '¡Listo!', icon: '✓' },
                error: { title: 'No se pudo completar', icon: '×' },
                warning: { title: 'Atención', icon: '!' },
                info: { title: 'Información', icon: 'i' },
            };
            const presentation = presentations[type];
            const toast = document.createElement('section');
            toast.className = 'app-feedback-toast is-' + type;
            toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
            toast.dataset.feedbackToast = '';

            const icon = document.createElement('span');
            icon.className = 'app-feedback-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = presentation.icon;

            const copy = document.createElement('div');
            copy.className = 'app-feedback-toast-copy';
            const heading = document.createElement('strong');
            heading.textContent = options.title || presentation.title;
            const text = document.createElement('p');
            text.textContent = options.message || '';
            copy.append(heading, text);

            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'app-feedback-toast-close';
            close.setAttribute('aria-label', 'Cerrar aviso');
            close.textContent = '×';
            toast.append(icon, copy, close);
            document.querySelector('.app-feedback-toasts').appendChild(toast);
            bindToast(toast);
        };

        function bindToast(toast) {
            const removeToast = () => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-8px)';
                window.setTimeout(() => toast.remove(), 180);
            };
            toast.querySelector('.app-feedback-toast-close').addEventListener('click', removeToast);
            window.setTimeout(removeToast, 6500);
        }

        function closeConfirmation(confirmed) {
            if (!pendingConfirmation) return;
            const resolve = pendingConfirmation;
            pendingConfirmation = null;
            backdrop.hidden = true;
            document.body.style.overflow = previousOverflow;
            resolve(confirmed);
            if (previousFocus && previousFocus.isConnected) previousFocus.focus();
        }

        window.appConfirm = function (options) {
            if (pendingConfirmation) closeConfirmation(false);
            previousFocus = document.activeElement;
            previousOverflow = document.body.style.overflow;
            title.textContent = options.title || 'Confirmar acción';
            message.textContent = options.message || '¿Deseas continuar?';
            accept.textContent = options.confirmText || 'Continuar';
            accept.classList.toggle('is-destructive', options.destructive === true);
            backdrop.hidden = false;
            document.body.style.overflow = 'hidden';
            return new Promise((resolve) => {
                pendingConfirmation = resolve;
                accept.focus();
            });
        };

        accept.addEventListener('click', () => closeConfirmation(true));
        cancel.addEventListener('click', () => closeConfirmation(false));
        backdrop.addEventListener('click', (event) => {
            if (event.target === backdrop) closeConfirmation(false);
        });

        document.addEventListener('keydown', (event) => {
            if (backdrop.hidden) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                closeConfirmation(false);
            } else if (event.key === 'Tab') {
                event.preventDefault();
                (document.activeElement === accept ? cancel : accept).focus();
            }
        });

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.dataset.confirmMessage ||
                form.dataset.confirmed === 'true') return;

            event.preventDefault();
            event.stopImmediatePropagation();
            const submitter = event.submitter;
            window.appConfirm({
                title: form.dataset.confirmTitle,
                message: form.dataset.confirmMessage,
                confirmText: form.dataset.confirmAccept,
                destructive: form.dataset.confirmDestructive === 'true',
            }).then((confirmed) => {
                if (!confirmed) return;
                form.dataset.confirmed = 'true';
                if (typeof form.requestSubmit === 'function') {
                    if (submitter) form.requestSubmit(submitter);
                    else form.requestSubmit();
                } else {
                    HTMLFormElement.prototype.submit.call(form);
                }
            });
        }, true);

        document.addEventListener('invalid', (event) => {
            const form = event.target.form;
            if (form && form.dataset.confirmed === 'true') {
                delete form.dataset.confirmed;
            }
        }, true);

        document.querySelectorAll('[data-feedback-toast]').forEach(bindToast);
    })();
</script>
