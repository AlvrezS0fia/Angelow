/**
 * ============================================================
 * ARCHIVO: pedidos_envios.js
 * QUÉ HACE: Establece el año actual en el footer, habilita el
 *            scroll suave hacia cada sección desde la barra de
 *            navegación y resalta el enlace activo según el scroll.
 * TIPO: ESTÁTICO (no consume API)
 * ENDPOINTS QUE CONSUME: Ninguno
 * CLÁVES localStorage QUE USA: Ninguna
 * LIBRERÍAS EXTERNAS: Ninguna
 * ============================================================
 */

// ============================================================
// 1. AÑO ACTUAL EN EL FOOTER
// ============================================================
(function() {
    'use strict';

    const yearElement = document.getElementById('currentYear');
    if (yearElement) {
        yearElement.textContent = new Date().getFullYear();
    }

    // ============================================================
    // 2. NAVEGACIÓN SUAVE Y RESALTADO DE ENLACES
    // ============================================================
    const navLinks = document.querySelectorAll('.nav-link');

    navLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);

            if (targetSection) {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                navLinks.forEach(function(l) {
                    l.classList.remove('active');
                });
                this.classList.add('active');
            }
        });
    });

    // ============================================================
    // 3. RESALTADO AUTOMÁTICO DE LA SECCIÓN VISIBLE AL HACER SCROLL
    // ============================================================
    const sections = document.querySelectorAll('.term-section');

    window.addEventListener('scroll', function() {
        let currentSectionId = '';
        const scrollPosition = window.scrollY;

        sections.forEach(function(section) {
            // Recorrer las secciones para detectar cuál está visible
            const sectionTop = section.offsetTop;
            if (scrollPosition >= sectionTop - 200) {
                currentSectionId = section.getAttribute('id');
            }
        });

        navLinks.forEach(function(link) {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + currentSectionId) {
                link.classList.add('active');
            }
        });
    });

    console.log('✅ Pedidos y Envíos - ANGELOW: JavaScript cargado correctamente');
})();