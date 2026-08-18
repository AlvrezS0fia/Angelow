/**
 * ============================================================
 * GUÍA DE TALLAS - ANGELOW
 * JavaScript para navegación suave y resaltado de secciones
 * ============================================================
 */

(function() {
    'use strict';

    /**
     * ============================================================
     * 1. AÑO ACTUAL EN EL FOOTER
     * ============================================================
     */
    const yearElement = document.getElementById('currentYear');
    if (yearElement) {
        yearElement.textContent = new Date().getFullYear();
    }

    /**
     * ============================================================
     * 2. NAVEGACIÓN SUAVE Y RESALTADO DE ENLACES
     * ============================================================
     */
    const navLinks = document.querySelectorAll('.nav-link');

    navLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);

            if (targetSection) {
                // Scroll suave hacia la sección
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                // Resaltar el enlace activo
                navLinks.forEach(function(l) {
                    l.classList.remove('active');
                });
                this.classList.add('active');
            }
        });
    });

    /**
     * ============================================================
     * 3. RESALTADO AUTOMÁTICO AL HACER SCROLL
     * ============================================================
     */
    const sections = document.querySelectorAll('.term-section');

    window.addEventListener('scroll', function() {
        let currentSectionId = '';
        const scrollPosition = window.scrollY;

        sections.forEach(function(section) {
            const sectionTop = section.offsetTop;
            // Si el scroll pasó la sección (con un margen de 200px)
            if (scrollPosition >= sectionTop - 200) {
                currentSectionId = section.getAttribute('id');
            }
        });

        // Actualizar la clase 'active' en los enlaces
        navLinks.forEach(function(link) {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + currentSectionId) {
                link.classList.add('active');
            }
        });
    });

    /**
     * ============================================================
     * 4. SOPORTE PARA EL BOTÓN VOLVER (comportamiento adicional)
     * ============================================================
     */
    const backButton = document.querySelector('.btn-back-home');
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            // El botón ya tiene un href, pero podemos agregar
            // un pequeño efecto de transición si se desea
            // (por ahora no hacemos nada adicional)
        });
    }

    console.log('✅ Guía de Tallas - ANGELOW: JavaScript cargado correctamente');
})();