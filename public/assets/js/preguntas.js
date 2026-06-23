document.getElementById('currentYear').textContent = new Date().getFullYear();

    // Funcionalidad de acordeón para preguntas frecuentes
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            const isActive = item.classList.contains('active');
            
            // Cerrar todos los items
            faqItems.forEach(otherItem => {
                otherItem.classList.remove('active');
            });
            
            // Abrir el item clickeado si no estaba activo
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });

    // Smooth scroll para los enlaces de navegación
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                // Actualizar clase active
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });

    // Actualizar enlace activo al hacer scroll
    window.addEventListener('scroll', () => {
        let current = '';
        const sections = document.querySelectorAll('.term-section');
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollY >= (sectionTop - 200)) {
                current = section.getAttribute('id');
            }
        });

        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });

    // Abrir la primera pregunta de cada sección automáticamente (opcional)
    document.querySelectorAll('.term-section').forEach(section => {
        const firstFaq = section.querySelector('.faq-item');
        if (firstFaq) {
            // Comentado para que no se abran automáticamente
            // firstFaq.classList.add('active');
        }
    });