document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.navegacion');
    const header = document.querySelector('header');
    const navLinks = document.querySelectorAll('.navegacion a');
    const whatsappBtn = document.querySelector('.whatsapp-btn');
    
    // Función para alternar el menú hamburguesa
    function toggleMenu() {
        menuToggle.classList.toggle('active');
        nav.classList.toggle('active');
        document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
    }

    // Función para cerrar el menú al hacer clic en un enlace
    function closeMenuOnLinkClick() {
        if (nav.classList.contains('active')) {
            toggleMenu();
        }
    }

    // Función para el efecto de scroll en el header
    function handleScroll() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    }

    // Función para animar el botón de WhatsApp al aparecer
    function animateWhatsAppBtn() {
        if (whatsappBtn) {
            setTimeout(() => {
                whatsappBtn.style.opacity = '1';
                whatsappBtn.style.transform = 'translateY(0)';
            }, 300);
        }
    }

    // Event Listeners
    menuToggle.addEventListener('click', toggleMenu);
    
    navLinks.forEach(link => {
        link.addEventListener('click', closeMenuOnLinkClick);
    });

    window.addEventListener('scroll', handleScroll);

    // Inicialización de animaciones
    animateWhatsAppBtn();

    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.navegacion') && !e.target.closest('.menu-toggle') && nav.classList.contains('active')) {
            toggleMenu();
        }
    });

    // Efecto hover mejorado para enlaces
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });

    // Detectar cambios de tamaño de pantalla
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && nav.classList.contains('active')) {
            toggleMenu();
        }
    });
});