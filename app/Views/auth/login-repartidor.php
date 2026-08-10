<?php
if (isset($_SESSION['user']) && ($_SESSION['user']['rol'] ?? '') === 'repartidor') {
    header('Location: ' . APP_URL . '/repartidor');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ANGELOW - Acceso Repartidor</title>
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/imagenes/general/favico.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/login.css">
    <style>
        .repartidor-login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            padding: 20px;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .repartidor-login-card {
            background: #0f0f0f;
            border-radius: 24px;
            padding: 40px 32px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid #2a2a2a;
            text-align: center;
        }
        .repartidor-logo {
            margin-bottom: 24px;
        }
        .repartidor-logo img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        }
        .repartidor-login-card h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .repartidor-login-card p {
            color: #9ca3af;
            font-size: 14px;
            margin-bottom: 28px;
        }
        .repartidor-form-group {
            margin-bottom: 16px;
            text-align: left;
        }
        .repartidor-form-group label {
            display: block;
            color: #e5e7eb;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .repartidor-form-group input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid #374151;
            background: #1c1c1c;
            color: #ffffff;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s;
            outline: none;
        }
        .repartidor-form-group input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }
        .repartidor-btn {
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            border: none;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.2s;
            font-family: inherit;
        }
        .repartidor-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.35);
        }
        .repartidor-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .repartidor-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            color: #9ca3af;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }
        .repartidor-back:hover {
            color: #ffffff;
        }
        .repartidor-message {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: 500;
            display: none;
        }
        .repartidor-message.error {
            display: block;
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .repartidor-message.success {
            display: block;
            background: rgba(16, 185, 129, 0.1);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
    </style>
</head>
<body>
    <div class="repartidor-login-wrapper">
        <div class="repartidor-login-card">
            <div class="repartidor-logo">
                <img src="<?= APP_URL ?>/assets/imagenes/general/logos.png" alt="ANGELOW" onerror="this.style.display='none'">
            </div>
            <h1>Acceso Repartidor</h1>
            <p>Ingresa con tu cuenta para gestionar tus entregas</p>

            <div id="messageBox" class="repartidor-message"></div>

            <form id="repartidorLoginForm">
                <div class="repartidor-form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" placeholder="tu@email.com" required autocomplete="email">
                </div>
                <div class="repartidor-form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" placeholder="••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" class="repartidor-btn" id="loginBtn">
                    Ingresar
                </button>
            </form>

            <a href="<?= APP_URL ?>/" class="repartidor-back">
                <i class="fas fa-arrow-left"></i> Volver a la tienda
            </a>
        </div>
    </div>

    <script>
        const APP_URL = '<?= APP_URL ?>';
        const form = document.getElementById('repartidorLoginForm');
        const btn = document.getElementById('loginBtn');
        const messageBox = document.getElementById('messageBox');

        function showMessage(message, type = 'error') {
            messageBox.textContent = message;
            messageBox.className = 'repartidor-message ' + type;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            showMessage('', '');

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!email || !password) {
                showMessage('Completa todos los campos');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Ingresando...';

            try {
                const res = await fetch(APP_URL + '/repartidor/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await res.json();

                if (data.success) {
                    showMessage('Acceso correcto. Redirigiendo...', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect || APP_URL + '/repartidor';
                    }, 600);
                } else {
                    showMessage(data.message || 'Error al iniciar sesión');
                    btn.disabled = false;
                    btn.textContent = 'Ingresar';
                }
            } catch (err) {
                showMessage('Error de conexión. Intenta nuevamente.');
                btn.disabled = false;
                btn.textContent = 'Ingresar';
            }
        });
    </script>
</body>
</html>
