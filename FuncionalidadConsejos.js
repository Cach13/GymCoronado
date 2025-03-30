document.addEventListener('DOMContentLoaded', function() {
    const parrafos = document.querySelectorAll('.parrafo-carrusel');
    const btnPrev = document.querySelector('.carrusel-btn.prev');
    const btnNext = document.querySelector('.carrusel-btn.next');
    const indicadores = document.querySelectorAll('.indicador');
    let currentIndex = 0;
    let intervalo;

    // Iniciar carrusel
    function iniciarCarrusel() {
        mostrarParrafo(currentIndex);
        iniciarAutoPlay();
        agregarEventListeners();
    }

    // Mostrar párrafo específico
    function mostrarParrafo(index) {
        parrafos.forEach(p => p.classList.remove('active'));
        parrafos[index].classList.add('active');
        
        indicadores.forEach(ind => ind.classList.remove('active'));
        indicadores[index].classList.add('active');
    }

    // Navegación
    function navegar(direccion) {
        resetearAutoPlay();
        if (direccion === 'prev') {
            currentIndex = (currentIndex > 0) ? currentIndex - 1 : parrafos.length - 1;
        } else {
            currentIndex = (currentIndex < parrafos.length - 1) ? currentIndex + 1 : 0;
        }
        mostrarParrafo(currentIndex);
    }

    // Auto-play
    function iniciarAutoPlay() {
        intervalo = setInterval(() => {
            currentIndex = (currentIndex < parrafos.length - 1) ? currentIndex + 1 : 0;
            mostrarParrafo(currentIndex);
        }, 6000);
    }

    function resetearAutoPlay() {
        clearInterval(intervalo);
        iniciarAutoPlay();
    }

    // Event listeners
    function agregarEventListeners() {
        btnPrev.addEventListener('click', () => navegar('prev'));
        btnNext.addEventListener('click', () => navegar('next'));

        indicadores.forEach((ind, index) => {
            ind.addEventListener('click', () => {
                resetearAutoPlay();
                currentIndex = index;
                mostrarParrafo(currentIndex);
            });
        });

        // Pausar al interactuar
        const carrusel = document.querySelector('.carrusel-container');
        carrusel.addEventListener('mouseenter', () => clearInterval(intervalo));
        carrusel.addEventListener('mouseleave', iniciarAutoPlay);
    }

    // Iniciar
    iniciarCarrusel();
});