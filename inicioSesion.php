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
        #activarSonido { position: fixed; bottom: 24px; left: 24px; padding: 11px 16px; border: 1px solid rgba(255, 255, 255, .65); border-radius: 6px; background: rgba(0, 0, 0, .6); color: #fff; cursor: pointer; font-size: 14px; }

        @media (max-width: 640px) {
            .login-panel { width: 100%; max-width: 430px; padding: 32px 24px; background: linear-gradient(90deg, rgba(18, 18, 18, .3) 0%, rgba(18, 18, 18, .58) 30%, rgba(18, 18, 18, .86) 70%, rgba(18, 18, 18, .94) 100%); }
            .brand { margin-bottom: 28px; }
        }
    </style>
</head>
<body>
    <video id="videoFondo" autoplay muted loop playsinline>
        <source src="src/videoEJEMPLO/22.mp4" type="video/mp4">
    </video>
    <div class="velo"></div>

    <aside class="login-panel">
        <div class="login-content">
            <header class="brand">
                <h1 class="brand-name">KION</h1>
                <div class="brand-subtitle">Vet & Agropecuario</div>
            </header>

            <form onsubmit="event.preventDefault();">
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

    <button id="activarSonido" type="button">Añadir sonido</button>

    <script>
        const video = document.getElementById('videoFondo');
        const botonSonido = document.getElementById('activarSonido');

        botonSonido.addEventListener('click', async () => {
            if (!video.muted) {
                video.muted = true;
                botonSonido.textContent = 'Añadir sonido';
                return;
            }

            video.muted = false;
            video.defaultMuted = false;
            video.volume = 1;
            try {
                await video.play();
                botonSonido.textContent = 'Quitar sonido';
            } catch (error) {
                video.muted = true;
                botonSonido.textContent = 'Añadir sonido';
            }
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