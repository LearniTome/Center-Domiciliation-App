document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
        const message = element.getAttribute('data-confirm') || 'Confirmer cette action ?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});

