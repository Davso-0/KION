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
        .role-demo-link { display: block; margin-top: 12px; color: rgba(255, 255, 255, .58); font-size: 12px; text-align: center; text-decoration: none; }
        .role-demo-link:hover { color: var(--gold); text-decoration: underline; }
        .role-overlay { display: none; position: fixed; inset: 0; z-index: 20; align-items: center; justify-content: center; padding: 20px; background: rgba(0, 0, 0, .58); backdrop-filter: blur(5px); }
        .role-overlay.visible { display: flex; animation: pageEnter .25s ease both; }
        .role-picker { position: relative; width: min(330px, calc(100% - 40px)); padding: 24px; border: 1px solid rgba(255, 255, 255, .18); border-radius: 14px; background: rgba(28, 28, 28, .94); box-shadow: 0 22px 55px rgba(0, 0, 0, .45); }
        .role-picker h3 { margin: 0 0 6px; color: var(--gold); font-size: 19px; text-align: center; }
        .role-picker p { margin: 0 0 18px; color: var(--muted); font-size: 12px; text-align: center; }
        .role-options { display: grid; gap: 8px; }
        .role-option { display: block; padding: 10px 12px; border: 1px solid rgba(255, 255, 255, .14); border-radius: 6px; background: rgba(255, 255, 255, .07); color: var(--text); font-size: 13px; text-align: center; text-decoration: none; transition: background .2s, border-color .2s; }
        .role-option:hover { border-color: var(--gold); background: rgba(201, 164, 68, .18); }
        .role-picker-close { position: absolute; top: 8px; right: 10px; border: 0; background: transparent; color: var(--muted); cursor: pointer; font-size: 22px; }
        .login-input.invalid { border-color: rgba(239, 170, 165, .95); box-shadow: 0 0 0 2px rgba(239, 170, 165, .18); }
        .login-inline-message { display: none; margin: 12px 0 0; color: #efaaa5; font-size: 12px; text-align: center; }
        .login-inline-message.visible { display: block; animation: pageEnter .25s ease both; }
        .login-divider { display: flex; align-items: center; gap: 10px; margin: 15px 0 9px; color: rgba(255, 255, 255, .42); font-size: 10px; letter-spacing: .5px; text-transform: uppercase; }
        .login-divider::before, .login-divider::after { content: ''; flex: 1; height: 1px; background: rgba(255, 255, 255, .18); }
        .google-login { display: flex; justify-content: center; width: 100%; min-height: 34px; opacity: .82; filter: saturate(.82); }
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
                    <input class="login-input" type="email" id="correo" name="correo" placeholder="usuario@kion.com" autocomplete="email" required>
                </div>

                <div class="form-group">
                    <label for="contrasena">Contrasena</label>
                    <input class="login-input" type="password" id="contrasena" name="contrasena" placeholder="Tu contrasena" autocomplete="current-password" required>
                </div>

                <button class="btn-submit" type="submit">Iniciar Sesion</button>
            </form>

            <small class="login-inline-message" id="loginInlineMessage" role="alert" aria-live="assertive"></small>

            <div class="login-divider"><span>o continúa con</span></div>
            <div class="google-login" id="googleLoginButton"></div>

            <div class="form-footer">
                ¿No tienes cuenta? <a href="registro.php">Registrate</a>
                <a class="role-demo-link" href="#seleccionar-rol" id="openRolePicker">Seleccionar rol</a>
            </div>
        </div>
    </aside>

    <div class="role-overlay" id="roleOverlay">
        <div class="role-picker" role="dialog" aria-modal="true" aria-labelledby="rolePickerTitle">
            <button class="role-picker-close" id="closeRolePicker" type="button" aria-label="Cerrar">&times;</button>
            <h3 id="rolePickerTitle">Seleccionar rol</h3>
            <p>Acceso rápido para la exposición de avances.</p>
            <div class="role-options">
                <a class="role-option" href="src/php/modulos/home/dashboard.php">Administrador General</a>
                <a class="role-option" href="src/php/modulos/home/dashboard.php">Gerente</a>
                <a class="role-option" href="src/php/modulos/home/dashboard.php">Cajero</a>
                <a class="role-option" href="src/php/componentes/catalogo.php">Usuario</a>
            </div>
        </div>
    </div>

    <script>
        window.googleClientId = '467947233896-kuvnpl5cdegqkduq4m3e1ste6280feaf.apps.googleusercontent.com';
        const loginForm = document.getElementById('loginForm');
        const correoInput = document.getElementById('correo');
        const contrasenaInput = document.getElementById('contrasena');
        const loginInlineMessage = document.getElementById('loginInlineMessage');
        const roleOverlay = document.getElementById('roleOverlay');
        const openRolePicker = document.getElementById('openRolePicker');
        const closeRolePicker = document.getElementById('closeRolePicker');

        openRolePicker.addEventListener('click', (evento) => {
            evento.preventDefault();
            roleOverlay.classList.add('visible');
        });

        closeRolePicker.addEventListener('click', () => roleOverlay.classList.remove('visible'));
        roleOverlay.addEventListener('click', (evento) => {
            if (evento.target === roleOverlay) roleOverlay.classList.remove('visible');
        });

        function showLoginMessage(errors, invalidFields = []) {
            correoInput.classList.toggle('invalid', invalidFields.includes('correo'));
            contrasenaInput.classList.toggle('invalid', invalidFields.includes('contrasena'));
            loginInlineMessage.textContent = errors.join(' ');
            loginInlineMessage.classList.add('visible');
        }

        function handleGoogleCredential(response) {
            const datos = new URLSearchParams({ credential: response.credential });
            fetch('google-callback.php', { method: 'POST', body: datos })
                .then(respuesta => respuesta.json())
                .then(resultado => {
                    if (resultado.ok) {
                        window.location.href = resultado.redirect;
                        return;
                    }
                    showLoginMessage([resultado.message || 'No fue posible iniciar sesión con Google.'], ['correo', 'contrasena']);
                })
                .catch(() => showLoginMessage(['No fue posible conectar con Google.'], ['correo', 'contrasena']));
        }

        window.onload = () => {
            if (!window.google) return;
            google.accounts.id.initialize({ client_id: window.googleClientId, callback: handleGoogleCredential });
            google.accounts.id.renderButton(document.getElementById('googleLoginButton'), { theme: 'filled_black', size: 'medium', width: 240, text: 'signin_with', shape: 'rectangular', logo_alignment: 'center' });
        };

        loginForm.addEventListener('submit', (evento) => {
            evento.preventDefault();
            const errors = [];

            if (!correoInput.value.trim()) errors.push('Escribe tu correo electrónico.');
            else if (!correoInput.validity.valid) errors.push('Escribe un correo electrónico válido.');
            if (!contrasenaInput.value) errors.push('Escribe tu contraseña.');

            if (errors.length > 0) {
                showLoginMessage(errors, [
                    !correoInput.value.trim() || !correoInput.validity.valid ? 'correo' : '',
                    !contrasenaInput.value ? 'contrasena' : ''
                ]);
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
                    showLoginMessage([resultado.message || 'No fue posible iniciar sesión.'], ['correo', 'contrasena']);
                })
                .catch(() => showLoginMessage(['No fue posible conectar con el servidor.'], ['correo', 'contrasena']));
        });

        [correoInput, contrasenaInput].forEach((campo) => {
            campo.addEventListener('input', () => {
                campo.classList.remove('invalid');
                if (!correoInput.classList.contains('invalid') && !contrasenaInput.classList.contains('invalid')) loginInlineMessage.classList.remove('visible');
            });
        });

        document.querySelectorAll('a[href="registro.php"]').forEach((enlace) => {
            enlace.addEventListener('click', (evento) => {
                evento.preventDefault();
                document.body.classList.add('page-exit');
                setTimeout(() => { window.location.href = enlace.href; }, 360);
            });
        });
    </script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</body>