document.addEventListener('click', (event) => {
    const logoutItem = event.target instanceof Element ? event.target.closest('#sidebar-logout') : null;
    const logoutLink = logoutItem?.querySelector('a');
    if (!logoutLink) return;

    event.preventDefault();

    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) {
        console.error('No se pudo cerrar la sesión: falta el token de seguridad.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = logoutLink.href;
    form.hidden = true;

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = token;
    form.append(csrf);

    document.body.append(form);
    form.submit();
});
