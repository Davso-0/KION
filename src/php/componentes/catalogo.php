<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la base de datos
$rutaConexion = __DIR__ . '/../config/conexion_BD.php';
if (!file_exists($rutaConexion)) {
    $rutaConexion = __DIR__ . '/../../config/conexion_BD.php';
}
require_once $rutaConexion;

$categoriaSeleccionada = $_GET['categoria'] ?? null;
$productos = [];
$categoriasDisponibles = [];
$errorConsulta = null;

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // 1. Obtener categorías dinámicas reales de la BD
        $catStmt = $pdo->query("SELECT nombre FROM categorias ORDER BY nombre ASC");
        while ($row = $catStmt->fetch(PDO::FETCH_ASSOC)) {
            $categoriasDisponibles[] = $row['nombre'];
        }
        if (empty($categoriasDisponibles)) {
            $categoriasDisponibles = ['Medicamentos', 'Alimentos', 'Antiparasitarios', 'Higiene', 'Accesorios'];
        }

        // 2. Obtener productos con stock real consolidado de todas las sucursales
        $sql = "SELECT 
                    p.id_producto, 
                    p.nombre, 
                    p.descripcion,
                    p.precio, 
                    p.imagen_url,
                    COALESCE(c.nombre, 'General') AS categoria,
                    COALESCE(SUM(i.existencias), 0) AS stock
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                LEFT JOIN inventarios i ON p.id_producto = i.id_producto
                LEFT JOIN sucursales s ON i.id_sucursal = s.id_sucursal
                WHERE p.estado = 'ACTIVO' AND (s.estado = 'ACTIVA' OR s.estado IS NULL)";

        if ($categoriaSeleccionada !== null && in_array($categoriaSeleccionada, $categoriasDisponibles, true)) {
            $sql .= " AND c.nombre = :categoria";
        }

        $sql .= " GROUP BY p.id_producto, p.nombre, p.descripcion, p.precio, p.imagen_url, c.nombre ORDER BY p.nombre ASC";
        
        $stmt = $pdo->prepare($sql);
        if ($categoriaSeleccionada !== null && in_array($categoriaSeleccionada, $categoriasDisponibles, true)) {
            $stmt->bindValue(':categoria', $categoriaSeleccionada, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $errorConsulta = 'No pudimos cargar los productos en este momento.';
    }
} else {
    $errorConsulta = 'Fallo al conectar con la base de datos.';
}

$rutasImagenesAntiguas = [
    1 => '../../img/perron.jpg',
    2 => '../../img/whiskas.jpg',
    3 => '../../img/purina.jpg',
    4 => '../../img/malta.jpg'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KION · Catálogo</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            /* VARIABLES MODO CLARO */
            --bg-app: #F4F5F7;
            --bg-surface: #FFFFFF;
            --bg-panel: #FAFAFA;
            --ink: #1F2328;
            --ink-soft: #656D76;
            --border-soft: #D0D7DE;
            --coffee-main: #936545;
            --coffee-light: rgba(147, 101, 69, 0.1);
            --coffee-gradient: linear-gradient(135deg, #A87B57, #7A5134);
            --success: #1F883D;
            --danger: #CF222E;
            --font-display: 'Fraunces', serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
            --shadow-sm: 0 4px 12px rgba(0,0,0,0.04);
            --shadow-md: 0 10px 24px rgba(0,0,0,0.08);
            --radius: 16px;
        }

        /* VARIABLES MODO OSCURO */
        body.dark-mode {
            --bg-app: oklch(20% 0.01 250); 
            --bg-surface: oklch(25% 0.01 250);
            --bg-panel: oklch(23% 0.01 250);
            --ink: oklch(95% 0.01 250); 
            --ink-soft: oklch(75% 0.01 250); 
            --border-soft: oklch(35% 0.01 250);
            --coffee-main: oklch(80% 0.03 60); 
            --coffee-light: rgba(179, 131, 91, 0.15);
            --coffee-gradient: linear-gradient(155deg, oklch(40% 0.045 48), oklch(20% 0.035 52));
            --success: oklch(70% 0.1 152);
            --danger: oklch(65% 0.15 25);
            --shadow-sm: 0 4px 12px rgba(0,0,0,0.4);
            --shadow-md: 0 10px 24px rgba(0,0,0,0.6);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--font-body); background: var(--bg-app); color: var(--ink); -webkit-font-smoothing: antialiased; transition: background 0.3s ease, color 0.3s ease; }
        a { text-decoration: none; color: inherit; }
        button { border: none; background: none; cursor: pointer; font-family: inherit; }

        /* HEADER */
        .topbar { display: flex; justify-content: space-between; align-items: center; padding: 16px 40px; background: var(--bg-surface); border-bottom: 1px solid var(--border-soft); position: sticky; top: 0; z-index: 50; box-shadow: var(--shadow-sm); transition: background 0.3s ease, border-color 0.3s ease; }
        .topbar__marca { display: flex; align-items: baseline; gap: 12px; }
        .topbar__logo { font-family: var(--font-display); font-size: 26px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px; transition: color 0.3s ease; }
        .topbar__logo i { color: var(--coffee-main); }
        .topbar__subtitulo { font-size: 13px; color: var(--ink-soft); display: none; }
        
        .topbar__acciones { display: flex; align-items: center; gap: 14px; }
        .btn-nav { font-weight: 600; font-size: 13.5px; padding: 8px 16px; border-radius: 10px; background: var(--bg-app); border: 1px solid var(--border-soft); transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px; color: var(--ink); }
        .btn-nav:hover { background: var(--coffee-light); color: var(--coffee-main); border-color: var(--coffee-main); }
        .btn-icon { width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center; background: var(--bg-app); border: 1px solid var(--border-soft); font-size: 20px; transition: all 0.2s ease; color: var(--ink); }
        .btn-icon:hover { background: var(--coffee-light); color: var(--coffee-main); border-color: var(--coffee-main); }
        
        .topbar__carrito { position: relative; }
        .topbar__carrito-conteo { position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; font-size: 11px; font-weight: 800; width: 20px; height: 20px; border-radius: 50%; display: grid; place-items: center; border: 2px solid var(--bg-surface); }

        /* LAYOUT PRINCIPAL */
        .layout { display: grid; grid-template-columns: 240px 1fr; gap: 40px; padding: 40px; max-width: 1440px; margin: 0 auto; min-height: 80vh; }
        
        /* SIDEBAR FILTROS */
        .filtros__titulo { font-family: var(--font-display); font-size: 18px; font-weight: 600; margin-bottom: 20px; border-bottom: 1px solid var(--border-soft); padding-bottom: 10px; }
        .filtros__lista { display: flex; flex-direction: column; gap: 6px; }
        .filtros__enlace { padding: 10px 14px; border-radius: 10px; font-size: 14px; font-weight: 500; color: var(--ink-soft); transition: all 0.2s ease; display: flex; align-items: center; justify-content: space-between; }
        .filtros__enlace:hover { background: var(--bg-surface); color: var(--ink); box-shadow: var(--shadow-sm); }
        .filtros__enlace--activo { background: var(--coffee-main); color: white; font-weight: 700; }
        .filtros__enlace--activo:hover { background: var(--coffee-main); color: white; }

        /* MAIN CATALOGO */
        .catalogo__encabezado { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
        .catalogo__encabezado h1 { font-family: var(--font-display); font-size: 32px; font-weight: 700; }
        .catalogo__conteo { font-size: 14px; font-weight: 600; color: var(--ink-soft); background: var(--bg-surface); padding: 4px 12px; border-radius: 20px; border: 1px solid var(--border-soft); }
        
        .estado-vacio { text-align: center; padding: 60px 20px; background: var(--bg-surface); border-radius: var(--radius); border: 1px dashed var(--border-soft); color: var(--ink-soft); }
        
        .grid-productos { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; }
        
        /* TARJETA PRODUCTO */
        .tarjeta { background: var(--bg-surface); border-radius: var(--radius); border: 1px solid var(--border-soft); overflow: hidden; box-shadow: var(--shadow-sm); transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease, background 0.3s ease; display: flex; flex-direction: column; }
        .tarjeta:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); border-color: var(--coffee-main); }
        .tarjeta__imagen { position: relative; width: 100%; padding-top: 85%; background: var(--bg-panel); border-bottom: 1px solid var(--border-soft); overflow: hidden; }
        .tarjeta__imagen img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
        .tarjeta:hover .tarjeta__imagen img { transform: scale(1.05); }
        .tarjeta__badge { position: absolute; top: 12px; left: 12px; background: rgba(255,255,255,0.9); backdrop-filter: blur(4px); color: #1F2328; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 20px; border: 1px solid var(--border-soft); box-shadow: var(--shadow-sm); z-index: 2; }
        .dark-mode .tarjeta__badge { background: rgba(0,0,0,0.7); color: #FFF; border-color: rgba(255,255,255,0.2); }
        
        .tarjeta__cuerpo { padding: 20px; display: flex; flex-direction: column; flex: 1; }
        .tarjeta__titulo { font-family: var(--font-display); font-size: 18px; font-weight: 600; line-height: 1.3; margin-bottom: 6px; }
        .tarjeta__desc { font-size: 13px; color: var(--ink-soft); flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 14px; }
        
        .tarjeta__meta { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; }
        .tarjeta__precio { font-family: var(--font-display); font-size: 22px; font-weight: 700; color: var(--coffee-main); }
        .tarjeta__stock { font-size: 12px; font-weight: 700; background: var(--bg-app); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-soft); }
        .stock-ok { color: var(--success); }
        .stock-none { color: var(--danger); background: rgba(207,34,46,0.1); border-color: rgba(207,34,46,0.2); }
        
        .btn-agregar { width: 100%; background: var(--coffee-gradient); color: white; font-weight: 700; font-size: 14px; padding: 12px; border-radius: 10px; transition: transform 0.2s ease, opacity 0.2s ease, box-shadow 0.2s ease; display: flex; justify-content: center; align-items: center; gap: 8px; border: none; }
        .btn-agregar:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(147,101,69,0.3); }
        .btn-agregar:active:not(:disabled) { transform: scale(0.97); }
        .btn-agregar:disabled { background: var(--border-soft); color: var(--ink-soft); cursor: not-allowed; }

        /* PANEL DEL CARRITO (Sliding) */
        .cart-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(3px); z-index: 90; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
        .cart-overlay.active { opacity: 1; pointer-events: auto; }
        
        .cart-panel { position: fixed; top: 0; right: -420px; width: 400px; max-width: 100%; height: 100vh; background: var(--bg-surface); z-index: 100; box-shadow: -10px 0 30px rgba(0,0,0,0.2); transition: right 0.4s cubic-bezier(0.16, 1, 0.3, 1), background 0.3s ease; display: flex; flex-direction: column; }
        .cart-panel.active { right: 0; }
        
        .cart-panel__header { padding: 24px; border-bottom: 1px solid var(--border-soft); display: flex; justify-content: space-between; align-items: center; background: var(--bg-panel); }
        .cart-panel__header h2 { font-family: var(--font-display); font-size: 22px; margin: 0; }
        .cart-panel__close { font-size: 24px; color: var(--ink-soft); transition: color 0.2s ease; }
        .cart-panel__close:hover { color: var(--danger); }
        
        .cart-panel__items { flex: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 16px; }
        
        .cart-item { display: grid; grid-template-columns: 70px 1fr; gap: 14px; padding-bottom: 16px; border-bottom: 1px solid var(--border-soft); }
        .cart-item__img { width: 100%; height: 70px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-soft); }
        .cart-item__info { display: flex; flex-direction: column; justify-content: space-between; }
        .cart-item__title { font-weight: 600; font-size: 14px; line-height: 1.2; margin-bottom: 4px; color: var(--ink); }
        .cart-item__price { font-weight: 700; color: var(--coffee-main); font-size: 14px; }
        
        /* Controles de Cantidad */
        .cart-qty-controls { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
        .qty-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid var(--border-soft); background: var(--bg-app); color: var(--ink); font-weight: 700; font-size: 16px; display: grid; place-items: center; transition: all 0.2s ease; }
        .qty-btn:hover { background: var(--border-soft); }
        .qty-input { width: 45px; height: 28px; text-align: center; border: 1px solid var(--border-soft); border-radius: 6px; font-family: var(--font-body); font-weight: 600; font-size: 14px; background: var(--bg-surface); color: var(--ink); -moz-appearance: textfield; transition: border-color 0.2s; }
        .qty-input::-webkit-outer-spin-button, .qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .qty-input:focus { outline: none; border-color: var(--coffee-main); }
        .btn-remove { margin-left: auto; color: var(--danger); font-size: 18px; padding: 4px; border-radius: 6px; transition: background 0.2s; }
        .btn-remove:hover { background: rgba(207,34,46,0.1); }

        .cart-panel__footer { padding: 24px; border-top: 1px solid var(--border-soft); background: var(--bg-panel); }
        .cart-panel__total { display: flex; justify-content: space-between; align-items: center; font-size: 20px; font-weight: 700; margin-bottom: 20px; font-family: var(--font-display); }
        .cart-panel__checkout { width: 100%; background: var(--coffee-gradient); color: white; font-weight: 700; font-size: 16px; padding: 14px; border-radius: 12px; display: flex; justify-content: center; gap: 8px; transition: transform 0.2s; border: none; }
        .cart-panel__checkout:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(147,101,69,0.3); }

        /* Dark mode Swal overrides */
        .dark-mode .swal2-popup { background: var(--bg-surface); color: var(--ink); }

        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; padding: 20px; }
            .topbar__subtitulo { display: none; }
            .filtros__lista { flex-direction: row; overflow-x: auto; padding-bottom: 10px; }
            .filtros__enlace { white-space: nowrap; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="topbar__marca">
        <a href="../modulos/home/home.php" class="topbar__logo"><i class="ph-fill ph-paw-print"></i> KION</a>
        <span class="topbar__subtitulo" data-i18n="topbar_sub">Experiencia Premium para el cuidado animal.</span>
    </div>

    <div class="topbar__acciones">
        <button class="btn-icon" id="btnTheme" title="Modo Oscuro / Claro"><i class="ph ph-moon"></i></button>
        <button class="btn-icon" id="btnLang" title="English / Español"><i class="ph ph-translate"></i></button>
        
        <?php 
        // Determinar la ruta del dashboard dinámicamente con las rutas relativas correctas
        $rutaDashboard = '../../inicioSesion.php'; // Por defecto si no hay sesión
        $textoPanel = 'Iniciar Sesión';
        $iconoPanel = 'ph-sign-in';
        
        if (isset($_SESSION['usuario'])) {
            $textoPanel = 'Panel';
            $iconoPanel = 'ph-squares-four';
            $rolSesion = strtolower($_SESSION['usuario']['rol'] ?? '');
            
            if (strpos($rolSesion, 'admin') !== false) {
                $rutaDashboard = '../modulos/home/dashboard.php';
            } elseif (strpos($rolSesion, 'gerente') !== false) {
                $rutaDashboard = '../modulos/home/dashboard_gerente.php';
            } else {
                // Cliente regular o usuario sin dashboard administrativo
                $rutaDashboard = '../modulos/home/dashboard_usuario.php'; 
            }
        }
        ?>
        
        <a href="<?= htmlspecialchars($rutaDashboard, ENT_QUOTES, 'UTF-8') ?>" class="btn-nav">
            <i class="ph <?= $iconoPanel ?>"></i> <span data-i18n="nav_dashboard"><?= $textoPanel ?></span>
        </a>
        
        <button class="btn-icon topbar__carrito" id="open-cart" aria-label="Carrito">
            <i class="ph ph-shopping-cart"></i>
            <span id="cart-count" class="topbar__carrito-conteo">0</span>
        </button>
    </div>
</header>

<div class="layout">
    <aside class="filtros">
        <h2 class="filtros__titulo" data-i18n="filter_title">Categorías</h2>
        <nav class="filtros__lista">
            <a href="catalogo.php" class="filtros__enlace <?= $categoriaSeleccionada === null ? 'filtros__enlace--activo' : '' ?>">
                <span data-i18n="filter_all">Todos los productos</span>
            </a>
            <?php foreach ($categoriasDisponibles as $cat): ?>
                <a href="?categoria=<?= urlencode($cat) ?>" class="filtros__enlace <?= $categoriaSeleccionada === $cat ? 'filtros__enlace--activo' : '' ?>">
                    <span><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="catalogo">
        <div class="catalogo__encabezado">
            <h1><?= $categoriaSeleccionada !== null ? htmlspecialchars($categoriaSeleccionada, ENT_QUOTES, 'UTF-8') : '<span data-i18n="cat_title">Catálogo General</span>' ?></h1>
            <p class="catalogo__conteo"><?= count($productos) ?> <span data-i18n="cat_items">artículos</span></p>
        </div>

        <?php if ($errorConsulta !== null): ?>
            <div class="estado-vacio">
                <i class="ph ph-warning-circle" style="font-size: 40px; margin-bottom:10px; color:var(--coffee-main);"></i>
                <p><?= htmlspecialchars($errorConsulta, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php elseif (count($productos) === 0): ?>
            <div class="estado-vacio">
                <i class="ph ph-package" style="font-size: 40px; margin-bottom:10px; color:var(--ink-soft);"></i>
                <p data-i18n="empty_state">No hay productos con stock disponible en esta categoría.</p>
            </div>
        <?php else: ?>
            <div class="grid-productos">
                <?php foreach ($productos as $producto):
                    $stock   = (int) $producto['stock'];
                    $agotado = $stock <= 0;
                    $nombre  = htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8');
                    $idProd  = (int) $producto['id_producto'];
                    $precio  = (float) $producto['precio'];
                    
                    // Lógica de imágenes (Nueva url o fallback antiguo)
                    $imgSrc = $producto['imagen_url'] ?: '';
                    if (!$imgSrc) {
                        $rutaAutomatica = "../../img/{$idProd}.jpg";
                        if (isset($rutasImagenesAntiguas[$idProd])) {
                            $imgSrc = $rutasImagenesAntiguas[$idProd];
                        } elseif (file_exists($rutaAutomatica)) {
                            $imgSrc = $rutaAutomatica;
                        } else {
                            $imgSrc = "https://placehold.co/400x400/EEEEEE/936545?text=" . urlencode($producto['categoria']);
                        }
                    }
                ?>
                <article class="tarjeta">
                    <div class="tarjeta__imagen">
                        <span class="tarjeta__badge"><?= htmlspecialchars($producto['categoria'], ENT_QUOTES, 'UTF-8') ?></span>
                        <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $nombre ?>" loading="lazy">
                    </div>
                    <div class="tarjeta__cuerpo">
                        <h3 class="tarjeta__titulo"><?= $nombre ?></h3>
                        <p class="tarjeta__desc"><?= htmlspecialchars($producto['descripcion'] ?: 'Sin descripción detallada.', ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="tarjeta__meta">
                            <span class="tarjeta__precio">$<?= number_format($precio, 2) ?></span>
                            <span class="tarjeta__stock <?= $agotado ? 'stock-none' : 'stock-ok' ?>">
                                <?= $agotado ? '<span data-i18n="stock_out">Agotado</span>' : '<span data-i18n="stock_lbl">Disponibles:</span> ' . $stock ?>
                            </span>
                        </div>
                        <button type="button" class="btn-agregar js-add-cart" 
                                data-id="<?= $idProd ?>" 
                                data-nombre="<?= $nombre ?>" 
                                data-precio="<?= $precio ?>" 
                                data-img="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>"
                                data-stock="<?= $stock ?>" 
                                <?= $agotado ? 'disabled' : '' ?>>
                            <i class="ph ph-shopping-cart-plus"></i>
                            <span><?= $agotado ? '<span data-i18n="btn_out">Sin stock</span>' : '<span data-i18n="btn_add">Agregar al carrito</span>' ?></span>
                        </button>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- CARRITO LATERAL -->
<div id="cart-overlay" class="cart-overlay"></div>
<aside id="cart-panel" class="cart-panel">
    <div class="cart-panel__header">
        <h2 data-i18n="cart_title">Tu Carrito</h2>
        <button id="close-cart" class="cart-panel__close"><i class="ph ph-x"></i></button>
    </div>
    <div id="cart-items" class="cart-panel__items">
        <!-- Los items se renderizan aquí -->
    </div>
    <div class="cart-panel__footer">
        <div class="cart-panel__total">
            <span data-i18n="cart_total">Total:</span>
            <span id="cart-total-price">$0.00</span>
        </div>
        <button class="cart-panel__checkout"><i class="ph ph-credit-card"></i> <span data-i18n="cart_checkout">Proceder al pago</span></button>
    </div>
</aside>

<script>

// MODO OSCURO / CLARO

const body = document.body;
const btnTheme = document.getElementById('btnTheme');
const themeIcon = btnTheme.querySelector('i');

// Sincronizar preferencia inicial (revisa localStorage de catálogo o de admin)
if (localStorage.getItem('kion-admin-theme') === 'dark' || localStorage.getItem('kion-catalog-theme') === 'dark') {
    body.classList.add('dark-mode');
    themeIcon.classList.replace('ph-moon', 'ph-sun');
}

btnTheme.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    const isDark = body.classList.contains('dark-mode');
    
    if (isDark) {
        themeIcon.classList.replace('ph-moon', 'ph-sun');
    } else {
        themeIcon.classList.replace('ph-sun', 'ph-moon');
    }
    
    // Guardar para que se recuerde tanto en catálogo como en dashboard
    localStorage.setItem('kion-catalog-theme', isDark ? 'dark' : 'light');
    localStorage.setItem('kion-admin-theme', isDark ? 'dark' : 'light');
});

// ==========================================
// IDIOMA (Bilingüe)
// ==========================================
const I18N = {
    es: {
        topbar_sub: 'Experiencia Premium para el cuidado animal.',
        nav_dashboard: 'Panel',
        filter_title: 'Categorías',
        filter_all: 'Todos los productos',
        cat_title: 'Catálogo General',
        cat_items: 'artículos',
        empty_state: 'No hay productos con stock disponible en esta categoría.',
        stock_lbl: 'Disponibles:',
        stock_out: 'Agotado',
        btn_add: 'Agregar al carrito',
        btn_out: 'Sin stock',
        cart_title: 'Tu Carrito',
        cart_total: 'Total:',
        cart_checkout: 'Proceder al pago',
        cart_empty: 'Tu carrito está vacío.',
        alert_added: 'Producto agregado al carrito',
        alert_max: 'No puedes agregar más del stock disponible'
    },
    en: {
        topbar_sub: 'Premium experience for animal care.',
        nav_dashboard: 'Dashboard',
        filter_title: 'Categories',
        filter_all: 'All products',
        cat_title: 'General Catalog',
        cat_items: 'items',
        empty_state: 'No products available in stock for this category.',
        stock_lbl: 'In Stock:',
        stock_out: 'Out of Stock',
        btn_add: 'Add to Cart',
        btn_out: 'No Stock',
        cart_title: 'Your Cart',
        cart_total: 'Total:',
        cart_checkout: 'Proceed to Checkout',
        cart_empty: 'Your cart is empty.',
        alert_added: 'Product added to cart',
        alert_max: 'You cannot exceed the available stock'
    }
};

let currentLang = localStorage.getItem('kion-catalog-lang') || 'es';

function t(key) { return I18N[currentLang][key] || key; }

function applyLanguage() {
    document.documentElement.lang = currentLang;
    document.querySelectorAll('[data-i18n]').forEach(el => { 
        el.innerHTML = t(el.dataset.i18n); 
    });
    renderCart(); // Re-renderizar carrito para traducir textos dinámicos
}

document.getElementById('btnLang').addEventListener('click', () => {
    currentLang = currentLang === 'es' ? 'en' : 'es';
    localStorage.setItem('kion-catalog-lang', currentLang);
    applyLanguage();
});

// ==========================================
// LÓGICA DEL CARRITO (Con control preciso de Stock)
// ==========================================
let cart = JSON.parse(localStorage.getItem('kion-cart')) || [];
const cartOverlay = document.getElementById('cart-overlay');
const cartPanel = document.getElementById('cart-panel');
const cartItemsContainer = document.getElementById('cart-items');

function saveCart() {
    localStorage.setItem('kion-cart', JSON.stringify(cart));
}

function moneyFormat(val) {
    return '$' + Number(val).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function updateCartUI() {
    const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
    const totalPrice = cart.reduce((sum, item) => sum + (item.precio * item.qty), 0);
    
    document.getElementById('cart-count').textContent = totalQty;
    document.getElementById('cart-total-price').textContent = moneyFormat(totalPrice);
}

function renderCart() {
    if (cart.length === 0) {
        cartItemsContainer.innerHTML = `<div style="text-align:center; color:var(--ink-soft); margin-top:40px;"><i class="ph ph-shopping-cart" style="font-size:40px; margin-bottom:10px;"></i><p>${t('cart_empty')}</p></div>`;
    } else {
        cartItemsContainer.innerHTML = cart.map(item => `
            <div class="cart-item">
                <img src="${item.img}" class="cart-item__img" alt="${item.nombre}">
                <div class="cart-item__info">
                    <div>
                        <div class="cart-item__title">${item.nombre}</div>
                        <div class="cart-item__price">${moneyFormat(item.precio)}</div>
                    </div>
                    <div class="cart-qty-controls">
                        <button class="qty-btn btn-minus" data-id="${item.id}">-</button>
                        <input type="number" class="qty-input" data-id="${item.id}" value="${item.qty}" min="1" max="${item.stock}">
                        <button class="qty-btn btn-plus" data-id="${item.id}">+</button>
                        <button class="btn-remove" data-id="${item.id}" title="Eliminar"><i class="ph ph-trash"></i></button>
                    </div>
                </div>
            </div>
        `).join('');
    }
    updateCartUI();
}

function addToCart(id, nombre, precio, img, maxStock) {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        if (existing.qty < maxStock) {
            existing.qty++;
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: t('alert_added'), showConfirmButton: false, timer: 1500, background: body.classList.contains('dark-mode') ? 'var(--bg-surface)' : '#fff', color: 'var(--ink)' });
        } else {
            Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: t('alert_max'), showConfirmButton: false, timer: 2000, background: body.classList.contains('dark-mode') ? 'var(--bg-surface)' : '#fff', color: 'var(--ink)' });
        }
    } else {
        cart.push({ id, nombre, precio, img, stock: maxStock, qty: 1 });
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: t('alert_added'), showConfirmButton: false, timer: 1500, background: body.classList.contains('dark-mode') ? 'var(--bg-surface)' : '#fff', color: 'var(--ink)' });
    }
    saveCart();
    renderCart();
}

function changeQty(id, newQty) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    
    let parsedQty = parseInt(newQty);
    if (isNaN(parsedQty) || parsedQty < 1) parsedQty = 1;
    if (parsedQty > item.stock) {
        parsedQty = item.stock;
        Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: t('alert_max'), showConfirmButton: false, timer: 2000, background: body.classList.contains('dark-mode') ? 'var(--bg-surface)' : '#fff', color: 'var(--ink)' });
    }
    
    item.qty = parsedQty;
    saveCart();
    renderCart();
}

// Event Listeners Globales
document.addEventListener('click', e => {
    // Agregar al carrito desde el catálogo
    const addBtn = e.target.closest('.js-add-cart');
    if (addBtn && !addBtn.disabled) {
        addToCart(
            addBtn.dataset.id, 
            addBtn.dataset.nombre, 
            parseFloat(addBtn.dataset.precio), 
            addBtn.dataset.img, 
            parseInt(addBtn.dataset.stock)
        );
    }

    // Botón Menos en Carrito
    if (e.target.classList.contains('btn-minus')) {
        const id = e.target.dataset.id;
        const item = cart.find(i => i.id === id);
        if (item && item.qty > 1) changeQty(id, item.qty - 1);
    }

    // Botón Más en Carrito
    if (e.target.classList.contains('btn-plus')) {
        const id = e.target.dataset.id;
        const item = cart.find(i => i.id === id);
        if (item) changeQty(id, item.qty + 1);
    }

    // Eliminar del Carrito
    const removeBtn = e.target.closest('.btn-remove');
    if (removeBtn) {
        cart = cart.filter(item => item.id !== removeBtn.dataset.id);
        saveCart();
        renderCart();
    }
});

// Input de número manual en el carrito
cartItemsContainer.addEventListener('change', e => {
    if (e.target.classList.contains('qty-input')) {
        changeQty(e.target.dataset.id, e.target.value);
    }
});

// Control del panel lateral
document.getElementById('open-cart').addEventListener('click', () => {
    cartOverlay.classList.add('active');
    cartPanel.classList.add('active');
});
const closeCart = () => {
    cartOverlay.classList.remove('active');
    cartPanel.classList.remove('active');
};
document.getElementById('close-cart').addEventListener('click', closeCart);
cartOverlay.addEventListener('click', closeCart);

// Inicialización
applyLanguage();
renderCart();
</script>
</body>
</html>