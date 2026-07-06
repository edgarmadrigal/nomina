/**
 * Responsive Menu Handler
 * Mejora el responsive design del menú
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== Limpiar estilos inline del menú =====
    const menuItems = document.querySelectorAll('.sidebar-nav ul li');
    menuItems.forEach(item => {
        // Remover estilos inline innecesarios
        const style = item.getAttribute('style');
        if (style && style === 'float: left;width: 100%;') {
            item.removeAttribute('style');
        }
    });

    // ===== Menu siempre expandido en mobile / restaurar estado =====
    const sidebar = document.querySelector('.left-sidebar');
    const menuToggle = document.getElementById('menu-toggle');

    function setMobileHidden(hidden) {
        if (!sidebar) return;
        if (hidden) {
            sidebar.classList.add('mobile-hidden');
            localStorage.setItem('nominaMobileMenuHidden', 'true');
        } else {
            sidebar.classList.remove('mobile-hidden');
            localStorage.removeItem('nominaMobileMenuHidden');
        }
    }

    if (sidebar && window.innerWidth <= 767) {
        if (localStorage.getItem('nominaMobileMenuHidden') === 'true') {
            setMobileHidden(true);
        } else {
            setMobileHidden(false);
        }
    }

    // ===== Menu Toggle para Desktop y Mobile =====
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.innerWidth > 767) {
                sidebar.classList.toggle('expanded');
            } else {
                setMobileHidden(!sidebar.classList.contains('mobile-hidden'));
            }
        });
    }

    // ===== Submenu Toggle =====
    const hasArrowItems = document.querySelectorAll('.has-arrow');
    hasArrowItems.forEach(item => {
        const link = item.querySelector('a');
        if (link && window.innerWidth <= 767) {
            link.addEventListener('click', function(e) {
                // No prevenir por defecto, dejar que funcione normalmente
                // Los submenús están siempre visibles en mobile
            });
        }
    });

    // ===== Cerrar menú al hacer click fuera (solo desktop) =====
    document.addEventListener('click', function(e) {
        if (window.innerWidth > 767) {
            if (sidebar && !sidebar.contains(e.target) && !menuToggle?.contains(e.target)) {
                sidebar.classList.remove('expanded');
            }
        }
    });

    // ===== Ocultar menú al seleccionar un item en mobile =====
    document.querySelectorAll('.sidebar-nav a').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href !== '#' && !href.startsWith('javascript')) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 767 && sidebar) {
                    setMobileHidden(true);
                }
            });
        }
    });

    // ===== Ajustar menú al redimensionar ventana =====
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth <= 767) {
                if (localStorage.getItem('nominaMobileMenuHidden') === 'true') {
                    setMobileHidden(true);
                } else {
                    setMobileHidden(false);
                }
            } else {
                if (sidebar) {
                    sidebar.classList.remove('expanded');
                    sidebar.classList.remove('mobile-hidden');
                }
            }
        }, 250);
    });

    console.log('✓ Responsive Menu Handler cargado');
});
