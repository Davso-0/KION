<?php
declare(strict_types=1);

/*
  ------------------------------------------------------------------
  REQUISITO DE BASE DE DATOS (ejecutar una sola vez si no existe):

  CREATE TABLE IF NOT EXISTS cajas (
      id_caja INT AUTO_INCREMENT PRIMARY KEY,
      id_sucursal INT NOT NULL,
      nombre VARCHAR(60) NOT NULL,
      estado ENUM('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA',
      fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (id_sucursal) REFERENCES sucursales(id_sucursal)
  );
  ------------------------------------------------------------------
*/

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['usuario']) && !isset($_GET['action'])) {
    header('Location: ../../../../inicioSesion.php');
    exit;
}

if (file_exists(__DIR__ . '/../../config/conexion_BD.php')) {
    require_once __DIR__ . '/../../config/conexion_BD.php';
} elseif (file_exists(__DIR__ . '/../../config/conexion.php')) {
    require_once __DIR__ . '/../../config/conexion.php';
}

// --------------------------------------------------------------
// Resolver el usuario/gerente en sesión y su sucursal asignada
// --------------------------------------------------------------
$id_usuario_actual = 0;
if (is_array($_SESSION['usuario'] ?? null)) {
    $id_usuario_actual = (int)($_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? 0);
} else {
    $id_usuario_actual = (int)($_SESSION['usuario'] ?? 0);
}

$gerente = null;
if (isset($pdo) && $pdo instanceof PDO && $id_usuario_actual > 0) {
    $stG = $pdo->prepare("SELECT u.id_usuario, u.nombre, u.apellido, u.id_sucursal, r.nombre AS rol
                           FROM usuarios u LEFT JOIN roles r ON r.id_rol = u.id_rol
                           WHERE u.id_usuario = ?");
    $stG->execute([$id_usuario_actual]);
    $gerente = $stG->fetch(PDO::FETCH_ASSOC) ?: null;
}
$id_sucursal = (int)($gerente['id_sucursal'] ?? 0);
$nombreGerente = trim(($gerente['nombre'] ?? 'Gerente') . ' ' . ($gerente['apellido'] ?? ''));

// --------------------------------------------------------------
// API (AJAX) — todas las acciones quedan restringidas a $id_sucursal
// --------------------------------------------------------------
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $out = ['ok' => false, 'msg' => 'Acción desconocida'];
    try {
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            throw new RuntimeException('[ERROR DE SISTEMA] No fue posible conectar con la base de datos.');
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($id_sucursal <= 0) {
            throw new RuntimeException('[ERROR DE SISTEMA] Tu usuario no tiene una sucursal asignada. Contacta al administrador.');
        }

        if ($action === 'get_metrics') {
            $rv = $pdo->prepare("SELECT COUNT(*) n, COALESCE(SUM(total),0) t FROM ventas WHERE id_sucursal=? AND DATE(fecha_hora)=CURDATE()");
            $rv->execute([$id_sucursal]); $rv = $rv->fetch();
            $stB = $pdo->prepare("SELECT COUNT(*) FROM inventarios WHERE id_sucursal=? AND existencias<=stock_minimo");
            $stB->execute([$id_sucursal]); $bajo = (int)$stB->fetchColumn();
            $stP = $pdo->prepare("SELECT COUNT(*) FROM inventarios WHERE id_sucursal=?");
            $stP->execute([$id_sucursal]); $totalProd = (int)$stP->fetchColumn();
            $stC = $pdo->prepare("SELECT COUNT(*) FROM cajas WHERE id_sucursal=? AND estado='ACTIVA'");
            $stC->execute([$id_sucursal]); $cajasActivas = (int)$stC->fetchColumn();

            $labels = []; $ingresos = [];
            for ($i = 6; $i >= 0; $i--) {
                $fd = date('Y-m-d', strtotime("-{$i} days"));
                $fl = date('d/m', strtotime("-{$i} days"));
                $r = $pdo->prepare("SELECT COALESCE(SUM(total),0) t FROM ventas WHERE id_sucursal=? AND DATE(fecha_hora)=?");
                $r->execute([$id_sucursal, $fd]);
                $labels[] = $fl; $ingresos[] = (float)$r->fetchColumn();
            }

            $out = ['ok' => true, 'data' => [
                'ih' => (float)($rv['t'] ?? 0), 'vh' => (int)($rv['n'] ?? 0),
                'bajo' => $bajo, 'totalProd' => $totalProd, 'cajasActivas' => $cajasActivas,
                'labels' => $labels, 'ingresos' => $ingresos
            ]];

        } elseif ($action === 'get_sucursal') {
            $st = $pdo->prepare("SELECT id_sucursal, nombre, direccion, telefono, contacto, estado FROM sucursales WHERE id_sucursal=?");
            $st->execute([$id_sucursal]);
            $out = ['ok' => true, 'data' => $st->fetch(PDO::FETCH_ASSOC) ?: null];

        } elseif ($action === 'get_categorias') {
            try { $data = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) { $data = []; }
            $out = ['ok' => true, 'data' => $data];

        } elseif ($action === 'get_metodos_pago') {
            try { $data = $pdo->query("SELECT id_metodo_pago, nombre FROM metodos_pago ORDER BY id_metodo_pago")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) { $data = []; }
            $out = ['ok' => true, 'data' => $data];

        } elseif ($action === 'get_productos') {
            $q = trim($_GET['q'] ?? '');
            $sql = "SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.id_categoria, p.precio, p.estado,
                           COALESCE(c.nombre,'Sin categoría') AS categoria, i.existencias, i.stock_minimo
                    FROM productos p
                    JOIN inventarios i ON i.id_producto=p.id_producto AND i.id_sucursal=?
                    LEFT JOIN categorias c ON c.id_categoria=p.id_categoria
                    WHERE 1=1";
            $params = [$id_sucursal];
            if ($q !== '') { $sql .= " AND (p.codigo LIKE ? OR p.nombre LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
            $sql .= " ORDER BY p.nombre ASC LIMIT 200";
            $st = $pdo->prepare($sql); $st->execute($params);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'create_producto') {
            $codigo = trim($_POST['codigo'] ?? ''); $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? ''); $categoria = (int)($_POST['id_categoria'] ?? 0);
            $precio = (float)str_replace(',', '.', $_POST['precio'] ?? '0');
            $existInicial = max(0, (int)($_POST['existencias'] ?? 0));
            $stockMin = max(0, (int)($_POST['stock_minimo'] ?? 5));
            if ($codigo === '' || $nombre === '') throw new Exception('[ERROR DE USUARIO] El código y el nombre son obligatorios.');

            $pdo->beginTransaction();
            try {
                $pdo->prepare("INSERT INTO productos(codigo,nombre,descripcion,id_categoria,precio,estado) VALUES(?,?,?,?,?,'ACTIVO')")
                    ->execute([$codigo, $nombre, $descripcion ?: null, $categoria > 0 ? $categoria : null, $precio]);
                $newId = (int)$pdo->lastInsertId();
                $pdo->prepare("INSERT INTO inventarios(id_producto,id_sucursal,existencias,stock_minimo) VALUES(?,?,?,?)")
                    ->execute([$newId, $id_sucursal, $existInicial, $stockMin]);
                $pdo->commit();
                $out = ['ok' => true, 'msg' => 'Producto registrado y asignado a tu sucursal.'];
            } catch (Exception $e) { $pdo->rollBack(); throw $e; }

        } elseif ($action === 'update_producto') {
            $id = (int)($_POST['id'] ?? 0);
            $codigo = trim($_POST['codigo'] ?? ''); $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? ''); $categoria = (int)($_POST['id_categoria'] ?? 0);
            $precio = (float)str_replace(',', '.', $_POST['precio'] ?? '0');
            $estado = ($_POST['estado'] ?? 'ACTIVO') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO';
            $stockMin = max(0, (int)($_POST['stock_minimo'] ?? 5));
            if (!$id || $codigo === '' || $nombre === '') throw new Exception('[ERROR DE USUARIO] Faltan datos obligatorios.');

            $chk = $pdo->prepare("SELECT COUNT(*) FROM inventarios WHERE id_producto=? AND id_sucursal=?");
            $chk->execute([$id, $id_sucursal]);
            if (!$chk->fetchColumn()) throw new Exception('[ERROR DE USUARIO] Este producto no pertenece a tu sucursal.');

            $pdo->prepare("UPDATE productos SET codigo=?,nombre=?,descripcion=?,id_categoria=?,precio=?,estado=? WHERE id_producto=?")
                ->execute([$codigo, $nombre, $descripcion ?: null, $categoria > 0 ? $categoria : null, $precio, $estado, $id]);
            $pdo->prepare("UPDATE inventarios SET stock_minimo=? WHERE id_producto=? AND id_sucursal=?")
                ->execute([$stockMin, $id, $id_sucursal]);
            $out = ['ok' => true, 'msg' => 'Producto actualizado correctamente.'];

        } elseif ($action === 'get_inventario') {
            $q = trim($_GET['q'] ?? ''); $soloBajo = ($_GET['solo_bajo'] ?? '') === '1';
            $sql = "SELECT i.id_inventario, p.id_producto, p.nombre AS producto, p.codigo, i.existencias, i.stock_minimo, p.precio,
                           CASE WHEN i.existencias<=0 THEN 'AGOTADO' WHEN i.existencias<=i.stock_minimo THEN 'BAJO' ELSE 'OK' END AS nivel
                    FROM inventarios i JOIN productos p ON p.id_producto=i.id_producto
                    WHERE i.id_sucursal=?";
            $params = [$id_sucursal];
            if ($q !== '') { $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
            if ($soloBajo) { $sql .= " AND i.existencias<=i.stock_minimo"; }
            $sql .= " ORDER BY i.existencias ASC LIMIT 200";
            $st = $pdo->prepare($sql); $st->execute($params);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'get_productos_simple') {
            $st = $pdo->prepare("SELECT p.id_producto, p.nombre, p.codigo FROM productos p JOIN inventarios i ON i.id_producto=p.id_producto AND i.id_sucursal=? WHERE p.estado='ACTIVO' ORDER BY p.nombre");
            $st->execute([$id_sucursal]);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'ajustar_stock') {
            $id_p = (int)($_POST['id_producto'] ?? 0);
            $cant = (int)($_POST['cantidad'] ?? 0);
            $tipo = $_POST['tipo'] ?? '';
            if (!$id_p) throw new Exception('[ERROR DE USUARIO] Selecciona un producto válido.');
            if ($cant < 0) throw new Exception('[ERROR DE USUARIO] No puedes ingresar cantidades negativas.');

            $st = $pdo->prepare("SELECT existencias FROM inventarios WHERE id_producto=? AND id_sucursal=?");
            $st->execute([$id_p, $id_sucursal]);
            $row = $st->fetch();
            if (!$row) throw new Exception("[ERROR DE USUARIO] Ese producto no está asignado a tu sucursal.");

            $nueva = (int)$row['existencias'];
            if ($tipo === 'entrada') $nueva += $cant;
            elseif ($tipo === 'salida') $nueva = max(0, $nueva - $cant);
            elseif ($tipo === 'reemplazo') $nueva = $cant;

            $pdo->prepare("UPDATE inventarios SET existencias=? WHERE id_producto=? AND id_sucursal=?")->execute([$nueva, $id_p, $id_sucursal]);
            $out = ['ok' => true, 'msg' => "Stock actualizado. Nueva existencia: $nueva unidades."];

        } elseif ($action === 'get_cajas') {
            $st = $pdo->prepare("SELECT id_caja, nombre, estado, fecha_registro FROM cajas WHERE id_sucursal=? ORDER BY id_caja");
            $st->execute([$id_sucursal]);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'get_cajas_activas') {
            $st = $pdo->prepare("SELECT id_caja, nombre FROM cajas WHERE id_sucursal=? AND estado='ACTIVA' ORDER BY nombre");
            $st->execute([$id_sucursal]);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'create_caja') {
            $nombre = trim($_POST['nombre'] ?? '');
            if ($nombre === '') throw new Exception('[ERROR DE USUARIO] El nombre de la caja es obligatorio.');
            $pdo->prepare("INSERT INTO cajas(id_sucursal,nombre,estado) VALUES(?,?,'ACTIVA')")->execute([$id_sucursal, $nombre]);
            $out = ['ok' => true, 'msg' => 'Caja registrada correctamente.'];

        } elseif ($action === 'delete_caja') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('[ERROR DE SISTEMA] ID de caja inválido.');
            $chk = $pdo->prepare("SELECT COUNT(*) FROM cajas WHERE id_caja=? AND id_sucursal=?");
            $chk->execute([$id, $id_sucursal]);
            if (!$chk->fetchColumn()) throw new Exception('[ERROR DE USUARIO] Esa caja no pertenece a tu sucursal.');
            $pdo->prepare("DELETE FROM cajas WHERE id_caja=?")->execute([$id]);
            $out = ['ok' => true, 'msg' => 'Caja eliminada.'];

        } elseif ($action === 'get_ventas') {
            $desde = trim($_GET['desde'] ?? ''); $hasta = trim($_GET['hasta'] ?? '');
            $sql = "SELECT v.id_venta, v.fecha_hora, v.total, v.estado, mp.nombre AS metodo_pago,
                           CONCAT(u.nombre,' ',COALESCE(u.apellido,'')) AS empleado
                    FROM ventas v
                    JOIN metodos_pago mp ON mp.id_metodo_pago=v.id_metodo_pago
                    JOIN usuarios u ON u.id_usuario=v.id_usuario
                    WHERE v.id_sucursal=?";
            $params = [$id_sucursal];
            if ($desde !== '') { $sql .= " AND DATE(v.fecha_hora)>=?"; $params[] = $desde; }
            if ($hasta !== '') { $sql .= " AND DATE(v.fecha_hora)<=?"; $params[] = $hasta; }
            $sql .= " ORDER BY v.fecha_hora DESC LIMIT 200";
            $st = $pdo->prepare($sql); $st->execute($params);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'get_venta_detalle') {
            $id = (int)($_GET['id'] ?? 0);
            $chk = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE id_venta=? AND id_sucursal=?");
            $chk->execute([$id, $id_sucursal]);
            if (!$chk->fetchColumn()) throw new Exception('[ERROR DE USUARIO] Esa venta no pertenece a tu sucursal.');
            $st = $pdo->prepare("SELECT dv.cantidad, dv.precio_unitario, dv.subtotal, COALESCE(p.nombre,'Producto no disponible') AS producto
                                  FROM detalle_ventas dv LEFT JOIN productos p ON p.id_producto=dv.id_producto WHERE dv.id_venta=?");
            $st->execute([$id]);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'buscar_producto_pos') {
            $codigo = trim($_GET['codigo'] ?? '');
            if ($codigo === '') throw new Exception('[ERROR DE USUARIO] Ingresa un código.');
            $st = $pdo->prepare("SELECT p.id_producto, p.codigo, p.nombre, p.precio, i.existencias
                                  FROM productos p JOIN inventarios i ON i.id_producto=p.id_producto AND i.id_sucursal=?
                                  WHERE p.codigo=? AND p.estado='ACTIVO' LIMIT 1");
            $st->execute([$id_sucursal, $codigo]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception('[ERROR DE USUARIO] Producto no encontrado en tu sucursal.');
            if ($row['existencias'] <= 0) throw new Exception('[ERROR DE USUARIO] "' . $row['nombre'] . '" está agotado en tu sucursal.');
            $out = ['ok' => true, 'data' => $row];

        } elseif ($action === 'procesar_venta') {
            $carrito = json_decode($_POST['carrito'] ?? '[]', true);
            $idMetodo = (int)($_POST['id_metodo_pago'] ?? 0);
            if (!is_array($carrito) || count($carrito) === 0) throw new Exception('[ERROR DE USUARIO] El carrito está vacío.');
            if (!$idMetodo) throw new Exception('[ERROR DE USUARIO] Selecciona un método de pago.');

            $pdo->beginTransaction();
            try {
                $total = 0; $lineas = [];
                $stStock = $pdo->prepare("SELECT i.existencias, p.precio, p.nombre FROM inventarios i JOIN productos p ON p.id_producto=i.id_producto WHERE i.id_producto=? AND i.id_sucursal=?");
                foreach ($carrito as $item) {
                    $idP = (int)($item['id_producto'] ?? 0); $cant = (int)($item['cantidad'] ?? 0);
                    if ($cant <= 0 || !$idP) continue;
                    $stStock->execute([$idP, $id_sucursal]);
                    $row = $stStock->fetch();
                    if (!$row) throw new Exception('[ERROR DE USUARIO] Un producto del carrito ya no está disponible.');
                    if ((int)$row['existencias'] < $cant) throw new Exception('[ERROR DE USUARIO] Stock insuficiente para "' . $row['nombre'] . '". Disponible: ' . $row['existencias']);
                    $subtotal = (float)$row['precio'] * $cant;
                    $total += $subtotal;
                    $lineas[] = ['id_producto' => $idP, 'cantidad' => $cant, 'precio_unitario' => $row['precio'], 'subtotal' => $subtotal];
                }
                if (empty($lineas)) throw new Exception('[ERROR DE USUARIO] No hay productos válidos en el carrito.');

                $pdo->prepare("INSERT INTO ventas(fecha_hora,total,estado,id_sucursal,id_metodo_pago,id_usuario) VALUES(NOW(),?,'COMPLETADA',?,?,?)")
                    ->execute([$total, $id_sucursal, $idMetodo, $id_usuario_actual]);
                $idVenta = (int)$pdo->lastInsertId();

                $stDet = $pdo->prepare("INSERT INTO detalle_ventas(id_venta,id_producto,cantidad,precio_unitario,subtotal) VALUES(?,?,?,?,?)");
                $stUpd = $pdo->prepare("UPDATE inventarios SET existencias=existencias-? WHERE id_producto=? AND id_sucursal=?");
                foreach ($lineas as $l) {
                    $stDet->execute([$idVenta, $l['id_producto'], $l['cantidad'], $l['precio_unitario'], $l['subtotal']]);
                    $stUpd->execute([$l['cantidad'], $l['id_producto'], $id_sucursal]);
                }
                $pdo->commit();
                $out = ['ok' => true, 'msg' => 'Venta registrada correctamente.', 'data' => ['id_venta' => $idVenta, 'total' => $total]];
            } catch (Exception $e) { $pdo->rollBack(); throw $e; }
        }
    } catch (Throwable $e) {
        $out = ['ok' => false, 'msg' => $e->getMessage()];
    }
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

// --------------------------------------------------------------
// Render de la página
// --------------------------------------------------------------
if ($id_sucursal <= 0) {
    ob_end_clean();
    echo '<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;padding:60px;text-align:center;color:#6c4630;">
    <h2>Tu usuario no tiene una sucursal asignada</h2><p>Contacta al administrador para que te asigne una sucursal.</p></body>';
    exit;
}
$stSuc = $pdo->prepare("SELECT nombre FROM sucursales WHERE id_sucursal=?");
$stSuc->execute([$id_sucursal]);
$nombreSucursal = $stSuc->fetchColumn() ?: 'Sucursal';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KION · Panel de Gerente</title>

    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --bg-app: oklch(96.5% 0.014 72); --bg-surface: oklch(100% 0 0); --bg-panel: oklch(99% 0.006 75);
            --ink: oklch(24% 0.028 50); --ink-soft: oklch(42% 0.030 48); --border-soft: oklch(89% 0.016 65);
            --coffee-main: oklch(32% 0.040 50); --coffee-light: oklch(91% 0.018 60);
            --coffee-gradient: linear-gradient(155deg, oklch(40% 0.045 48), oklch(26% 0.035 52));
            --success: oklch(34% 0.06 152); --success-bg: oklch(94% 0.035 152);
            --danger: oklch(50% 0.14 25); --danger-bg: oklch(95% 0.035 20);
            --warning: oklch(42% 0.09 70); --warning-bg: oklch(95% 0.045 90);
            --font-display: 'Fraunces', serif; --font-body: 'Plus Jakarta Sans', sans-serif;
            --shadow-sm: 0 4px 12px rgba(0,0,0,0.05); --shadow-md: 0 8px 24px rgba(0,0,0,0.08);
            --radius: 14px; --sidebar-w: 260px;
        }
        body.dark-mode {
            --bg-app: oklch(20% 0.01 250); --bg-surface: oklch(25% 0.01 250); --bg-panel: oklch(23% 0.01 250);
            --ink: oklch(95% 0.01 250); --ink-soft: oklch(75% 0.01 250); --border-soft: oklch(35% 0.01 250);
            --coffee-main: oklch(80% 0.03 60); --coffee-light: oklch(30% 0.02 250);
            --coffee-gradient: linear-gradient(155deg, oklch(40% 0.045 48), oklch(20% 0.035 52));
            --success-bg: oklch(25% 0.04 152); --danger-bg: oklch(30% 0.06 20); --warning-bg: oklch(30% 0.05 85);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--font-body); background: var(--bg-app); color: var(--ink); transition: background 0.3s, color 0.3s; }
        button { font: inherit; cursor: pointer; border: none; background: none; color: inherit; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .view-section { display: none; animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .view-section.active { display: block; }

        .app-layout { display: grid; grid-template-columns: var(--sidebar-w) 1fr; min-height: 100vh; }
        .sidebar { background: var(--bg-surface); border-right: 1px solid var(--border-soft); padding: 24px 16px; display: flex; flex-direction: column; gap: 30px; }
        .brand { display: flex; align-items: center; gap: 12px; font-family: var(--font-display); font-size: 20px; font-weight: 600; padding: 0 10px; }
        .brand i { font-size: 28px; color: var(--coffee-main); }
        .nav-menu { display: flex; flex-direction: column; gap: 8px; }
        .nav-btn { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: var(--radius); font-weight: 600; color: var(--ink-soft); transition: all 0.2s; }
        .nav-btn i { font-size: 20px; }
        .nav-btn:hover { background: var(--bg-app); color: var(--ink); }
        .nav-btn.active { background: var(--coffee-main); color: var(--bg-surface); }

        .topbar { height: 70px; display: flex; justify-content: space-between; align-items: center; padding: 0 32px; border-bottom: 1px solid var(--border-soft); background: var(--bg-panel); }
        .topbar-actions { display: flex; gap: 16px; align-items: center; }
        .icon-btn { width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center; background: var(--bg-app); border: 1px solid var(--border-soft); font-size: 20px; transition: 0.2s; }
        .icon-btn:hover { background: var(--coffee-light); color: var(--coffee-main); }
        .user-profile { display: flex; align-items: center; gap: 10px; font-weight: 600; padding-left: 16px; border-left: 1px solid var(--border-soft); }

        .content { padding: 32px; max-width: 1400px; margin: 0 auto; }
        .header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .title { font-family: var(--font-display); font-size: 28px; font-weight: 600; }
        .subtitle { color: var(--ink-soft); font-size: 14px; margin-top: 4px; }

        .btn-primary { background: var(--coffee-gradient); color: #fff; padding: 10px 20px; border-radius: 8px; font-weight: 600; box-shadow: var(--shadow-sm); transition: transform 0.2s; display:inline-flex; align-items:center; gap:8px; }
        .btn-primary:active { transform: scale(0.96); }
        .btn-danger { background: var(--danger); color: #fff; padding: 10px 20px; border-radius: 8px; font-weight: 600; }

        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .kpi-card { background: var(--bg-surface); padding: 24px; border-radius: var(--radius); border: 1px solid var(--border-soft); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 12px; }
        .kpi-card i { font-size: 28px; color: var(--coffee-main); padding: 12px; background: var(--coffee-light); border-radius: 10px; width: fit-content; }
        .kpi-val { font-family: var(--font-display); font-size: 32px; font-weight: 600; }

        .panel { background: var(--bg-surface); border-radius: var(--radius); border: 1px solid var(--border-soft); padding: 24px; overflow-x: auto; margin-bottom:24px; }
        .grid-2col { display:grid; grid-template-columns: 1.4fr 1fr; gap:24px; }
        .chart-box { position:relative; height:260px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { font-size: 12px; text-transform: uppercase; color: var(--ink-soft); padding-bottom: 16px; border-bottom: 1px solid var(--border-soft); }
        td { padding: 16px 0; border-bottom: 1px solid var(--border-soft); font-weight: 500; }
        tr:last-child td { border: none; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .badge.b-red { background: var(--danger-bg); color: var(--danger); }
        .badge.b-green { background: var(--success-bg); color: var(--success); }
        .badge.b-yellow { background: var(--warning-bg); color: var(--warning); }
        .chip-btn { border:1px solid var(--border-soft); background:var(--bg-app); color:var(--coffee-main); font-size:12px; font-weight:700; padding:6px 12px; border-radius:8px; }
        .chip-btn:hover { background:var(--coffee-main); color:#fff; }
        .icon-x { width:30px; height:30px; border-radius:8px; border:1px solid var(--border-soft); display:inline-grid; place-items:center; color:var(--ink-soft); }
        .icon-x:hover { background:var(--danger-bg); color:var(--danger); }
        .form-inline { display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
        .search-input { padding:10px 16px; border:1px solid var(--border-soft); border-radius:8px; background:var(--bg-app); color:var(--ink); min-width:220px; }
        .info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; }
        .info-item span { display:block; color:var(--ink-soft); font-size:12px; text-transform:uppercase; margin-bottom:6px; }
        .info-item b { font-size:18px; }

        .pos-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
        .pos-scanner { background: var(--bg-surface); padding: 24px; border-radius: var(--radius); border: 1px solid var(--border-soft); margin-bottom: 20px; display: flex; gap: 16px; }
        .pos-scanner input { flex: 1; padding: 14px 20px; font-size: 16px; border: 2px solid var(--border-soft); border-radius: 8px; background: var(--bg-app); color: var(--ink); outline: none; transition: border 0.3s; }
        .pos-scanner input:focus { border-color: var(--coffee-main); }
        .pos-cart { background: var(--bg-surface); border-radius: var(--radius); border: 1px solid var(--border-soft); min-height: 400px; display: flex; flex-direction: column; }
        .pos-summary { background: var(--coffee-main); color: #fff; border-radius: var(--radius); padding: 32px 24px; display: flex; flex-direction: column; gap: 20px; }
        .summary-row { display: flex; justify-content: space-between; font-size: 16px; font-weight: 500; opacity: 0.9; }
        .summary-total { font-family: var(--font-display); font-size: 42px; font-weight: 700; margin: 10px 0 20px; text-align: right; }
        .pay-btn { background: #fff; color: var(--coffee-main); width: 100%; padding: 16px; border-radius: 8px; font-size: 18px; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.2s; }
        .pay-btn:active { transform: scale(0.97); }
        .pay-select { width:100%; padding:12px; border-radius:8px; border:none; font-weight:600; color:var(--coffee-main); }

        .dark-mode .swal2-popup { background: var(--bg-surface); color: var(--ink); }
        .dark-mode .swal2-input, .dark-mode .swal2-select, .dark-mode .swal2-textarea { background: var(--bg-app); color: var(--ink); border-color: var(--border-soft); }
        @media (max-width: 980px){ .grid-2col{ grid-template-columns:1fr; } .pos-layout{ grid-template-columns:1fr; } }
    </style>
</head>
<body>

<div class="app-layout">
    <aside class="sidebar">
        <div class="brand"><i class="ph-fill ph-paw-print"></i> KION Gerencia</div>
        <nav class="nav-menu">
            <button class="nav-btn active" data-target="view-dash"><i class="ph ph-squares-four"></i> Panel Principal</button>
            <button class="nav-btn" data-target="view-sucursal"><i class="ph ph-storefront"></i> Mi Sucursal</button>
            <button class="nav-btn" data-target="view-inventario"><i class="ph ph-package"></i> Inventario</button>
            <button class="nav-btn" data-target="view-productos"><i class="ph ph-tag"></i> Productos</button>
            <button class="nav-btn" data-target="view-cajas"><i class="ph ph-desktop"></i> Cajas</button>
            <button class="nav-btn" data-target="view-ventas"><i class="ph ph-chart-line-up"></i> Ventas</button>
            <hr style="border:none; border-top:1px solid var(--border-soft); margin: 10px 0;">
            <button class="nav-btn" id="btnIrPOS"><i class="ph ph-monitor"></i> Punto de Venta</button>
        </nav>
    </aside>

    <main>
        <header class="topbar">
            <div><b id="branchName">📍 Sucursal: <?= htmlspecialchars($nombreSucursal) ?></b></div>
            <div class="topbar-actions">
                <button class="icon-btn" id="btnTheme" title="Modo Oscuro"><i class="ph ph-moon"></i></button>
                <div class="user-profile">
                    <i class="ph-fill ph-user-circle" style="font-size:24px; color:var(--coffee-main)"></i>
                    <span><?= htmlspecialchars($nombreGerente) ?></span>
                </div>
            </div>
        </header>

        <div class="content">

            <!-- PANEL PRINCIPAL -->
            <section id="view-dash" class="view-section active">
                <div class="header-row"><div><h1 class="title">Resumen de Operaciones</h1><p class="subtitle">Métricas en tiempo real de tu sucursal.</p></div></div>
                <div class="kpi-grid">
                    <div class="kpi-card"><i class="ph-fill ph-money"></i><span style="color:var(--ink-soft); font-weight:600;">Ingresos Hoy</span><div class="kpi-val" id="kpiIngresos">$0.00</div></div>
                    <div class="kpi-card"><i class="ph-fill ph-warning-circle" style="color:var(--danger)"></i><span style="color:var(--ink-soft); font-weight:600;">Stock Bajo</span><div class="kpi-val" id="kpiBajo" style="color:var(--danger)">0</div></div>
                    <div class="kpi-card"><i class="ph-fill ph-desktop"></i><span style="color:var(--ink-soft); font-weight:600;">Cajas Activas</span><div class="kpi-val" id="kpiCajas">0</div></div>
                    <div class="kpi-card"><i class="ph-fill ph-package"></i><span style="color:var(--ink-soft); font-weight:600;">Productos Asignados</span><div class="kpi-val" id="kpiProductos">0</div></div>
                </div>
                <div class="grid-2col">
                    <div class="panel"><h3 style="margin-bottom:16px;">Ventas (7 días)</h3><div class="chart-box"><canvas id="salesChart"></canvas></div></div>
                    <div class="panel"><h3>Alertas de Inventario Bajo</h3><table style="margin-top:16px;" id="tblAlertas"><tr><th>Producto</th><th>Existencia</th><th>Estado</th></tr></table></div>
                </div>
            </section>

            <!-- MI SUCURSAL -->
            <section id="view-sucursal" class="view-section">
                <div class="header-row"><div><h1 class="title">Mi Sucursal</h1><p class="subtitle">Información general de tu punto de venta.</p></div></div>
                <div class="panel"><div class="info-grid" id="infoSucursal">Cargando...</div></div>
            </section>

            <!-- INVENTARIO -->
            <section id="view-inventario" class="view-section">
                <div class="header-row">
                    <div><h1 class="title">Inventario</h1><p class="subtitle">Consulta y actualiza existencias de tu sucursal.</p></div>
                    <button class="btn-primary" id="btnAjustarStock"><i class="ph ph-plus"></i> Actualizar Existencias</button>
                </div>
                <div class="panel">
                    <div class="form-inline" style="margin-bottom:16px;">
                        <input class="search-input" id="invSearch" placeholder="Buscar producto o código...">
                        <label style="display:flex; align-items:center; gap:6px; font-weight:600; font-size:14px;"><input type="checkbox" id="invSoloBajo"> Solo bajo stock</label>
                    </div>
                    <table><thead><tr><th>Producto</th><th>Código</th><th>Existencia</th><th>Precio</th><th>Nivel</th></tr></thead><tbody id="tblInventario"></tbody></table>
                </div>
            </section>

            <!-- PRODUCTOS -->
            <section id="view-productos" class="view-section">
                <div class="header-row">
                    <div><h1 class="title">Productos</h1><p class="subtitle">Registra, consulta y modifica productos de tu sucursal.</p></div>
                    <button class="btn-primary" id="btnNuevoProducto"><i class="ph ph-plus"></i> Nuevo Producto</button>
                </div>
                <div class="panel">
                    <div class="form-inline" style="margin-bottom:16px;"><input class="search-input" id="prodSearch" placeholder="Buscar producto o código..."></div>
                    <table><thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th></th></tr></thead><tbody id="tblProductos"></tbody></table>
                </div>
            </section>

            <!-- CAJAS -->
            <section id="view-cajas" class="view-section">
                <div class="header-row">
                    <div><h1 class="title">Cajas</h1><p class="subtitle">Registra o elimina terminales de tu sucursal.</p></div>
                    <button class="btn-primary" id="btnNuevaCaja"><i class="ph ph-plus"></i> Nueva Caja</button>
                </div>
                <div class="panel"><table><thead><tr><th>Nombre</th><th>Estado</th><th>Registrada</th><th></th></tr></thead><tbody id="tblCajas"></tbody></table></div>
            </section>

            <!-- VENTAS -->
            <section id="view-ventas" class="view-section">
                <div class="header-row"><div><h1 class="title">Historial de Ventas</h1><p class="subtitle">Supervisa las ventas realizadas en tu sucursal.</p></div></div>
                <div class="panel">
                    <div class="form-inline" style="margin-bottom:16px;">
                        <input class="search-input" type="date" id="ventasDesde"><input class="search-input" type="date" id="ventasHasta">
                    </div>
                    <table><thead><tr><th>Folio</th><th>Fecha</th><th>Personal</th><th>Método</th><th>Total</th><th>Estado</th><th></th></tr></thead><tbody id="tblVentas"></tbody></table>
                </div>
            </section>

            <!-- PUNTO DE VENTA -->
            <section id="view-pos" class="view-section">
                <div class="header-row" style="margin-bottom: 12px;">
                    <div><h1 class="title">Caja <span id="lblCajaActual">-</span></h1><p class="subtitle">Cajero: <b id="lblCajeroActual"><?= htmlspecialchars($nombreGerente) ?></b></p></div>
                    <button class="btn-danger" id="btnCerrarCaja"><i class="ph ph-sign-out"></i> Cerrar Turno</button>
                </div>
                <div class="pos-layout">
                    <div>
                        <div class="pos-scanner">
                            <i class="ph ph-barcode" style="font-size:32px; color:var(--coffee-main); align-self:center;"></i>
                            <input type="text" id="posBarcode" placeholder="Escanea o ingresa el código del producto..." autocomplete="off">
                        </div>
                        <div class="pos-cart panel" style="padding:0;">
                            <table style="width:100%; margin:0;">
                                <thead><tr><th style="padding:16px 24px;">Producto</th><th style="text-align:center;">Cant.</th><th style="text-align:right;">Precio</th><th style="text-align:right; padding-right:24px;">Subtotal</th></tr></thead>
                                <tbody id="cartBody"><tr><td colspan="4" style="text-align:center; padding: 40px; color:var(--ink-soft);">El carrito está vacío. Escanea un producto.</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="pos-summary">
                        <h2>Resumen de Venta</h2>
                        <select class="pay-select" id="posMetodo"><option value="">Cargando métodos de pago...</option></select>
                        <div style="flex:1;"></div>
                        <div class="summary-row"><span>Subtotal:</span><span id="posSubtotal">$0.00</span></div>
                        <div class="summary-row"><span>IVA (16%):</span><span id="posIva">$0.00</span></div>
                        <hr style="border:1px solid rgba(255,255,255,0.2)">
                        <div style="font-size:18px; font-weight:600; opacity:0.9;">Total a Pagar</div>
                        <div class="summary-total" id="posTotal">$0.00</div>
                        <button class="pay-btn" id="btnCobrar"><i class="ph-fill ph-credit-card"></i> PROCESAR COBRO</button>
                    </div>
                </div>
            </section>

        </div>
    </main>
</div>

<script>
const endpoint = 'dashboard_gerente.php';
const $ = s => document.querySelector(s);
const escapeHtml = v => String(v ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
const money = v => '$' + Number(v || 0).toFixed(2);

const notify = (msg, icon='success') => Swal.fire({ toast:true, position:'top-end', icon, title: msg, showConfirmButton:false, timer:2500 });

async function request(actionStr, options = {}) {
    const r = await fetch(`${endpoint}?action=${actionStr}`, options);
    const text = await r.text();
    let data;
    try { data = JSON.parse(text); } catch(e) { throw new Error('[ERROR DE SISTEMA] Respuesta inválida del servidor.'); }
    if (!data.ok) throw new Error(data.msg || 'No fue posible completar la operación.');
    return data;
}

// -------- TEMA --------
const body = document.body, btnTheme = $('#btnTheme');
btnTheme.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    const icon = btnTheme.querySelector('i');
    icon.classList.toggle('ph-moon'); icon.classList.toggle('ph-sun');
});

// -------- NAVEGACIÓN --------
const navButtons = document.querySelectorAll('.nav-btn[data-target]');
const views = document.querySelectorAll('.view-section');
function switchView(targetId) {
    views.forEach(v => v.classList.remove('active'));
    navButtons.forEach(b => b.classList.remove('active'));
    document.getElementById(targetId).classList.add('active');
    const btn = document.querySelector(`.nav-btn[data-target="${targetId}"]`);
    if (btn) btn.classList.add('active');
    if (targetId === 'view-dash') loadDashboard();
    if (targetId === 'view-sucursal') loadSucursal();
    if (targetId === 'view-inventario') loadInventario();
    if (targetId === 'view-productos') loadProductos();
    if (targetId === 'view-cajas') loadCajas();
    if (targetId === 'view-ventas') loadVentas();
}
navButtons.forEach(btn => btn.addEventListener('click', e => switchView(e.currentTarget.dataset.target)));

// -------- DASHBOARD --------
let salesChartInstance = null;
async function loadDashboard() {
    try {
        const { data } = await request('get_metrics');
        $('#kpiIngresos').innerText = money(data.ih);
        $('#kpiBajo').innerText = data.bajo;
        $('#kpiCajas').innerText = data.cajasActivas;
        $('#kpiProductos').innerText = data.totalProd;

        if (salesChartInstance) salesChartInstance.destroy();
        salesChartInstance = new Chart($('#salesChart'), {
            type: 'line',
            data: { labels: data.labels, datasets: [{ label: 'Ingresos', data: data.ingresos, borderColor:'#6c4630', backgroundColor:'rgba(147,101,69,.14)', fill:true, tension:.38, pointRadius:3 }] },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
        });

        const { data: inv } = await request('get_inventario&solo_bajo=1');
        const rows = inv.slice(0, 6).map(x => {
            const cls = x.nivel === 'AGOTADO' ? 'b-red' : 'b-yellow';
            return `<tr><td>${escapeHtml(x.producto)}</td><td><b>${x.existencias}</b> / ${x.stock_minimo}</td><td><span class="badge ${cls}">${escapeHtml(x.nivel)}</span></td></tr>`;
        }).join('');
        $('#tblAlertas').innerHTML = `<tr><th>Producto</th><th>Existencia</th><th>Estado</th></tr>` + (rows || `<tr><td colspan="3" style="text-align:center;color:var(--ink-soft);padding:20px">Sin alertas.</td></tr>`);
    } catch (e) { notify(e.message, 'error'); }
}

// -------- MI SUCURSAL --------
async function loadSucursal() {
    try {
        const { data } = await request('get_sucursal');
        if (!data) { $('#infoSucursal').innerHTML = 'No encontrada.'; return; }
        $('#infoSucursal').innerHTML = `
            <div class="info-item"><span>Nombre</span><b>${escapeHtml(data.nombre)}</b></div>
            <div class="info-item"><span>Dirección</span><b>${escapeHtml(data.direccion || '-')}</b></div>
            <div class="info-item"><span>Teléfono</span><b>${escapeHtml(data.telefono || '-')}</b></div>
            <div class="info-item"><span>Contacto</span><b>${escapeHtml(data.contacto || '-')}</b></div>
            <div class="info-item"><span>Estado</span><span class="badge ${data.estado==='ACTIVA'?'b-green':'b-red'}">${escapeHtml(data.estado)}</span></div>`;
    } catch (e) { notify(e.message, 'error'); }
}

// -------- INVENTARIO --------
async function loadInventario() {
    try {
        const q = $('#invSearch').value, solo = $('#invSoloBajo').checked ? '1' : '0';
        const { data } = await request(`get_inventario&q=${encodeURIComponent(q)}&solo_bajo=${solo}`);
        $('#tblInventario').innerHTML = data.length ? data.map(x => {
            const cls = x.nivel === 'OK' ? 'b-green' : (x.nivel === 'BAJO' ? 'b-yellow' : 'b-red');
            return `<tr><td><b>${escapeHtml(x.producto)}</b></td><td>${escapeHtml(x.codigo)}</td><td><b>${x.existencias}</b> / mín. ${x.stock_minimo}</td><td>${money(x.precio)}</td><td><span class="badge ${cls}">${escapeHtml(x.nivel)}</span></td></tr>`;
        }).join('') : `<tr><td colspan="5" style="text-align:center;color:var(--ink-soft);padding:30px">Sin resultados.</td></tr>`;
    } catch (e) { notify(e.message, 'error'); }
}
$('#invSearch').addEventListener('input', loadInventario);
$('#invSoloBajo').addEventListener('change', loadInventario);

$('#btnAjustarStock').addEventListener('click', async () => {
    let productos = [];
    try { productos = (await request('get_productos_simple')).data; } catch(e) { notify(e.message,'error'); return; }
    const options = productos.map(p => `<option value="${p.id_producto}">${escapeHtml(p.nombre)} (${escapeHtml(p.codigo)})</option>`).join('');
    const { value: form } = await Swal.fire({
        title: 'Actualizar Existencias',
        html: `<div style="text-align:left;">
            <label><b>Producto:</b></label>
            <select id="swal-prod" class="swal2-input" style="width:100%;margin:8px 0 16px;">${options}</select>
            <label><b>Tipo de movimiento:</b></label>
            <select id="swal-tipo" class="swal2-input" style="width:100%;margin:8px 0 16px;">
                <option value="entrada">Entrada (sumar)</option>
                <option value="salida">Salida (restar)</option>
                <option value="reemplazo">Ajuste fijo (reemplazar)</option>
            </select>
            <label><b>Cantidad:</b></label>
            <input id="swal-cant" type="number" min="0" class="swal2-input" style="width:100%;margin-top:8px;" value="1">
        </div>`,
        confirmButtonText: 'Guardar', confirmButtonColor: 'var(--coffee-main)', showCancelButton: true, cancelButtonText: 'Cancelar',
        preConfirm: () => ({ id_producto: document.getElementById('swal-prod').value, tipo: document.getElementById('swal-tipo').value, cantidad: document.getElementById('swal-cant').value })
    });
    if (!form) return;
    try {
        const fd = new FormData(); Object.entries(form).forEach(([k,v]) => fd.append(k,v));
        const d = await request('ajustar_stock', { method:'POST', body: fd });
        notify(d.msg); loadInventario(); loadDashboard();
    } catch (e) { notify(e.message, 'error'); }
});

// -------- PRODUCTOS --------
async function loadProductos() {
    try {
        const q = $('#prodSearch').value;
        const { data } = await request(`get_productos&q=${encodeURIComponent(q)}`);
        $('#tblProductos').innerHTML = data.length ? data.map(x => `<tr>
            <td><b>${escapeHtml(x.codigo)}</b></td><td>${escapeHtml(x.nombre)}</td><td>${escapeHtml(x.categoria)}</td>
            <td>${money(x.precio)}</td><td>${x.existencias}</td>
            <td><span class="badge ${x.estado==='ACTIVO'?'b-green':'b-red'}">${escapeHtml(x.estado)}</span></td>
            <td><button class="chip-btn" data-edit-prod='${JSON.stringify(x).replace(/'/g,'&#39;')}'>Editar</button></td>
        </tr>`).join('') : `<tr><td colspan="7" style="text-align:center;color:var(--ink-soft);padding:30px">Sin productos registrados.</td></tr>`;
    } catch (e) { notify(e.message, 'error'); }
}
$('#prodSearch').addEventListener('input', loadProductos);

async function productoModal(record = null) {
    let categorias = [];
    try { categorias = (await request('get_categorias')).data; } catch(e) {}
    const catOptions = categorias.map(c => `<option value="${c.id_categoria}" ${record && +record.id_categoria===+c.id_categoria?'selected':''}>${escapeHtml(c.nombre)}</option>`).join('');
    const editing = !!record;

    const { value: form } = await Swal.fire({
        title: editing ? 'Editar Producto' : 'Nuevo Producto',
        html: `<div style="text-align:left;">
            <label><b>Código *</b></label><input id="swal-codigo" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.codigo):''}">
            <label><b>Nombre *</b></label><input id="swal-nombre" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.nombre):''}">
            <label><b>Descripción</b></label><input id="swal-desc" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.descripcion||''):''}">
            <label><b>Categoría</b></label><select id="swal-cat" class="swal2-input" style="width:100%;margin:6px 0 12px;">${catOptions}</select>
            <label><b>Precio *</b></label><input id="swal-precio" type="number" step="0.01" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?record.precio:''}">
            ${editing ? `<label><b>Stock mínimo</b></label><input id="swal-min" type="number" min="0" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${record.stock_minimo}">`
                      : `<label><b>Existencia inicial</b></label><input id="swal-exist" type="number" min="0" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="0">
                         <label><b>Stock mínimo</b></label><input id="swal-min" type="number" min="0" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="5">`}
            ${editing ? `<label><b>Estado</b></label><select id="swal-estado" class="swal2-input" style="width:100%;margin:6px 0;"><option ${record.estado==='ACTIVO'?'selected':''}>ACTIVO</option><option ${record.estado==='INACTIVO'?'selected':''}>INACTIVO</option></select>` : ''}
        </div>`,
        confirmButtonText: 'Guardar', confirmButtonColor: 'var(--coffee-main)', showCancelButton: true, cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const codigo = document.getElementById('swal-codigo').value.trim();
            const nombre = document.getElementById('swal-nombre').value.trim();
            if (!codigo || !nombre) { Swal.showValidationMessage('Código y nombre son obligatorios.'); return false; }
            const out = {
                codigo, nombre, descripcion: document.getElementById('swal-desc').value,
                id_categoria: document.getElementById('swal-cat').value, precio: document.getElementById('swal-precio').value,
                stock_minimo: document.getElementById('swal-min').value
            };
            if (editing) { out.id = record.id_producto; out.estado = document.getElementById('swal-estado').value; }
            else { out.existencias = document.getElementById('swal-exist').value; }
            return out;
        }
    });
    if (!form) return;
    try {
        const fd = new FormData(); Object.entries(form).forEach(([k,v]) => fd.append(k,v));
        const d = await request(editing ? 'update_producto' : 'create_producto', { method:'POST', body: fd });
        notify(d.msg); loadProductos(); loadInventario(); loadDashboard();
    } catch (e) { notify(e.message, 'error'); }
}
$('#btnNuevoProducto').addEventListener('click', () => productoModal());
document.addEventListener('click', e => {
    const editBtn = e.target.closest('[data-edit-prod]');
    if (editBtn) productoModal(JSON.parse(editBtn.dataset.editProd));
});

// -------- CAJAS --------
async function loadCajas() {
    try {
        const { data } = await request('get_cajas');
        $('#tblCajas').innerHTML = data.length ? data.map(x => `<tr>
            <td><b>${escapeHtml(x.nombre)}</b></td>
            <td><span class="badge ${x.estado==='ACTIVA'?'b-green':'b-red'}">${escapeHtml(x.estado)}</span></td>
            <td>${new Date(x.fecha_registro).toLocaleDateString('es-MX')}</td>
            <td><button class="icon-x" data-del-caja="${x.id_caja}">×</button></td>
        </tr>`).join('') : `<tr><td colspan="4" style="text-align:center;color:var(--ink-soft);padding:30px">Sin cajas registradas.</td></tr>`;
    } catch (e) { notify(e.message, 'error'); }
}
$('#btnNuevaCaja').addEventListener('click', async () => {
    const { value: nombre } = await Swal.fire({
        title: 'Nueva Caja', input: 'text', inputPlaceholder: 'Ej. Caja Principal 01',
        confirmButtonText: 'Registrar', confirmButtonColor: 'var(--coffee-main)', showCancelButton: true, cancelButtonText: 'Cancelar',
        inputValidator: v => !v && 'El nombre es obligatorio.'
    });
    if (!nombre) return;
    try {
        const fd = new FormData(); fd.append('nombre', nombre);
        const d = await request('create_caja', { method:'POST', body: fd });
        notify(d.msg); loadCajas(); loadDashboard();
    } catch (e) { notify(e.message, 'error'); }
});
document.addEventListener('click', async e => {
    const delBtn = e.target.closest('[data-del-caja]');
    if (!delBtn) return;
    const conf = await Swal.fire({ title:'¿Eliminar caja?', icon:'warning', showCancelButton:true, confirmButtonText:'Sí, eliminar', confirmButtonColor:'var(--danger)', cancelButtonText:'Cancelar' });
    if (!conf.isConfirmed) return;
    try {
        const fd = new FormData(); fd.append('id', delBtn.dataset.delCaja);
        const d = await request('delete_caja', { method:'POST', body: fd });
        notify(d.msg); loadCajas(); loadDashboard();
    } catch (e) { notify(e.message, 'error'); }
});

// -------- VENTAS --------
async function loadVentas() {
    try {
        const p = new URLSearchParams({ desde: $('#ventasDesde').value, hasta: $('#ventasHasta').value });
        const { data } = await request(`get_ventas&${p}`);
        $('#tblVentas').innerHTML = data.length ? data.map(x => `<tr>
            <td>#${x.id_venta}</td><td>${new Date(x.fecha_hora).toLocaleString('es-MX')}</td><td>${escapeHtml(x.empleado)}</td>
            <td>${escapeHtml(x.metodo_pago)}</td><td><b>${money(x.total)}</b></td>
            <td><span class="badge b-green">${escapeHtml(x.estado)}</span></td>
            <td><button class="chip-btn" data-ver-venta="${x.id_venta}">Ver</button></td>
        </tr>`).join('') : `<tr><td colspan="7" style="text-align:center;color:var(--ink-soft);padding:30px">Sin ventas en el rango.</td></tr>`;
    } catch (e) { notify(e.message, 'error'); }
}
['#ventasDesde','#ventasHasta'].forEach(s => $(s).addEventListener('change', loadVentas));
document.addEventListener('click', async e => {
    const verBtn = e.target.closest('[data-ver-venta]');
    if (!verBtn) return;
    try {
        const { data } = await request(`get_venta_detalle&id=${verBtn.dataset.verVenta}`);
        Swal.fire({
            title: `Detalle Venta #${verBtn.dataset.verVenta}`,
            html: data.length ? `<table style="width:100%;text-align:left;"><tr><th>Producto</th><th>Cant</th><th style="text-align:right">Subtotal</th></tr>${data.map(x=>`<tr><td>${escapeHtml(x.producto)}</td><td>${x.cantidad}</td><td style="text-align:right">${money(x.subtotal)}</td></tr>`).join('')}</table>` : 'Sin datos.'
        });
    } catch (e) { notify(e.message, 'error'); }
});

// -------- PUNTO DE VENTA --------
const btnIrPOS = $('#btnIrPOS'), btnCerrarCaja = $('#btnCerrarCaja');
let cart = [], shiftOpen = false;

btnIrPOS.addEventListener('click', async () => {
    if (shiftOpen) { switchView('view-pos'); return; }
    let cajas = [];
    try { cajas = (await request('get_cajas_activas')).data; } catch(e) {}
    if (!cajas.length) { notify('Registra al menos una caja activa antes de abrir un turno.', 'error'); switchView('view-cajas'); return; }
    const options = cajas.map(c => `<option value="${c.id_caja}">${escapeHtml(c.nombre)}</option>`).join('');

    const { value: form } = await Swal.fire({
        title: 'Apertura de Caja',
        html: `<div style="text-align:left; font-size:14px;">
            <label><b>Selecciona Terminal:</b></label>
            <select id="swal-caja" class="swal2-input" style="width:100%; margin: 10px 0 20px;">${options}</select>
        </div>`,
        confirmButtonText: 'Abrir Turno', confirmButtonColor: 'var(--success)', showCancelButton: true, cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const sel = document.getElementById('swal-caja');
            return { id: sel.value, nombre: sel.options[sel.selectedIndex].text };
        }
    });
    if (!form) return;

    $('#lblCajaActual').innerText = form.nombre;
    shiftOpen = true;
    try {
        const { data: metodos } = await request('get_metodos_pago');
        $('#posMetodo').innerHTML = metodos.length ? metodos.map(m => `<option value="${m.id_metodo_pago}">${escapeHtml(m.nombre)}</option>`).join('') : '<option value="">Sin métodos configurados</option>';
    } catch (e) {}

    Swal.fire({ icon:'success', title:'Turno Abierto', timer:1200, showConfirmButton:false });
    switchView('view-pos');
    $('#posBarcode').focus();
});

btnCerrarCaja.addEventListener('click', () => {
    Swal.fire({ title:'¿Cerrar Turno?', icon:'warning', showCancelButton:true, confirmButtonColor:'var(--danger)', confirmButtonText:'Sí, cerrar caja', cancelButtonText:'Cancelar' })
    .then(result => {
        if (result.isConfirmed) {
            shiftOpen = false; cart = []; updateCartUI();
            switchView('view-dash'); loadDashboard();
            Swal.fire({ icon:'success', title:'Turno cerrado', timer:1200, showConfirmButton:false });
        }
    });
});

const posBarcode = $('#posBarcode');
posBarcode.addEventListener('keypress', async e => {
    if (e.key !== 'Enter' || posBarcode.value.trim() === '') return;
    const codigo = posBarcode.value.trim(); posBarcode.value = '';
    try {
        const { data } = await request(`buscar_producto_pos&codigo=${encodeURIComponent(codigo)}`);
        const existing = cart.find(i => i.id_producto === data.id_producto);
        if (existing) { existing.qty++; } else { cart.push({ id_producto:data.id_producto, nombre:data.nombre, precio:parseFloat(data.precio), qty:1 }); }
        updateCartUI();
    } catch (e) { notify(e.message, 'error'); }
});

function updateCartUI() {
    const tbody = $('#cartBody');
    if (cart.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:40px; color:var(--ink-soft);">El carrito está vacío. Escanea un producto.</td></tr>`;
        $('#posSubtotal').innerText = money(0); $('#posIva').innerText = money(0); $('#posTotal').innerText = money(0);
        return;
    }
    let subtotal = 0;
    tbody.innerHTML = cart.map(item => {
        const lineTotal = item.precio * item.qty; subtotal += lineTotal;
        return `<tr><td style="padding:16px 24px;"><b>${escapeHtml(item.nombre)}</b></td>
            <td style="text-align:center;"><span style="background:var(--coffee-light); color:var(--coffee-main); padding:4px 12px; border-radius:20px; font-weight:700;">${item.qty}</span></td>
            <td style="text-align:right; color:var(--ink-soft);">${money(item.precio)}</td>
            <td style="text-align:right; padding-right:24px; font-weight:700;">${money(lineTotal)}</td></tr>`;
    }).join('');
    const iva = subtotal * 0.16, total = subtotal + iva;
    $('#posSubtotal').innerText = money(subtotal); $('#posIva').innerText = money(iva); $('#posTotal').innerText = money(total);
}

$('#btnCobrar').addEventListener('click', async () => {
    if (cart.length === 0) { notify('No hay productos en la venta.', 'error'); return; }
    const idMetodo = $('#posMetodo').value;
    if (!idMetodo) { notify('Selecciona un método de pago.', 'error'); return; }

    const conf = await Swal.fire({
        title: 'Procesar Cobro', text: `Total a cobrar: ${$('#posTotal').innerText}`, icon: 'info',
        showCancelButton: true, confirmButtonColor: 'var(--coffee-main)', confirmButtonText: 'Confirmar Pago', cancelButtonText: 'Cancelar'
    });
    if (!conf.isConfirmed) return;

    try {
        const fd = new FormData();
        fd.append('carrito', JSON.stringify(cart.map(i => ({ id_producto:i.id_producto, cantidad:i.qty }))));
        fd.append('id_metodo_pago', idMetodo);
        const d = await request('procesar_venta', { method:'POST', body: fd });
        Swal.fire({ title:'¡Pago Exitoso!', text:d.msg, icon:'success', timer:1800, showConfirmButton:false }).then(() => {
            cart = []; updateCartUI(); posBarcode.focus(); loadDashboard();
        });
    } catch (e) { notify(e.message, 'error'); }
});

// -------- INICIO --------
loadDashboard();
</script>

</body>
</html>