 document.getElementById('currentYear').textContent = new Date().getFullYear();
    // Smooth scroll
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            if(targetSection) {
                targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    // Active link on scroll
    window.addEventListener('scroll', () => {
        let current = '';
        document.querySelectorAll('.term-section').forEach(section => {
            const sectionTop = section.offsetTop;
            if(scrollY >= sectionTop - 200) current = section.getAttribute('id');
        });
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            if(link.getAttribute('href') === `#${current}`) link.classList.add('active');
        });
    });