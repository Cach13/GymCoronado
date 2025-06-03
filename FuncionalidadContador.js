// JavaScript para el menú hamburguesa - Contador de Calorías

document.addEventListener('DOMContentLoaded', function() {
    // Elementos del menú
    const menuToggle = document.querySelector('.menu-toggle');
    const navegacion = document.querySelector('.navegacion');
    const navLinks = document.querySelectorAll('.navegacion a');
    const header = document.querySelector('header');
    
    // Función para alternar el menú
    function toggleMenu() {
        menuToggle.classList.toggle('active');
        navegacion.classList.toggle('active');
        
        // Agregar/quitar clase al body para prevenir scroll cuando el menú está abierto
        document.body.classList.toggle('menu-open');
    }
    
    // Event listener para el botón hamburguesa
    if (menuToggle) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMenu();
        });
    }
    
    // Cerrar menú al hacer click en un enlace (para dispositivos móviles)
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                toggleMenu();
            }
        });
    });
    
    // Cerrar menú al hacer click fuera de él
    document.addEventListener('click', function(e) {
        if (!menuToggle.contains(e.target) && 
            !navegacion.contains(e.target) && 
            navegacion.classList.contains('active')) {
            toggleMenu();
        }
    });
    
    // Cerrar menú con la tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && navegacion.classList.contains('active')) {
            toggleMenu();
        }
    });
    
    // Efecto de scroll en el header
    let lastScrollTop = 0;
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (header) {
            if (scrollTop > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }
        
        // Cerrar menú móvil al hacer scroll
        if (navegacion.classList.contains('active') && Math.abs(scrollTop - lastScrollTop) > 50) {
            toggleMenu();
        }
        
        lastScrollTop = scrollTop;
    });
    
    // Manejar cambios de tamaño de ventana
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992 && navegacion.classList.contains('active')) {
            // Cerrar menú móvil si la ventana se hace más grande
            toggleMenu();
        }
    });
    
    // Prevenir scroll del body cuando el menú móvil está abierto
    const style = document.createElement('style');
    style.textContent = `
        body.menu-open {
            overflow: hidden;
        }
        
        @media (max-width: 992px) {
            body.menu-open {
                position: fixed;
                width: 100%;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Animación suave para la aparición del menú
    if (navegacion) {
        navegacion.addEventListener('transitionend', function() {
            if (!navegacion.classList.contains('active')) {
                // Restaurar el scroll cuando el menú se cierre
                document.body.classList.remove('menu-open');
            }
        });
    }
    
    // Mejorar accesibilidad
    if (menuToggle) {
        menuToggle.setAttribute('aria-label', 'Abrir menú de navegación');
        menuToggle.setAttribute('aria-expanded', 'false');
        
        // Actualizar aria-expanded cuando se abre/cierra el menú
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    const isActive = menuToggle.classList.contains('active');
                    menuToggle.setAttribute('aria-expanded', isActive.toString());
                    menuToggle.setAttribute('aria-label', 
                        isActive ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'
                    );
                }
            });
        });
        
        observer.observe(menuToggle, { attributes: true });
    }
    
    // Mejorar el contraste para usuarios con preferencias de alto contraste
    if (window.matchMedia && window.matchMedia('(prefers-contrast: high)').matches) {
        document.documentElement.style.setProperty('--glass-border', 'rgba(255, 255, 255, 0.5)');
    }
    
    // Detectar si el usuario prefiere movimiento reducido
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        // Reducir las animaciones para usuarios que prefieren menos movimiento
        const reducedMotionStyle = document.createElement('style');
        reducedMotionStyle.textContent = `
            .navegacion {
                transition: right 0.2s ease !important;
            }
            .menu-toggle span {
                transition: all 0.1s ease !important;
            }
        `;
        document.head.appendChild(reducedMotionStyle);
    }
});

// Función adicional para destacar el enlace activo
function setActiveNavLink() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navegacion a');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        
        // Comparar la ruta actual con el href del enlace
        if (link.getAttribute('href') === currentPath || 
            (currentPath.includes(link.getAttribute('href')) && link.getAttribute('href') !== '/')) {
            link.classList.add('active');
        }
    });
}

// Ejecutar al cargar la página
document.addEventListener('DOMContentLoaded', setActiveNavLink);
// Base de datos de alimentos (calorías por 100g)
        const foodDatabase = {
            'manzana': 52,
            'banana': 89,
            'plátano': 89,
            'naranja': 47,
            'pera': 57,
            'uva': 62,
            'fresa': 32,
            'piña': 50,
            'mango': 60,
            'sandía': 30,
            'melón': 34,
            'kiwi': 61,
            'durazno': 39,
            'cereza': 63,
            'arándano': 57,
            
            'pollo': 165,
            'res': 250,
            'cerdo': 242,
            'pescado': 206,
            'salmón': 208,
            'atún': 132,
            'huevo': 155,
            'leche': 42,
            'yogurt': 59,
            'queso': 402,
            
            'arroz': 130,
            'pasta': 131,
            'pan': 265,
            'tortilla': 218,
            'avena': 68,
            'quinoa': 120,
            'papa': 77,
            'batata': 86,
            
            'lechuga': 15,
            'tomate': 18,
            'cebolla': 40,
            'zanahoria': 41,
            'brócoli': 34,
            'espinaca': 23,
            'apio': 16,
            'pepino': 15,
            'pimiento': 31,
            
            'almendra': 579,
            'nuez': 654,
            'cacahuate': 567,
            'avellana': 628,
            
            'aceite de oliva': 884,
            'mantequilla': 717,
            'azúcar': 387,
            'miel': 304,
            'chocolate': 546
        };

        let dailyFoods = [];

        function showSuggestions() {
            const input = document.getElementById('foodName');
            const suggestions = document.getElementById('suggestions');
            const query = input.value.toLowerCase().trim();
            
            if (query.length < 2) {
                suggestions.style.display = 'none';
                return;
            }
            
            const matches = Object.keys(foodDatabase).filter(food => 
                food.includes(query)
            ).slice(0, 5);
            
            if (matches.length > 0) {
                suggestions.innerHTML = matches.map(food => 
                    `<div class="suggestion-item" onclick="selectFood('${food}')">${food} (${foodDatabase[food]} kcal/100g)</div>`
                ).join('');
                suggestions.style.display = 'block';
            } else {
                suggestions.style.display = 'none';
            }
        }

        function selectFood(food) {
            document.getElementById('foodName').value = food;
            document.getElementById('suggestions').style.display = 'none';
        }

        function addFood() {
            const foodName = document.getElementById('foodName').value.trim().toLowerCase();
            const quantity = parseFloat(document.getElementById('quantity').value);
            const unit = document.getElementById('unit').value;
            
            if (!foodName || !quantity || quantity <= 0) {
                alert('Por favor completa todos los campos correctamente');
                return;
            }
            
            let calories = 0;
            let baseCalories = foodDatabase[foodName];
            
            if (!baseCalories) {
                // Si no está en la base de datos, permitir entrada manual
                const customCalories = prompt(`"${foodName}" no está en nuestra base de datos. ¿Cuántas calorías tiene por 100g?`);
                if (customCalories && !isNaN(customCalories)) {
                    baseCalories = parseFloat(customCalories);
                    foodDatabase[foodName] = baseCalories; // Agregar a la base de datos temporal
                } else {
                    return;
                }
            }
            
            // Calcular calorías según la unidad
            switch(unit) {
                case 'gramos':
                    calories = (baseCalories * quantity) / 100;
                    break;
                case 'unidad':
                    // Asumimos peso promedio según el tipo de alimento
                    let avgWeight = getAverageWeight(foodName);
                    calories = (baseCalories * quantity * avgWeight) / 100;
                    break;
                case 'taza':
                    calories = (baseCalories * quantity * 150) / 100; // 150g aprox por taza
                    break;
                case 'cucharada':
                    calories = (baseCalories * quantity * 15) / 100; // 15g aprox por cucharada
                    break;
                case 'rebanada':
                    calories = (baseCalories * quantity * 30) / 100; // 30g aprox por rebanada
                    break;
            }
            
            const foodItem = {
                name: foodName,
                quantity: quantity,
                unit: unit,
                calories: Math.round(calories),
                id: Date.now()
            };
            
            dailyFoods.push(foodItem);
            updateDisplay();
            clearForm();
        }

        function getAverageWeight(foodName) {
            // Pesos promedio en gramos para diferentes alimentos
            const weights = {
                'manzana': 150,
                'banana': 120,
                'plátano': 120,
                'naranja': 130,
                'huevo': 50,
                'rebanada de pan': 30,
                'tortilla': 30
            };
            return weights[foodName] || 100; // 100g por defecto
        }

        function removeFood(id) {
            dailyFoods = dailyFoods.filter(food => food.id !== id);
            updateDisplay();
        }

        function updateDisplay() {
            const totalCalories = dailyFoods.reduce((sum, food) => sum + food.calories, 0);
            const totalFoods = dailyFoods.length;
            
            document.getElementById('totalCalories').textContent = totalCalories;
            document.getElementById('totalFoods').textContent = totalFoods;
            
            const foodList = document.getElementById('foodList');
            
            if (dailyFoods.length === 0) {
                foodList.innerHTML = '<p style="text-align: center; color: #666; font-style: italic;">No has agregado ningún alimento aún</p>';
            } else {
                foodList.innerHTML = dailyFoods.map(food => `
                    <div class="food-item">
                        <div class="food-info">
                            <div class="food-name">${food.name.charAt(0).toUpperCase() + food.name.slice(1)}</div>
                            <div class="food-details">${food.quantity} ${food.unit}</div>
                        </div>
                        <div class="food-calories">${food.calories} kcal</div>
                        <button class="btn btn-danger" onclick="removeFood(${food.id})">Eliminar</button>
                    </div>
                `).join('');
            }
        }

        function clearForm() {
            document.getElementById('foodName').value = '';
            document.getElementById('quantity').value = '1';
            document.getElementById('unit').value = 'gramos';
            document.getElementById('suggestions').style.display = 'none';
        }

        function resetDay() {
            if (confirm('¿Estás seguro de que quieres reiniciar el día? Se eliminarán todos los alimentos.')) {
                dailyFoods = [];
                updateDisplay();
            }
        }

        // Event listeners
        document.getElementById('foodName').addEventListener('input', showSuggestions);
        document.getElementById('foodName').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                addFood();
            }
        });

        // Ocultar sugerencias al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                document.getElementById('suggestions').style.display = 'none';
            }
        });

        // Inicializar display
        updateDisplay();