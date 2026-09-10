/**
 * ============================================================
 * ARCHIVO: terminos.js
 * QUÉ HACE: Coloca el año actual en el footer, detecta la
 *            sección visible al hacer scroll para resaltar el
 *            enlace correspondiente y aplica scroll suave al
 *            navegar por los enlaces de la página.
 * TIPO: ESTÁTICO (no consume API)
 * ENDPOINTS QUE CONSUME: Ninguno
 * CLÁVES localStorage QUE USA: Ninguna
 * LIBRERÍAS EXTERNAS: Ninguna
 * ============================================================
 */

// Año actual en el footer
        document.getElementById('currentYear').textContent = new Date().getFullYear();

        // Navegación activa al hacer scroll
        const sections = document.querySelectorAll('.term-section');
        const navLinks = document.querySelectorAll('.nav-link');

        // Actualiza el enlace activo según la sección visible actualmente
        function updateActiveNav() {
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                const sectionHeight = section.clientHeight;
                
                if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        }

        window.addEventListener('scroll', updateActiveNav);

        // Inicializar activo
        updateActiveNav();

        // Scroll suave mejorado
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });