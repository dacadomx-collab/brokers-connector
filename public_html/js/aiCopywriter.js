/**
 * aiCopywriter.js — Generador de copywriting inmobiliario con IA.
 * Endpoint: POST /home/ai/generate-copy (web, CSRF).
 * Convención: camelCase (Mandamiento #7 Frontend).
 */
(function () {
    'use strict';

    /* ── Helpers ─────────────────────────────────────────────────── */

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function getInputValue(name) {
        var el = document.querySelector('[name="' + name + '"]');
        return el ? el.value.trim() : '';
    }

    function getSelectText(name) {
        var el = document.querySelector('select[name="' + name + '"]');
        if (!el || el.selectedIndex < 0) return '';
        var text = el.options[el.selectedIndex].text.trim();
        // Ignorar el placeholder (primera opción vacía)
        return (el.options[el.selectedIndex].value === '') ? '' : text;
    }

    /* ── Recolección de datos del formulario ─────────────────────── */

    function collectPropertyData() {
        return {
            title:       getInputValue('title'),
            prop_type:   getSelectText('prop_type_id'),
            prop_status: getSelectText('prop_status_id'),
            bedrooms:    getInputValue('bedrooms'),
            baths:       getInputValue('baths'),
            price:       getInputValue('price'),       // campo hidden con valor numérico
            currency:    getSelectText('currency'),
        };
    }

    /* ── Handler del botón ───────────────────────────────────────── */

    function handleCopyGeneration() {
        var btn         = document.getElementById('btn-ai-copy');
        var descTextarea = document.getElementById('description');

        if (!btn || !descTextarea) return;

        var originalHTML = btn.innerHTML;
        btn.innerHTML    = '⏳ Generando magia...';
        btn.disabled     = true;

        var payload = collectPropertyData();

        fetch('/home/ai/generate-copy', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(payload),
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data.copy) {
                descTextarea.value = data.copy;
                // Dispara el evento change para que otros listeners (validaciones) lo detecten
                descTextarea.dispatchEvent(new Event('change', { bubbles: true }));
            }
        })
        .catch(function () {
            // Error de red — el textarea no se modifica; el botón se restaura
        })
        .finally(function () {
            btn.innerHTML = originalHTML;
            btn.disabled  = false;
        });
    }

    /* ── Init ────────────────────────────────────────────────────── */

    function init() {
        var btn = document.getElementById('btn-ai-copy');
        if (!btn) return;
        btn.addEventListener('click', handleCopyGeneration);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

}());
