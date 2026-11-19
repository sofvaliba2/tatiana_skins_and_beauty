(function () {
    'use strict';

    /* Confirmación antes de acciones destructivas (data-confirm). */
    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!form || !form.getAttribute) return;
        var msg = form.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) {
            ev.preventDefault();
            return;
        }

        /* Evitar doble envío al presionar varias veces "Guardar". */
        if (form.hasAttribute('data-guardar')) {
            if (form.dataset.enviando === '1') {
                ev.preventDefault();
                return;
            }
            form.dataset.enviando = '1';
            form.classList.add('enviando');
            window.setTimeout(function () {
                form.dataset.enviando = '0';
                form.classList.remove('enviando');
            }, 4000);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var alertas = document.querySelectorAll('.alert');
        window.setTimeout(function () {
            alertas.forEach(function (a) {
                a.style.transition = 'opacity .5s';
                a.style.opacity = '0';
                window.setTimeout(function () { a.remove(); }, 500);
            });
        }, 6000);
    });

    /* Ojo mostrar/ocultar contraseña: cambia el tipo del input y el icono. */
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('[data-password-toggle]') : null;
        if (!btn) return;
        ev.preventDefault();
        var field = btn.closest('.password-field');
        var input = field ? field.querySelector('.password-input') : null;
        if (!input) return;
        var visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        field.classList.toggle('is-visible', !visible);
        btn.setAttribute('aria-pressed', visible ? 'false' : 'true');
        btn.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        input.focus();
    });

    /* Aviso de cambios sin guardar en formularios marcados con data-guardar. */
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-guardar]').forEach(function (form) {
            var sucio = false;
            form.addEventListener('input', function () { sucio = true; });
            form.addEventListener('change', function () { sucio = true; });
            form.addEventListener('submit', function () { sucio = false; });
            window.addEventListener('beforeunload', function (ev) {
                if (!sucio) return;
                ev.preventDefault();
                ev.returnValue = '';
            });
            document.addEventListener('click', function (ev) {
                var cancelar = ev.target.closest ? ev.target.closest('[data-cancelar]') : null;
                if (cancelar) sucio = false;
            });
        });
    });

    /* Menú lateral en móvil: abrir / cerrar sin recargar. */
    document.addEventListener('click', function (ev) {
        var abrir = ev.target.closest ? ev.target.closest('[data-sidebar-open]') : null;
        var cerrar = ev.target.closest ? ev.target.closest('[data-sidebar-close]') : null;
        var overlay = ev.target.closest ? ev.target.closest('[data-sidebar-overlay]') : null;
        if (!abrir && !cerrar && !overlay) return;
        var debeAbrir = !!abrir;
        document.body.classList.toggle('sidebar-abierta', debeAbrir);
    });

    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('[data-step]') : null;
        if (!btn) return;
        var input = document.querySelector(btn.getAttribute('data-step'));
        if (!input) return;
        var paso = parseInt(btn.getAttribute('data-step-value') || '1', 10);
        var min = parseInt(input.min || '1', 10);
        var max = input.max ? parseInt(input.max, 10) : 999;
        var val = parseInt(input.value || '1', 10) + paso;
        input.value = Math.max(min, Math.min(max, isNaN(val) ? min : val));
    });
})();
