<?php


declare(strict_types=1);


$dbHost = 'localhost';
$dbName = 'kion_db';
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
    error_log('KION catalogo.php - Error de conexión: ' . $e->getMessage());
}


$categoriasDisponibles = ['Alimento', 'Medicamento', 'Accesorios', 'Higiene y Cuidado', 'Juguetes'];
$categoriaSlugs = [
    'Alimento'           => 'alimento',
    'Medicamento'        => 'medicamento',
    'Accesorios'         => 'accesorios',
    'Higiene y Cuidado'  => 'higiene',
    'Juguetes'           => 'juguetes',
];

$categoriaSeleccionada = null;
if (isset($_GET['categoria']) && in_array($_GET['categoria'], $categoriasDisponibles, true)) {
    $categoriaSeleccionada = $_GET['categoria'];
}


$productos    = [];
$errorConsulta = null;

if ($pdo !== null) {
    try {
       $sql = "SELECT id_producto, nombre, 'KION' as marca, 'Alimento' as categoria, precio, 10 as stock, imagen
                FROM productos
                WHERE 1=1";

        if ($categoriaSeleccionada !== null) {
            $sql .= " AND categoria = :categoria";
        }

        $sql .= " ORDER BY nombre ASC";

        $stmt = $pdo->prepare($sql);

        if ($categoriaSeleccionada !== null) {
            $stmt->bindValue(':categoria', $categoriaSeleccionada, PDO::PARAM_STR);
        }

        $stmt->execute();

        
        while ($fila = $stmt->fetch()) {
            $productos[] = $fila;
        }
    } catch (PDOException $e) {
        $errorConsulta = 'No pudimos cargar los productos en este momento.';
        error_log('KION catalogo.php - Error de consulta: ' . $e->getMessage());
    }
}


function formatearPrecio(float $precio): string
{
    return '$' . number_format($precio, 2) . ' MXN';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KION · Catálogo Veterinario Multiespecie</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../css/catalogo.css">
</head>
<body>

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

    <aside class="filtros" aria-label="Filtrar productos por categoría">
        <h2 class="filtros__titulo">Categorías</h2>
        <nav class="filtros__lista">
            <a href="catalogo.php"
               class="filtros__enlace<?= $categoriaSeleccionada === null ? ' filtros__enlace--activo' : '' ?>">
                Todos los productos
            </a>
            <?php foreach ($categoriasDisponibles as $cat): ?>
                <a href="?categoria=<?= urlencode($cat) ?>"
                   class="filtros__enlace filtros__enlace--<?= $categoriaSlugs[$cat] ?><?= $categoriaSeleccionada === $cat ? ' filtros__enlace--activo' : '' ?>">
                    <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="catalogo">
        <div class="catalogo__encabezado">
            <h1><?= $categoriaSeleccionada !== null ? htmlspecialchars($categoriaSeleccionada, ENT_QUOTES, 'UTF-8') : 'Todos los productos' ?></h1>
            <p class="catalogo__conteo"><?= count($productos) ?> producto(s)</p>
        </div>

        <?php if ($errorConexion !== null || $errorConsulta !== null): ?>
            <div class="estado-vacio">
                <p><?= htmlspecialchars($errorConexion ?? $errorConsulta, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="estado-vacio__ayuda">Revisa los datos de conexión en la parte superior de catalogo.php.</p>
            </div>

        <?php elseif (count($productos) === 0): ?>
            <div class="estado-vacio">
                <p>No hay productos que mostrar en esta categoría todavía.</p>
                <p class="estado-vacio__ayuda">Agrega productos a la tabla PRODUCTOS o elige otra categoría.</p>
            </div>

        <?php else: ?>
            <div class="grid-productos">
                <?php foreach ($productos as $producto):
                    $stock       = (int) $producto['stock'];
                    $agotado     = $stock <= 0;
                    $stockBajo   = !$agotado && $stock <= 5;
                    $slugCat     = $categoriaSlugs[$producto['categoria']] ?? 'otros';
                    $precioTexto = formatearPrecio((float) $producto['precio']);
                    $nombre      = htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8');
                ?>
                <article class="tarjeta">
                    <div class="tarjeta__imagen">
                        <span class="tarjeta__badge tarjeta__badge--<?= $slugCat ?>">
                            <?= htmlspecialchars($producto['categoria'], ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <?php if (!empty($producto['imagen'])): ?>
                            <img src="<?= htmlspecialchars($producto['imagen'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= $nombre ?>" loading="lazy">
                        <?php else: ?>
                            <div class="tarjeta__imagen-marcador" aria-hidden="true">
                                <svg viewBox="0 0 64 64" width="46" height="46" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path d="M20 26c-3-6-2-13 2-13s5 7 4 12" stroke-linecap="round"/>
                                    <path d="M44 26c3-6 2-13-2-13s-5 7-4 12" stroke-linecap="round"/>
                                    <path d="M14 34c0-3 3-6 6-6s5 2 6 2 3-2 6-2 6 3 6 6c1 6-3 9-3 13 0 5-6 8-12 8s-12-3-12-8c0-4-4-7-3-13z" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <?php if ($agotado): ?>
                            <span class="tarjeta__agotado">Agotado</span>
                        <?php endif; ?>
                    </div>

                    <div class="tarjeta__cuerpo">
                        <h3 class="tarjeta__titulo"><?= $nombre ?></h3>
                        <p class="tarjeta__marca"><?= htmlspecialchars($producto['marca'], ENT_QUOTES, 'UTF-8') ?></p>

                        <div class="tarjeta__pie">
                            <span class="tarjeta__precio"><?= $precioTexto ?></span>
                            <?php if ($stockBajo): ?>
                                <span class="tarjeta__stock-bajo">¡Solo quedan <?= $stock ?>!</span>
                            <?php endif; ?>
                        </div>

                        <button
                            type="button"
                            class="btn-agregar"
                            data-id="<?= (int) $producto['id_producto'] ?>"
                            data-nombre="<?= $nombre ?>"
                            data-precio="<?= (float) $producto['precio'] ?>"
                            <?= $agotado ? 'disabled' : '' ?>
                        >
                            <?= $agotado ? 'Agotado' : 'Agregar al carrito' ?>
                        </button>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<div id="toast-container" aria-live="polite"></div>

<script src="../../js/catalogo.js" defer></script>
</body>
</html>