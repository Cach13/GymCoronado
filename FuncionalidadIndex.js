 // Carousel Script
 let currentSlide = 0;
 const slides = document.querySelectorAll('.carousel-slide');
 const dotsContainer = document.querySelector('.carousel-dots');
 
 // Crear puntos indicadores
 slides.forEach((_, index) => {
     const dot = document.createElement('span');
     dot.classList.add('dot');
     if(index === 0) dot.classList.add('active');
     dot.addEventListener('click', () => goToSlide(index));
     dotsContainer.appendChild(dot);
 });
 
 const dots = document.querySelectorAll('.dot');
 
 function updateCarousel() {
     slides.forEach((slide, index) => {
         slide.classList.toggle('active', index === currentSlide);
     });
     
     dots.forEach((dot, index) => {
         dot.classList.toggle('active', index === currentSlide);
     });
 }
 
 function moveSlide(n) {
     currentSlide = (currentSlide + n + slides.length) % slides.length;
     updateCarousel();
 }
 
 function goToSlide(n) {
     currentSlide = n;
     updateCarousel();
 }
 
 // Auto-avance cada 5 segundos
 setInterval(() => moveSlide(1), 5000);
 
 // Menú Hamburguesa Script
 const menuToggle = document.querySelector('.menu-toggle');
 const navegacion = document.querySelector('.navegacion');
 
 menuToggle.addEventListener('click', () => {
     menuToggle.classList.toggle('active');
     navegacion.classList.toggle('active');
     
     // Deshabilitar scroll cuando el menú está abierto
     if(navegacion.classList.contains('active')) {
         document.body.style.overflow = 'hidden';
     } else {
         document.body.style.overflow = 'auto';
     }
 });
 
 // Cerrar menú al hacer clic en un enlace
 document.querySelectorAll('.navegacion a').forEach(link => {
     link.addEventListener('click', () => {
         menuToggle.classList.remove('active');
         navegacion.classList.remove('active');
         document.body.style.overflow = 'auto';
     });
 });

