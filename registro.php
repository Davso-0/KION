<?php
$registroMensaje = '';
$registroExitoso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/src/php/config/conexion_BD.php';

    $nombreCompleto = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['email'] ?? '');
    $contrasena = $_POST['password'] ?? '';
    $confirmacion = $_POST['confirm_password'] ?? '';

    if ($pdo === null) {
        $registroMensaje = $errorConexion ?? 'No fue posible conectar con la base de datos.';
    } elseif ($nombreCompleto === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $registroMensaje = 'Completa tu nombre y proporciona un correo válido.';
    } elseif (strlen($contrasena) < 8 || !preg_match('/[A-Z]/', $contrasena) || !preg_match('/[0-9]/', $contrasena)) {
        $registroMensaje = 'La contraseña debe tener 8 caracteres, una mayúscula y un número.';
    } elseif ($contrasena !== $confirmacion) {
        $registroMensaje = 'Las contraseñas no coinciden.';
    } else {
        $partesNombre = preg_split('/\s+/', $nombreCompleto, 2);
        $nombre = $partesNombre[0];
        $apellido = $partesNombre[1] ?? '-';

        try {
            $rol = $pdo->query("SELECT id_rol FROM roles WHERE nombre = 'Usuario' LIMIT 1")->fetchColumn();
            if (!$rol) {
                throw new RuntimeException('No se encontró el rol Usuario en la base de datos.');
            }

            $consulta = $pdo->prepare('INSERT INTO usuarios (nombre, apellido, correo, password_hash, id_rol, estado) VALUES (?, ?, ?, ?, ?, \'ACTIVO\')');
            $consulta->execute([$nombre, $apellido, $correo, password_hash($contrasena, PASSWORD_DEFAULT), $rol]);
            $registroMensaje = 'Registro completado correctamente.';
            $registroExitoso = true;
        } catch (PDOException $e) {
            $registroMensaje = $e->errorInfo[1] === 1062 ? 'Ese correo ya está registrado.' : 'No fue posible completar el registro.';
        } catch (Throwable $e) {
            $registroMensaje = $e->getMessage();
        }
    }
}
?>
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

        html, body {
            overflow: hidden;
        }

        body {
            background-color: #000000;
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 0;
            animation: pageEnter .55s ease both;
        }

        #videoFondo {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
        }

        .velo {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .16);
            z-index: -1;
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
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: auto;
            width: min(390px, calc(100% - 32px));
            max-height: calc(100vh - 24px);
            padding: 28px;
            background: linear-gradient(145deg, rgba(52, 52, 52, .48), rgba(20, 20, 20, .34));
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .42), 0 0 0 1px rgba(201, 164, 68, .08);
            -webkit-backdrop-filter: blur(45px);
            backdrop-filter: blur(3.65px);
            overflow: hidden;
        }

        .auth-card > * {
            width: 100%;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .brand-logo {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 3px;
            color: #ffffff;
            margin-bottom: 3px;
        }

        .brand-subtitle {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 14px;
            color: var(--primary-gold);
            text-align: center;
        }

        .form-group {
            margin-bottom: 12px;
        }

        label {
            display: block;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 4px;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 9px 11px;
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-main);
            font-size: 13px;
            outline: none;
            transition: border-color 0.2s;
        }

        input:focus, select:focus {
            border-color: var(--primary-gold);
        }

        .btn-submit {
            width: 100%;
            padding: 10px;
            background-color: var(--primary-gold);
            border: none;
            border-radius: 6px;
            color: #121212;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            margin-top: 6px;
            transition: background-color 0.2s;
        }

        .btn-submit:hover {
            background-color: var(--primary-gold-hover);
        }

        .form-footer {
            margin-top: 16px;
            text-align: center;
            font-size: 11px;
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

        .password-help {
            display: block;
            margin-top: 5px;
            color: var(--text-muted);
            font-size: 11px;
            line-height: 1.4;
        }

        .password-rules {
            display: grid;
            gap: 3px;
            margin-top: 7px;
            padding: 0;
            list-style: none;
            color: var(--text-muted);
            font-size: 11px;
        }

        .password-rules li::before {
            display: inline-block;
            width: 18px;
            color: #777777;
            content: '○';
            font-size: 14px;
            vertical-align: -1px;
        }

        .password-rules li.valid {
            color: #b9e8c4;
        }

        .password-rules li.valid::before {
            color: #8bd19a;
            content: '✓';
            font-weight: 700;
        }

        .field-error {
            display: none;
            margin-top: 5px;
            color: #efaaa5;
            font-size: 11px;
        }

        .field-error.visible {
            display: block;
        }

        .form-message {
            display: none;
            margin: 14px 0 0;
            padding: 10px 12px;
            border: 1px solid rgba(102, 178, 123, 0.55);
            border-radius: 8px;
            background: rgba(55, 130, 75, 0.18);
            color: #b9e8c4;
            font-size: 12px;
            text-align: center;
        }

        .form-message.visible {
            display: block;
            animation: messageEnter .3s ease both;
        }

        .form-message.error {
            border-color: rgba(239, 170, 165, .55);
            background: rgba(130, 55, 55, .2);
            color: #efaaa5;
        }

        @media (max-width: 640px) {
            .auth-card {
                width: 100%;
                max-width: 390px;
                min-height: auto;
                padding: 24px 20px;
                background: linear-gradient(145deg, rgba(52, 52, 52, .58), rgba(20, 20, 20, .44));
                border-radius: 14px;
            }

        }

        @keyframes messageEnter {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<video id="videoFondo" autoplay muted loop playsinline>
    <source src="src/videoEJEMPLO/22.mp4" type="video/mp4">
</video>
<div class="velo"></div>

<div class="auth-card">
    <div class="brand-header">
        <div class="brand-logo">KION</div>
        <div class="brand-subtitle">Vet & Agropecuario</div>
    </div>

    <div class="form-title">Crear nueva cuenta</div>

    <form id="registerForm" method="post" novalidate>
        <div class="form-group">
            <label for="nombre">Nombre Completo</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ej. Ana Martínez" required>
        </div>

        <div class="form-group">
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" placeholder="usuario@kion.com" required>
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" placeholder="••••••••" minlength="8" pattern="(?=.*[A-Z])(?=.*[0-9]).{8,}" title="Debe tener al menos 8 caracteres, una mayúscula y un número" required>
            <small class="password-help">Tu contraseña debe cumplir estos requisitos:</small>
            <ul class="password-rules" aria-label="Requisitos de la contraseña">
                <li data-rule="length">8 caracteres como mínimo</li>
                <li data-rule="uppercase">Una letra mayúscula</li>
                <li data-rule="number">Un número</li>
            </ul>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirmar Contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
            <small class="field-error" id="confirmPasswordError">Las contraseñas no coinciden.</small>
        </div>

        <button type="submit" class="btn-submit">Registrar Usuario</button>
    </form>

    <div class="form-message<?= $registroExitoso ? ' visible' : ($registroMensaje !== '' ? ' visible error' : '') ?>" id="formMessage" role="status" aria-live="polite">
        <?= htmlspecialchars($registroMensaje, ENT_QUOTES, 'UTF-8') ?>
    </div>

    <div class="form-footer">
        ¿Ya tienes una cuenta? <a href="inicioSesion.php">Inicia Sesión</a>
    </div>
</div>

<script>
    const registerForm = document.getElementById('registerForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const formMessage = document.getElementById('formMessage');
    const confirmPasswordError = document.getElementById('confirmPasswordError');
    const passwordRules = {
        length: value => value.length >= 8,
        uppercase: value => /[A-Z]/.test(value),
        number: value => /[0-9]/.test(value)
    };

    function updatePasswordRules() {
        Object.entries(passwordRules).forEach(([rule, check]) => {
            document.querySelector(`[data-rule="${rule}"]`).classList.toggle('valid', check(passwordInput.value));
        });
    }

    passwordInput.addEventListener('input', updatePasswordRules);
    confirmPasswordInput.addEventListener('input', () => {
        confirmPasswordError.classList.remove('visible');
    });

    registerForm.addEventListener('submit', (evento) => {
        updatePasswordRules();
        const passwordIsValid = Object.values(passwordRules).every(check => check(passwordInput.value));
        const fieldsAreFilled = registerForm.checkValidity();
        const passwordsMatch = passwordInput.value === confirmPasswordInput.value;
        confirmPasswordError.classList.toggle('visible', !passwordsMatch && confirmPasswordInput.value.length > 0);

        if (!fieldsAreFilled || !passwordIsValid || !passwordsMatch) {
            evento.preventDefault();
            formMessage.classList.remove('visible');
            return;
        }
    });

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