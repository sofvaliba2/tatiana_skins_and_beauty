/* Tatiana Skin and Beauty — interacciones del sitio */
(function () {
    'use strict';

    /* Confirmación de acciones delicadas (data-confirm) */
    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!form || !form.getAttribute) return;
        var msg = form.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) {
            ev.preventDefault();
        }
    });

    /* Ocultar mensajes flash a los 6 segundos */
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

    /* Botones + / − para cantidades */
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
