<?php
session_start();
$loginError = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
$esPeticionAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/src/php/config/conexion_BD.php';

    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($pdo === null) {
        $loginError = $errorConexion ?? 'No fue posible conectar con la base de datos.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $loginError = 'Escribe un correo electrónico válido.';
    } elseif ($contrasena === '') {
        $loginError = 'Escribe tu contraseña.';
    } else {
        $consulta = $pdo->prepare('SELECT u.id_usuario, u.nombre, u.apellido, u.correo, u.password_hash, u.id_rol, u.id_sucursal, u.estado, r.nombre AS rol FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol WHERE u.correo = ? LIMIT 1');
        $consulta->execute([$correo]);
        $usuario = $consulta->fetch();

        if (!$usuario) {
            $loginError = 'La contraseña o correo electrónico no son correctos.';
        } elseif ($usuario['estado'] !== 'ACTIVO') {
            $loginError = 'Esta cuenta se encuentra inactiva.';
        } elseif (!password_verify($contrasena, $usuario['password_hash'])) {
            $loginError = 'La contraseña o correo electrónico no son correctos.';
        } else {
            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id_usuario' => $usuario['id_usuario'],
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'correo' => $usuario['correo'],
                'id_rol' => $usuario['id_rol'],
                'id_sucursal' => $usuario['id_sucursal'],
                'rol' => $usuario['rol'],
            ];
            $destino = $usuario['rol'] === 'Usuario'
                ? 'src/php/componentes/catalogo.php'
                : 'src/php/modulos/home/dashboard.php';
            if ($esPeticionAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true, 'redirect' => $destino]);
                exit;
            }
            header('Location: ' . $destino);
            exit;
        }
    }

    if ($loginError !== '') {
        if ($esPeticionAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => $loginError]);
            exit;
        }
        $_SESSION['login_error'] = $loginError;
        header('Location: inicioSesion.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KION - Iniciar Sesion</title>
    <style>
        :root {
            --gold: #c9a444;
            --gold-hover: #e0b84c;
            --panel: rgba(18, 18, 18, .88);
            --field: rgba(255, 255, 255, .08);
            --line: rgba(255, 255, 255, .18);
            --text: #f5f5f5;
            --muted: #b6b6b6;
        }

        * { box-sizing: border-box; }
        html, body { width: 100%; height: 100%; margin: 0; }
        body { overflow: hidden; background: #000; color: var(--text); font-family: Arial, sans-serif; }
        body { animation: pageEnter .55s ease both; }
        body.page-exit { animation: pageExit .36s ease both; }
        @keyframes pageEnter {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pageExit {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-8px); }
        }
        #videoFondo { position: fixed; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: -2; }
        .velo { position: fixed; inset: 0; background: rgba(0, 0, 0, .28); z-index: -1; }
        .login-panel { position: fixed; top: 0; right: 0; display: flex; width: min(430px, 100%); height: 100%; align-items: center; padding: 48px; background: linear-gradient(90deg, rgba(18, 18, 18, 0) 0%, rgba(18, 18, 18, .04) 18%, rgba(18, 18, 18, .18) 36%, rgba(18, 18, 18, .5) 58%, rgba(18, 18, 18, .78) 78%, rgba(18, 18, 18, .88) 100%); }
        .login-content { width: 100%; }
        .brand { margin-bottom: 42px; text-align: center; }
        .brand-name { margin: 0 0 8px; font-size: 31px; letter-spacing: 4px; }
        .brand-subtitle { color: var(--muted); font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; }
        .form-title { margin: 0 0 26px; color: var(--gold); font-size: 22px; text-align: center; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 7px; color: var(--muted); font-size: 13px; }
        input { width: 100%; padding: 13px 14px; border: 1px solid var(--line); border-radius: 6px; outline: none; background: var(--field); color: var(--text); font-size: 15px; }
        input:focus { border-color: var(--gold); }
        .btn-submit { width: 100%; margin-top: 8px; padding: 13px; border: 0; border-radius: 6px; background: var(--gold); color: #17130a; cursor: pointer; font-size: 15px; font-weight: 700; }
        .btn-submit:hover { background: var(--gold-hover); }
        .form-footer { margin-top: 24px; color: var(--muted); font-size: 13px; text-align: center; }
        .form-footer a { color: var(--gold); font-weight: 700; text-decoration: none; }
        .form-footer a:hover { text-decoration: underline; }
        .login-message-overlay { display: none; position: fixed; inset: 0; z-index: 20; align-items: flex-start; justify-content: center; padding: 28px 20px; pointer-events: none; }
        .login-message-overlay.visible { display: flex; animation: pageEnter .3s ease both; }
        .login-message { position: relative; width: min(320px, calc(100% - 40px)); padding: 20px 22px 16px; border: 1px solid rgba(239, 170, 165, .55); border-radius: 12px; background: rgba(70, 30, 30, .96); color: #efaaa5; font-size: 13px; text-align: left; box-shadow: 0 12px 30px rgba(0, 0, 0, .45); pointer-events: auto; }
        .login-message-title { display: block; margin-bottom: 5px; font-weight: 700; }
        .login-message-list { margin: 0; padding-left: 18px; }
        .login-message-list li + li { margin-top: 3px; }
        .login-message-close { position: absolute; top: 8px; right: 10px; width: 24px; height: 24px; border: 0; background: transparent; color: currentColor; cursor: pointer; font-size: 20px; line-height: 1; }
        @media (max-width: 640px) {
            .login-panel { width: 100%; max-width: 430px; padding: 32px 24px; background: linear-gradient(90deg, rgba(18, 18, 18, .3) 0%, rgba(18, 18, 18, .58) 30%, rgba(18, 18, 18, .86) 70%, rgba(18, 18, 18, .94) 100%); }
            .brand { margin-bottom: 28px; }
        }
    </style>
</head>
<body>
    <video id="videoFondo" autoplay muted loop playsinline>
        <source src="src/videoEJEMPLO/20.mp4" type="video/mp4">
    </video>
    <div class="velo"></div>

    <aside class="login-panel">
        <div class="login-content">
            <header class="brand">
                <h1 class="brand-name">KION</h1>
                <div class="brand-subtitle">Vet & Agropecuario</div>
            </header>

            <form id="loginForm" method="post" novalidate>
                <h2 class="form-title">Iniciar Sesion</h2>

                <div class="form-group">
                    <label for="correo">Correo electronico</label>
                    <input type="email" id="correo" name="correo" placeholder="usuario@kion.com" autocomplete="email" required>
                </div>

                <div class="form-group">
                    <label for="contrasena">Contrasena</label>
                    <input type="password" id="contrasena" name="contrasena" placeholder="Tu contrasena" autocomplete="current-password" required>
                </div>

                <button class="btn-submit" type="submit">Iniciar Sesion</button>
            </form>

            <div class="form-footer">
                ¿No tienes cuenta? <a href="registro.php">Registrate</a>
            </div>
        </div>
    </aside>

    <div class="login-message-overlay<?= $loginError !== '' ? ' visible' : '' ?>" id="loginMessageOverlay">
        <div class="login-message" id="loginMessage" role="alert" aria-live="assertive">
            <?php if ($loginError !== ''): ?>
                <button class="login-message-close" type="button" aria-label="Cerrar notificación">&times;</button>
                <strong class="login-message-title">No se pudo iniciar sesión</strong>
                <ul class="login-message-list"><li><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></li></ul>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const correoInput = document.getElementById('correo');
        const contrasenaInput = document.getElementById('contrasena');
        const loginMessageOverlay = document.getElementById('loginMessageOverlay');
        const loginMessage = document.getElementById('loginMessage');

        function showLoginMessage(errors) {
            loginMessage.innerHTML = `<button class="login-message-close" type="button" aria-label="Cerrar notificación">&times;</button><strong class="login-message-title">Revisa tu inicio de sesión</strong><ul class="login-message-list">${errors.map(error => `<li>${error}</li>`).join('')}</ul>`;
            loginMessageOverlay.classList.add('visible');
        }

        loginForm.addEventListener('submit', (evento) => {
            evento.preventDefault();
            const errors = [];

            if (!correoInput.value.trim()) errors.push('Escribe tu correo electrónico.');
            else if (!correoInput.validity.valid) errors.push('Escribe un correo electrónico válido.');
            if (!contrasenaInput.value) errors.push('Escribe tu contraseña.');

            if (errors.length > 0) {
                showLoginMessage(errors);
                return;
            }

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(loginForm)
            })
                .then(respuesta => respuesta.json())
                .then(resultado => {
                    if (resultado.ok) {
                        window.location.href = resultado.redirect;
                        return;
                    }
                    showLoginMessage([resultado.message || 'No fue posible iniciar sesión.']);
                })
                .catch(() => showLoginMessage(['No fue posible conectar con el servidor.']));
        });

        loginMessageOverlay.addEventListener('click', (evento) => {
            if (evento.target === loginMessageOverlay || evento.target.closest('.login-message-close')) loginMessageOverlay.classList.remove('visible');
        });

        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape') loginMessageOverlay.classList.remove('visible');
        });

        document.querySelectorAll('a[href="registro.php"]').forEach((enlace) => {
            enlace.addEventListener('click', (evento) => {
                evento.preventDefault();
                document.body.classList.add('page-exit');
                setTimeout(() => { window.location.href = enlace.href; }, 360);
            });
        });
    </script>
</body>