(function() {
    'use strict';

    const yearElement = document.getElementById('currentYear');
    if (yearElement) {
        yearElement.textContent = new Date().getFullYear();
    }

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

    const sections = document.querySelectorAll('.term-section');

    window.addEventListener('scroll', function() {
        let currentSectionId = '';
        const scrollPosition = window.scrollY;

        sections.forEach(function(section) {
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