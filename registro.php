<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KION - Registro de Usuarios</title>
    <style>
        :root {
            --bg-dark: #121212;
            --card-bg: #1e1e1e;
            --input-bg: #2a2a2a;
            --border-color: #333333;
            --primary-gold: #c9a444;
            --primary-gold-hover: #e0b84c;
            --text-main: #f0f0f0;
            --text-muted: #a0a0a0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            animation: pageEnter .55s ease both;
        }

        body.page-exit {
            animation: pageExit .36s ease both;
        }

        @keyframes pageEnter {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pageExit {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-8px); }
        }

        .auth-card {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 440px;
            border: 1px solid var(--border-color);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .brand-logo {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 3px;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .brand-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary-gold);
            text-align: center;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 6px;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 12px 14px;
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-main);
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        input:focus, select:focus {
            border-color: var(--primary-gold);
        }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background-color: var(--primary-gold);
            border: none;
            border-radius: 6px;
            color: #121212;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.2s;
        }

        .btn-submit:hover {
            background-color: var(--primary-gold-hover);
        }

        .form-footer {
            margin-top: 22px;
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
        }

        .form-footer a {
            color: var(--primary-gold);
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="brand-header">
        <div class="brand-logo">KION</div>
        <div class="brand-subtitle">Vet & Agropecuario</div>
    </div>

    <div class="form-title">Crear nueva cuenta</div>

    <!-- Formulario de Registro (Vista Previa Visual) -->
    <form onsubmit="event.preventDefault(); alert('Vista previa del diseño: Registro en proceso.');">
        <div class="form-group">
            <label for="nombre">Nombre Completo</label>
            <input type="text" id="nombre" placeholder="Ej. Ana Martínez" required>
        </div>

        <div class="form-group">
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" placeholder="usuario@kion.com" required>
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" placeholder="••••••••" required>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirmar Contraseña</label>
            <input type="password" id="confirm_password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-submit">Registrar Usuario</button>
    </form>

    <div class="form-footer">
        ¿Ya tienes una cuenta? <a href="inicioSesion.php">Inicia Sesión</a>
    </div>
</div>

<script>
    document.querySelectorAll('a[href="inicioSesion.php"]').forEach((enlace) => {
        enlace.addEventListener('click', (evento) => {
            evento.preventDefault();
            document.body.classList.add('page-exit');
            setTimeout(() => { window.location.href = enlace.href; }, 360);
        });
    });
</script>

</body>
</html>