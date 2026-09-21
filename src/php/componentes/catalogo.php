<?php
declare(strict_types=1);

$dbHost = 'localhost';
$dbName = 'kion'; 
$dbUser = 'root';
$dbPass = '';

$pdo = null;
$errorConexion = null;

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    $errorConexion = 'No pudimos conectar con la base de datos.';
}

$categoriaSeleccionada = $_GET['categoria'] ?? null;
$productos    = [];
$categoriasDisponibles = [];
$errorConsulta = null;

if ($pdo !== null) {
    try {
        $catStmt = $pdo->query("SELECT nombre FROM categorias ORDER BY id_categoria ASC");
        while ($row = $catStmt->fetch()) {
            $categoriasDisponibles[] = $row['nombre'];
        }

        if (empty($categoriasDisponibles)) {
            $categoriasDisponibles = ['Medicamentos', 'Alimentos', 'Antiparasitarios', 'Higiene', 'Accesorios'];
        }

        $sql = "SELECT 
                    p.id_producto, 
                    p.nombre, 
                    p.precio, 
                    c.nombre AS categoria,
                    COALESCE(i.existencias, 10) AS stock,
                    'KION' AS marca
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                LEFT JOIN inventarios i ON p.id_producto = i.id_producto
                WHERE p.estado = 'ACTIVO'";

        if ($categoriaSeleccionada !== null && in_array($categoriaSeleccionada, $categoriasDisponibles, true)) {
            $sql .= " AND c.nombre = :categoria";
        }

        $sql .= " ORDER BY p.id_producto ASC";
        $stmt = $pdo->prepare($sql);

        if ($categoriaSeleccionada !== null && in_array($categoriaSeleccionada, $categoriasDisponibles, true)) {
            $stmt->bindValue(':categoria', $categoriaSeleccionada, PDO::PARAM_STR);
        }

        $stmt->execute();
        $productos = $stmt->fetchAll();

    } catch (PDOException $e) {
        $errorConsulta = 'No pudimos cargar los productos.';
    }
}

function formatearPrecio(float $precio): string {
    return '$' . number_format($precio, 2) . ' MXN';
}

$rutasImagenes = [
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
<title>KION · Experiencia Premium</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600&family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../css/catalogo.css">
</head>
<body>

<div class="ambient-glow"></div>
<div class="ambient-glow ambient-glow--alt"></div>

<header class="topbar">
    <div class="topbar__marca">
        <a href="../modulos/home/home.php" class="topbar__logo" style="text-decoration: none; color: inherit;">KION</a>
        <span class="topbar__subtitulo">De gatos a vacas: alimento, medicamento y accesorios para cada especie que cuidamos.</span>
    </div>

    <div style="display: flex; align-items: center; gap: 14px;">
        <a href="../modulos/home/home.php" class="topbar__btn-home" style="display: inline-flex; align-items: center; gap: 6px; color: #FFFFFF; text-decoration: none; font-weight: 600; font-size: 0.9rem; padding: 7px 14px; border-radius: 10px; background: rgba(255, 255, 255, 0.14); transition: background 0.2s ease;">
            🏠 Inicio
        </a>
        <a href="../modulos/home/dashboard.php" class="topbar__btn-dashboard" style="display: inline-flex; align-items: center; gap: 6px; color: #FFFFFF; text-decoration: none; font-weight: 600; font-size: 0.9rem; padding: 7px 14px; border-radius: 10px; background: rgba(255, 255, 255, 0.14); transition: background 0.2s ease;">
            📊 Dashboard
        </a>

        <div class="topbar__carrito" aria-label="Carrito de venta">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L20.5 8H6" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="10" cy="20" r="1.4"/>
                <circle cx="17" cy="20" r="1.4"/>
            </svg>
            <span id="cart-count" class="topbar__carrito-conteo">0</span>
        </div>
    </div>
</header>

<div class="layout">
    <aside class="filtros">
        <h2 class="filtros__titulo">Categorías</h2>
        <nav class="filtros__lista">
            <a href="catalogo.php" class="filtros__enlace<?= $categoriaSeleccionada === null ? ' filtros__enlace--activo' : '' ?>">
                <span class="filtros__enlace-texto">Todos los productos</span>
            </a>
            <?php foreach ($categoriasDisponibles as $cat): ?>
                <a href="?categoria=<?= urlencode($cat) ?>" class="filtros__enlace<?= $categoriaSeleccionada === $cat ? ' filtros__enlace--activo' : '' ?>">
                    <span class="filtros__enlace-texto"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="catalogo">
        <div class="catalogo__encabezado">
            <h1><?= $categoriaSeleccionada !== null ? htmlspecialchars($categoriaSeleccionada, ENT_QUOTES, 'UTF-8') : 'Catálogo General' ?></h1>
            <p class="catalogo__conteo"><?= count($productos) ?> items</p>
        </div>

        <?php if ($errorConexion !== null || $errorConsulta !== null): ?>
            <div class="estado-vacio">
                <p><?= htmlspecialchars($errorConexion ?? $errorConsulta, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php elseif (count($productos) === 0): ?>
            <div class="estado-vacio">
                <p>No hay productos registrados en esta categoría.</p>
            </div>
        <?php else: ?>
            <div class="grid-productos">
                <?php foreach ($productos as $producto):
                    $stock       = (int) $producto['stock'];
                    $agotado     = $stock <= 0;
                    $precioTexto = formatearPrecio((float) $producto['precio']);
                    $nombre      = htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8');
                    $idProd      = (int) $producto['id_producto'];
                    
                    $rutaAutomatica = "../../img/{$idProd}.jpg";

                    if (isset($rutasImagenes[$idProd])) {
                        $imgSrc = $rutasImagenes[$idProd];
                    } 
                    elseif (file_exists($rutaAutomatica)) {
                        $imgSrc = $rutaAutomatica;
                    } 
                    else {
                        $textoPlaceholder = urlencode($producto['categoria']);
                        $imgSrc = "https://placehold.co/600x450/1A1815/C5A880?text={$textoPlaceholder}";
                    }
                ?>
                <article class="tarjeta">
                    <div class="tarjeta__glow"></div>
                    <div class="tarjeta__contenido">
                        <div class="tarjeta__imagen">
                            <span class="tarjeta__badge"><?= htmlspecialchars($producto['categoria'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></span>
                            <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $nombre ?>" loading="lazy">
                            <?php if ($agotado): ?>
                                <span class="tarjeta__agotado">Agotado</span>
                            <?php endif; ?>
                        </div>
                        <div class="tarjeta__cuerpo">
                            <h3 class="tarjeta__titulo"><?= $nombre ?></h3>
                            <p class="tarjeta__marca"><?= htmlspecialchars($producto['marca'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="tarjeta__pie">
                                <span class="tarjeta__precio"><?= $precioTexto ?></span>
                            </div>
                            <button type="button" class="btn-agregar" data-id="<?= $idProd ?>" data-nombre="<?= $nombre ?>" data-precio="<?= (float) $producto['precio'] ?>" <?= $agotado ? 'disabled' : '' ?>>
                                <span class="btn-agregar__texto"><?= $agotado ? 'Sin stock' : 'Agregar' ?></span>
                                <span class="btn-agregar__brillo"></span>
                            </button>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<div id="cart-overlay" class="cart-overlay"></div>
<aside id="cart-panel" class="cart-panel">
    <div class="cart-panel__header">
        <h2>Tu Carrito</h2>
        <button id="close-cart" class="cart-panel__close">&times;</button>
    </div>
    <div id="cart-items" class="cart-panel__items"></div>
    <div class="cart-panel__footer">
        <div class="cart-panel__total">
            <span>Total:</span>
            <span id="cart-total">$0.00 MXN</span>
        </div>
        <button class="cart-panel__checkout">Proceder al pago</button>
    </div>
</aside>

<div id="toast-container" aria-live="polite"></div>
<script src="../../js/catalogo.js" defer></script>
</body>
</html>