<?php
// Configuración básica
$titulo = "¡Próximamente!";
$subtitulo = "Estamos trabajando en algo increíble";
$cuenta_regresiva = "2024-12-31"; // Fecha de lanzamiento
$color_principal = "#f3f3f3ff"; // Puedes cambiarlo
$email_contacto = "contacto@tudominio.com";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;600;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primario: <?php echo $color_principal; ?>;
            --color-secundario: #ff8a00;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #16088fff, #1703eeff, #0909edff);
            color: white;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            overflow: hidden;
        }
        
        .container {
            position: relative;
            z-index: 10;
            padding: 2rem;
            max-width: 800px;
        }
        
        h1 {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: white;
            -webkit-background-clip: text;
            background-clip: text;
           
            animation: pulse 2s infinite alternate;
        }
        
        h2 {
            font-weight: 300;
            font-size: 1.8rem;
            margin-bottom: 3rem;
        }
        
        .countdown {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .countdown-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 1.5rem;
            min-width: 80px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .countdown-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .countdown-label {
            font-size: 0.9rem;
            opacity: 0.8;
            text-transform: uppercase;
        }
        
        .notify-form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .notify-input {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 50px 0 0 50px;
            font-size: 1rem;
            outline: none;
        }
        
        .notify-button {
            background: linear-gradient(to right, var(--color-primario), var(--color-secundario));
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 0 50px 50px 0;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .notify-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .social-links {
            margin-top: 3rem;
            display: flex;
            justify-content: center;
            gap: 1.5rem;
        }
        
        .social-icon {
            color: white;
            font-size: 1.5rem;
            transition: all 0.3s;
        }
        
        .social-icon:hover {
            color: var(--color-primario);
            transform: translateY(-5px);
        }
        
        /* Animaciones */
        @keyframes pulse {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }
        
        /* Partículas animadas */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        /* Efecto de neón */
        .neon {
            text-shadow: 0 0 10px rgba(26, 26, 26, 0.8),
                         0 0 20px rgba(255, 255, 255, 0.6),
                         0 0 30px var(--color-primario),
                         0 0 40px var(--color-primario);
        }
    </style>
</head>
<body>
    <div class="particles" id="particles-js"></div>
    
    <div class="container">
        <h1 class="neon"><?php echo $titulo; ?></h1>
        <h2><?php echo $subtitulo; ?></h2>
        
        <div class="countdown">
            <div class="countdown-box">
                <div class="countdown-number" id="days">00</div>
                <div class="countdown-label">Días</div>
            </div>
            <div class="countdown-box">
                <div class="countdown-number" id="hours">00</div>
                <div class="countdown-label">Horas</div>
            </div>
            <div class="countdown-box">
                <div class="countdown-number" id="minutes">00</div>
                <div class="countdown-label">Minutos</div>
            </div>
            <div class="countdown-box">
                <div class="countdown-number" id="seconds">00</div>
                <div class="countdown-label">Segundos</div>
            </div>
        </div>
        
       
    </div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // Cuenta regresiva
        function updateCountdown() {
            const endDate = new Date("<?php echo $cuenta_regresiva; ?>").getTime();
            const now = new Date().getTime();
            const distance = endDate - now;
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById("days").innerText = days.toString().padStart(2, '0');
            document.getElementById("hours").innerText = hours.toString().padStart(2, '0');
            document.getElementById("minutes").innerText = minutes.toString().padStart(2, '0');
            document.getElementById("seconds").innerText = seconds.toString().padStart(2, '0');
        }
        
        setInterval(updateCountdown, 1000);
        updateCountdown();
        
        // Partículas animadas
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#ffffff" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.5, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#ffffff", "opacity": 0.4, "width": 1 },
                "move": { "enable": true, "speed": 2, "direction": "none", "random": true, "straight": false, "out_mode": "out" }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": { "enable": true, "mode": "repulse" },
                    "onclick": { "enable": true, "mode": "push" }
                }
            }
        });
        
        // Formulario
        document.getElementById("notifyForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const email = this.querySelector("input").value;
            alert(`¡Gracias! Te avisaremos cuando esté listo a: ${email}`);
            this.reset();
        });
    </script>
</body>
</html>