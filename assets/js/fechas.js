/**
 * Campos de fecha nativos (<input type="date"> y similares): al hacer clic en
 * cualquier parte del campo se abre el calendario, no solo en el iconito.
 */
document.addEventListener('click', function (e) {
    var el = e.target;
    if (!el || el.tagName !== 'INPUT' || el.disabled || el.readOnly) return;
    if (!/^(date|datetime-local|month|week)$/.test(el.type)) return;
    if (typeof el.showPicker === 'function') {
        try { el.showPicker(); } catch (err) { /* el navegador no lo permite: queda el iconito */ }
    }
});
