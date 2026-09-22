<?php
declare(strict_types=1);

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
// Resolver el usuario en sesión y VERIFICAR que su rol sea Admin
// --------------------------------------------------------------
$id_usuario_actual = 0;
if (is_array($_SESSION['usuario'] ?? null)) {
    $id_usuario_actual = (int)($_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? 0);
} else {
    $id_usuario_actual = (int)($_SESSION['usuario'] ?? 0);
}

$admin = null;
if (isset($pdo) && $pdo instanceof PDO && $id_usuario_actual > 0) {
    $stA = $pdo->prepare("SELECT u.id_usuario, u.nombre, u.apellido, u.estado, r.nombre AS rol
                           FROM usuarios u LEFT JOIN roles r ON r.id_rol = u.id_rol
                           WHERE u.id_usuario = ?");
    $stA->execute([$id_usuario_actual]);
    $admin = $stA->fetch(PDO::FETCH_ASSOC) ?: null;
}
$esAdmin = $admin && stripos((string)($admin['rol'] ?? ''), 'admin') !== false && ($admin['estado'] ?? '') !== 'INACTIVO';
$nombreAdmin = trim(($admin['nombre'] ?? 'Administrador') . ' ' . ($admin['apellido'] ?? ''));

// --------------------------------------------------------------
// API (AJAX) — toda acción exige rol de Administrador verificado
// --------------------------------------------------------------
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $out = ['ok' => false, 'msg' => 'Acción desconocida'];
    try {
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            throw new RuntimeException('[ERROR DE SISTEMA] No fue posible conectar con la base de datos. Revisa tus credenciales.');
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if (!$esAdmin) {
            throw new RuntimeException('[ERROR DE SISTEMA] Tu sesión no tiene permisos de administrador. Inicia sesión nuevamente.');
        }

        if ($action === 'get_metrics') {
            $ts = (int)$pdo->query("SELECT COUNT(*) FROM sucursales")->fetchColumn();
            $tu = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
            $tp = (int)$pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
            $inv_data = $pdo->query("SELECT COALESCE(SUM(i.existencias),0) AS piezas, COALESCE(SUM(i.existencias * p.precio),0) AS valor FROM inventarios i JOIN productos p ON p.id_producto = i.id_producto")->fetch(PDO::FETCH_ASSOC);
            $ti = (int)($inv_data['piezas'] ?? 0);
            $valor_inv = (float)($inv_data['valor'] ?? 0);

            $rv = $pdo->query("SELECT COUNT(*) AS n, COALESCE(SUM(total),0) AS t FROM ventas WHERE DATE(fecha_hora)=CURDATE()")->fetch();
            $out = ['ok' => true, 'data' => [
                'ts' => $ts, 'tu' => $tu, 'tp' => $tp, 'ti' => $ti, 'valor_inv' => $valor_inv,
                'vh' => (int)($rv['n'] ?? 0), 'ih' => (float)($rv['t'] ?? 0)
            ]];

        } elseif ($action === 'get_sucursales') {
            $stmt = $pdo->query("SELECT id_sucursal, nombre, direccion, telefono, contacto, estado FROM sucursales ORDER BY id_sucursal");
            $out = ['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'create_sucursal') {
            $nombre = trim($_POST['nombre'] ?? ''); $dir = trim($_POST['direccion'] ?? '');
            $tel = trim($_POST['telefono'] ?? ''); $cont = trim($_POST['contacto'] ?? '');
            if ($nombre === '') throw new Exception('[ERROR DE USUARIO] El nombre de la sucursal es obligatorio.');
            $pdo->prepare("INSERT INTO sucursales (nombre,direccion,telefono,contacto,estado) VALUES(?,?,?,?,'ACTIVA')")->execute([$nombre, $dir, $tel, $cont]);
            $out = ['ok' => true, 'msg' => 'Sucursal registrada con éxito.'];

        } elseif ($action === 'update_sucursal') {
            $id = (int)($_POST['id'] ?? 0); $nombre = trim($_POST['nombre'] ?? ''); $tel = trim($_POST['telefono'] ?? '');
            if (!$id || !$nombre) throw new Exception('[ERROR DE USUARIO] Faltan datos obligatorios para actualizar.');
            $est = ($_POST['estado'] ?? 'ACTIVA') === 'INACTIVA' ? 'INACTIVA' : 'ACTIVA';
            $pdo->prepare("UPDATE sucursales SET nombre=?,direccion=?,telefono=?,contacto=?,estado=? WHERE id_sucursal=?")
                ->execute([$nombre, trim($_POST['direccion'] ?? ''), $tel, trim($_POST['contacto'] ?? ''), $est, $id]);
            $out = ['ok' => true, 'msg' => 'Datos de la sucursal actualizados.'];

        } elseif ($action === 'delete_sucursal') {
            $id = (int)($_POST['id'] ?? 0); if (!$id) throw new Exception('[ERROR DE SISTEMA] ID de sucursal inválido.');
            $pdo->prepare("DELETE FROM sucursales WHERE id_sucursal=?")->execute([$id]);
            $out = ['ok' => true, 'msg' => 'Sucursal eliminada.'];

        } elseif ($action === 'get_gerentes') {
            $st = $pdo->query("SELECT u.id_usuario,u.nombre,u.apellido,u.correo,u.estado,r.nombre AS rol,COALESCE(s.nombre,'-- Ninguna --') AS sucursal,u.id_sucursal,u.id_rol FROM usuarios u LEFT JOIN roles r ON r.id_rol=u.id_rol LEFT JOIN sucursales s ON s.id_sucursal=u.id_sucursal ORDER BY u.id_usuario");
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'delete_gerente') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === $id_usuario_actual) throw new Exception('[ERROR DE USUARIO] No puedes eliminar tu propio usuario mientras tienes sesión activa.');
            $pdo->prepare("DELETE FROM usuarios WHERE id_usuario=?")->execute([$id]);
            $out = ['ok' => true, 'msg' => 'Registro del personal eliminado.'];

        } elseif ($action === 'get_roles') {
            try { $data = $pdo->query("SELECT id_rol, nombre FROM roles ORDER BY id_rol")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) { $data = []; }
            $out = ['ok' => true, 'data' => $data];

        } elseif ($action === 'get_categorias') {
            try { $data = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) { $data = []; }
            $out = ['ok' => true, 'data' => $data];

        } elseif ($action === 'get_productos') {
            $q = trim($_GET['q'] ?? '');
            $sql = "SELECT p.id_producto,p.codigo,p.nombre,p.descripcion,p.id_categoria,p.precio,p.estado,COALESCE(c.nombre, 'Sin categoría') AS categoria FROM productos p LEFT JOIN categorias c ON c.id_categoria=p.id_categoria";
            $params = [];
            if ($q !== '') { $sql .= " WHERE p.codigo LIKE ? OR p.nombre LIKE ?"; $params[] = "%$q%"; $params[] = "%$q%"; }
            $sql .= " ORDER BY p.nombre ASC LIMIT 200";
            $st = $pdo->prepare($sql); $st->execute($params);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'create_producto' || $action === 'update_producto') {
            $id = (int)($_POST['id'] ?? 0); $codigo = trim($_POST['codigo'] ?? ''); $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? ''); $categoria = (int)($_POST['id_categoria'] ?? 0);
            $precio = (float)str_replace(',', '.', $_POST['precio'] ?? '0');
            $estado = ($_POST['estado'] ?? 'ACTIVO') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO';
            $sucs = $_POST['sucursales'] ?? [];
            if ($codigo === '' || $nombre === '') throw new Exception('[ERROR DE USUARIO] El código y el nombre son obligatorios.');

            $pdo->beginTransaction();
            try {
                if ($action === 'create_producto') {
                    $pdo->prepare("INSERT INTO productos(codigo,nombre,descripcion,id_categoria,precio,estado) VALUES(?,?,?,?,?,?)")
                        ->execute([$codigo, $nombre, $descripcion ?: null, $categoria > 0 ? $categoria : null, $precio, $estado]);
                    $new_id = (int)$pdo->lastInsertId();
                    if (!empty($sucs)) {
                        $st_inv = $pdo->prepare("INSERT INTO inventarios(id_producto, id_sucursal, existencias, stock_minimo) VALUES(?,?,0,5)");
                        foreach ($sucs as $sid) $st_inv->execute([$new_id, $sid]);
                    }
                    $out = ['ok' => true, 'msg' => 'Producto registrado exitosamente. (Stock inicial en 0)'];
                } else {
                    $pdo->prepare("UPDATE productos SET codigo=?,nombre=?,descripcion=?,id_categoria=?,precio=?,estado=? WHERE id_producto=?")
                        ->execute([$codigo, $nombre, $descripcion ?: null, $categoria > 0 ? $categoria : null, $precio, $estado, $id]);
                    if (!empty($sucs)) {
                        $st_check = $pdo->prepare("SELECT COUNT(*) FROM inventarios WHERE id_producto=? AND id_sucursal=?");
                        $st_inv = $pdo->prepare("INSERT INTO inventarios(id_producto, id_sucursal, existencias, stock_minimo) VALUES(?,?,0,5)");
                        foreach ($sucs as $sid) {
                            $st_check->execute([$id, $sid]);
                            if ($st_check->fetchColumn() == 0) $st_inv->execute([$id, $sid]);
                        }
                    }
                    $out = ['ok' => true, 'msg' => 'Producto actualizado correctamente.'];
                }
                $pdo->commit();
            } catch (Exception $e) { $pdo->rollBack(); throw $e; }

        } elseif ($action === 'delete_producto') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('[ERROR DE SISTEMA] ID de producto inválido.');
            $pdo->prepare("DELETE FROM inventarios WHERE id_producto=?")->execute([$id]);
            $pdo->prepare("DELETE FROM productos WHERE id_producto=?")->execute([$id]);
            $out = ['ok' => true, 'msg' => 'Producto eliminado permanentemente.'];

        } elseif ($action === 'get_sucursales_por_producto') {
            $id_p = (int)($_GET['id_producto'] ?? 0);
            $st = $pdo->prepare("SELECT s.id_sucursal, s.nombre FROM inventarios i JOIN sucursales s ON s.id_sucursal = i.id_sucursal WHERE i.id_producto = ?");
            $st->execute([$id_p]);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'ajustar_stock') {
            $id_p = (int)($_POST['id_producto'] ?? 0); $id_s = (int)($_POST['id_sucursal'] ?? 0);
            $cant = (int)($_POST['cantidad'] ?? 0); $tipo = $_POST['tipo'] ?? '';
            if (!$id_p || !$id_s) throw new Exception('[ERROR DE USUARIO] Debes seleccionar un producto y una sucursal válidos.');
            if ($cant < 0) throw new Exception('[ERROR DE USUARIO] No puedes ingresar cantidades negativas.');
            $st = $pdo->prepare("SELECT existencias FROM inventarios WHERE id_producto=? AND id_sucursal=?");
            $st->execute([$id_p, $id_s]);
            $row = $st->fetch();
            if (!$row) throw new Exception("[ERROR DE USUARIO] Ese producto no existe en la sucursal seleccionada. Ve a 'Productos', edítalo y asígnale esta sucursal primero.");
            $nueva_cant = $row['existencias'];
            if ($tipo === 'entrada') $nueva_cant += $cant;
            elseif ($tipo === 'salida') $nueva_cant = max(0, $nueva_cant - $cant);
            elseif ($tipo === 'reemplazo') $nueva_cant = $cant;
            $pdo->prepare("UPDATE inventarios SET existencias=? WHERE id_producto=? AND id_sucursal=?")->execute([$nueva_cant, $id_p, $id_s]);
            $out = ['ok' => true, 'msg' => "Stock actualizado correctamente. Nueva existencia: $nueva_cant unidades."];

        } elseif ($action === 'get_producto_detalle') {
            $id = (int)($_GET['id'] ?? 0);
            $st = $pdo->prepare("SELECT s.id_sucursal, s.nombre AS sucursal, i.existencias FROM inventarios i JOIN sucursales s ON s.id_sucursal = i.id_sucursal WHERE i.id_producto = ?");
            $st->execute([$id]);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'get_sucursales_simple') {
            $out = ['ok' => true, 'data' => $pdo->query("SELECT id_sucursal,nombre FROM sucursales WHERE estado='ACTIVA' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'get_productos_simple') {
            $out = ['ok' => true, 'data' => $pdo->query("SELECT id_producto, nombre, codigo FROM productos WHERE estado='ACTIVO' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'create_gerente' || $action === 'update_gerente') {
            $id = (int)($_POST['id'] ?? 0); $nom = trim($_POST['nombre'] ?? ''); $cor = trim($_POST['correo'] ?? '');
            $pas = trim($_POST['password'] ?? ''); $rol = (int)($_POST['id_rol'] ?? 0);
            $suc = ($_POST['id_sucursal'] ?? '') !== '' ? (int)$_POST['id_sucursal'] : null;
            if ($nom === '' || $cor === '') throw new Exception('[ERROR DE USUARIO] Nombre y correo son obligatorios.');

            $st_r = $pdo->prepare("SELECT nombre FROM roles WHERE id_rol = ?");
            $st_r->execute([$rol]);
            $rname = $st_r->fetchColumn() ?: '';
            $is_admin = (stripos($rname, 'admin') !== false);
            if (!$is_admin && !$suc) throw new Exception('[ERROR DE USUARIO] Los Gerentes y Cajeros DEBEN tener una sucursal asignada.');
            if ($is_admin) $suc = null;

            $st_mail = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE correo=? AND id_usuario<>?");
            $st_mail->execute([$cor, $id]);
            if ($st_mail->fetchColumn() > 0) throw new Exception('[ERROR DE USUARIO] Ya existe otro usuario registrado con ese correo.');

            if ($action === 'create_gerente') {
                if ($pas === '') throw new Exception('[ERROR DE USUARIO] La contraseña es obligatoria para un nuevo registro.');
                $pdo->prepare("INSERT INTO usuarios(nombre,apellido,correo,password_hash,id_rol,id_sucursal,estado)VALUES(?,?,?,?,?,?,'ACTIVO')")
                    ->execute([$nom, trim($_POST['apellido'] ?? ''), $cor, password_hash($pas, PASSWORD_BCRYPT), $rol, $suc]);
                $out = ['ok' => true, 'msg' => 'Personal registrado correctamente.'];
            } else {
                $est = ($_POST['estado'] ?? 'ACTIVO') === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO';
                if ($id === $id_usuario_actual && $est === 'INACTIVO') throw new Exception('[ERROR DE USUARIO] No puedes desactivar tu propio usuario mientras tienes sesión activa.');
                $pdo->prepare("UPDATE usuarios SET nombre=?,apellido=?,correo=?,id_rol=?,id_sucursal=?,estado=? WHERE id_usuario=?")
                    ->execute([$nom, trim($_POST['apellido'] ?? ''), $cor, $rol, $suc, $est, $id]);
                if ($pas !== '') $pdo->prepare("UPDATE usuarios SET password_hash=? WHERE id_usuario=?")->execute([password_hash($pas, PASSWORD_BCRYPT), $id]);
                $out = ['ok' => true, 'msg' => 'Información del personal actualizada.'];
            }

        } elseif ($action === 'get_inventarios') {
            $q = trim($_GET['q'] ?? ''); $sid = (int)($_GET['sucursal'] ?? 0);
            $sql = "SELECT i.id_inventario,p.nombre AS producto,p.codigo,s.nombre AS sucursal,i.existencias,i.stock_minimo,p.precio,CASE WHEN i.existencias<=0 THEN 'AGOTADO' WHEN i.existencias<=i.stock_minimo THEN 'BAJO' ELSE 'OK' END AS nivel FROM inventarios i JOIN productos p ON p.id_producto=i.id_producto JOIN sucursales s ON s.id_sucursal=i.id_sucursal WHERE 1=1";
            $params = [];
            if ($q !== '') { $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
            if ($sid > 0) { $sql .= " AND i.id_sucursal=?"; $params[] = $sid; }
            $sql .= " ORDER BY i.existencias ASC LIMIT 200";
            $st = $pdo->prepare($sql); $st->execute($params);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'get_ventas') {
            $sid = (int)($_GET['sucursal'] ?? 0); $desde = trim($_GET['desde'] ?? ''); $hasta = trim($_GET['hasta'] ?? '');
            $sql = "SELECT v.id_venta,v.fecha_hora,v.total,v.estado,s.nombre AS sucursal,mp.nombre AS metodo_pago,CONCAT(u.nombre,' ',COALESCE(u.apellido,'')) AS empleado FROM ventas v JOIN sucursales s ON s.id_sucursal=v.id_sucursal JOIN metodos_pago mp ON mp.id_metodo_pago=v.id_metodo_pago JOIN usuarios u ON u.id_usuario=v.id_usuario WHERE 1=1";
            $params = [];
            if ($sid > 0) { $sql .= " AND v.id_sucursal=?"; $params[] = $sid; }
            if ($desde !== '') { $sql .= " AND DATE(v.fecha_hora)>=?"; $params[] = $desde; }
            if ($hasta !== '') { $sql .= " AND DATE(v.fecha_hora)<=?"; $params[] = $hasta; }
            $sql .= " ORDER BY v.fecha_hora DESC LIMIT 200";
            $st = $pdo->prepare($sql); $st->execute($params);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'get_venta_detalle') {
            $id = (int)($_GET['id'] ?? 0);
            $st = $pdo->prepare("SELECT dv.cantidad, dv.precio_unitario, dv.subtotal, COALESCE(p.nombre, 'Producto no disponible') AS producto FROM detalle_ventas dv LEFT JOIN productos p ON p.id_producto = dv.id_producto WHERE dv.id_venta = ?");
            $st->execute([$id]);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];
        }
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'Base table or view not found') !== false) {
            $msg = "[ERROR DE SISTEMA/CÓDIGO] Te falta crear una tabla en tu base de datos: " . str_replace('SQLSTATE[42S02]: Base table or view not found: 1146 Table', '', $msg);
        } elseif (strpos($msg, 'Column not found') !== false) {
            $msg = "[ERROR DE SISTEMA/CÓDIGO] Te falta una columna en tu tabla: " . str_replace('SQLSTATE[42S22]: Column not found: 1054 Unknown column', '', $msg);
        } elseif (strpos($msg, 'Integrity constraint violation') !== false) {
            $msg = "[ERROR DE USUARIO] Estás intentando eliminar un registro que ya está vinculado a otras partes del sistema (como una venta). Es mejor ponerlo como INACTIVO.";
        }
        $out = ['ok' => false, 'msg' => $msg];
    }
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

// --------------------------------------------------------------
// Render de la página — bloquea a cualquiera que no sea Admin
// --------------------------------------------------------------
if (!$esAdmin) {
    ob_end_clean();
    echo '<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;padding:60px;text-align:center;color:#6c4630;">
    <h2>Acceso restringido</h2><p>Esta sección es exclusiva para el rol de Administrador.</p>
    <p><a href="home.php">Volver al inicio</a></p></body>';
    exit;
}

$ts = 0; $tu = 0; $tp = 0; $vh = 0; $ih = 0.0; $ti = 0; $valor_inv = 0; $v7 = []; $mp = [];
try {
    $ts = (int)$pdo->query("SELECT COUNT(*) FROM sucursales")->fetchColumn();
    $tu = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $tp = (int)$pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    $inv_data = $pdo->query("SELECT COALESCE(SUM(i.existencias),0) AS piezas, COALESCE(SUM(i.existencias * p.precio),0) AS valor FROM inventarios i JOIN productos p ON p.id_producto = i.id_producto")->fetch(PDO::FETCH_ASSOC);
    $ti = (int)($inv_data['piezas'] ?? 0); $valor_inv = (float)($inv_data['valor'] ?? 0);
    $rv = $pdo->query("SELECT COUNT(*) AS n,COALESCE(SUM(total),0) AS t FROM ventas WHERE DATE(fecha_hora)=CURDATE()")->fetch();
    $vh = (int)($rv['n'] ?? 0); $ih = (float)($rv['t'] ?? 0);
    for ($i = 6; $i >= 0; $i--) {
        $fd = date('Y-m-d', strtotime("-{$i} days")); $fl = date('d/m', strtotime("-{$i} days"));
        $r = $pdo->prepare("SELECT COUNT(*) AS n,COALESCE(SUM(total),0) AS t FROM ventas WHERE DATE(fecha_hora)=?"); $r->execute([$fd]); $rr = $r->fetch();
        $v7['labels'][] = $fl; $v7['ingresos'][] = (float)($rr['t'] ?? 0);
    }
    $mp = $pdo->query("SELECT mp.nombre AS metodo,COUNT(v.id_venta) AS total_ventas FROM metodos_pago mp LEFT JOIN ventas v ON v.id_metodo_pago=mp.id_metodo_pago GROUP BY mp.id_metodo_pago,mp.nombre")->fetchAll();
} catch (PDOException $e) {}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KION · Panel de Administración</title>

    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --bg-app: oklch(96.5% 0.014 72); --bg-surface: oklch(100% 0 0); --bg-panel: oklch(99% 0.006 75);
            --ink: oklch(24% 0.028 50); --ink-soft: oklch(42% 0.030 48); --ink-faint: oklch(58% 0.022 50); --border-soft: oklch(89% 0.016 65);
            --coffee-main: oklch(32% 0.040 50); --coffee-light: oklch(91% 0.018 60);
            --coffee-gradient: linear-gradient(155deg, oklch(40% 0.045 48), oklch(26% 0.035 52));
            --success: oklch(34% 0.06 152); --success-bg: oklch(94% 0.035 152);
            --danger: oklch(50% 0.14 25); --danger-bg: oklch(95% 0.035 20);
            --warning: oklch(42% 0.09 70); --warning-bg: oklch(95% 0.045 90);
            --sky: oklch(38% 0.07 240); --sky-bg: oklch(95% 0.022 235);
            --font-display: 'Fraunces', serif; --font-body: 'Plus Jakarta Sans', sans-serif;
            --shadow-sm: 0 4px 12px rgba(0,0,0,0.05); --shadow-md: 0 8px 24px rgba(0,0,0,0.08); --shadow-lg: 0 16px 40px rgba(0,0,0,0.12);
            --radius: 14px; --sidebar-w: 264px; --sidebar-w-collapsed: 84px;
            --ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
        }
        body.dark-mode {
            --bg-app: oklch(20% 0.01 250); --bg-surface: oklch(25% 0.01 250); --bg-panel: oklch(23% 0.01 250);
            --ink: oklch(95% 0.01 250); --ink-soft: oklch(75% 0.01 250); --ink-faint: oklch(62% 0.01 250); --border-soft: oklch(35% 0.01 250);
            --coffee-main: oklch(80% 0.03 60); --coffee-light: oklch(30% 0.02 250);
            --coffee-gradient: linear-gradient(155deg, oklch(40% 0.045 48), oklch(20% 0.035 52));
            --success-bg: oklch(25% 0.04 152); --danger-bg: oklch(30% 0.06 20); --warning-bg: oklch(30% 0.05 85); --sky-bg: oklch(26% 0.03 235);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { font-family: var(--font-body); background: var(--bg-app); color: var(--ink); transition: background .3s ease, color .3s ease; }
        button { font: inherit; cursor: pointer; border: none; background: none; color: inherit; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: .001ms !important; transition-duration: .001ms !important; } }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes wave { 0%,60%,100% { transform: rotate(0deg); } 10% { transform: rotate(16deg); } 20% { transform: rotate(-8deg); } 30% { transform: rotate(16deg); } 40% { transform: rotate(-4deg); } }
        .view-section { display: none; animation: fadeIn .38s var(--ease-expo) forwards; }
        .view-section.active { display: block; }
        .wave { display: inline-block; transform-origin: 70% 70%; animation: wave 2.2s ease-in-out .3s 1; }

        .app-layout { display: grid; grid-template-columns: var(--sidebar-w) 1fr; min-height: 100vh; transition: grid-template-columns .35s var(--ease-expo); }
        .app-layout.is-collapsed { grid-template-columns: var(--sidebar-w-collapsed) 1fr; }

        .sidebar { position: sticky; top: 0; height: 100vh; background: var(--bg-surface); border-right: 1px solid var(--border-soft); padding: 22px 16px; display: flex; flex-direction: column; gap: 26px; z-index: 40; transition: padding .35s var(--ease-expo), background .3s ease; }
        .brand { display: flex; align-items: center; gap: 12px; font-family: var(--font-display); font-size: 19px; font-weight: 600; padding: 0 8px; position: relative; }
        .brand i { font-size: 26px; color: var(--coffee-main); flex: none; }
        .brand-text { overflow: hidden; white-space: nowrap; transition: opacity .2s ease; }
        .brand-sub { font-size: 10.5px; letter-spacing: .6px; color: var(--ink-faint); font-weight: 700; text-transform: uppercase; margin-top: 1px; }
        .app-layout.is-collapsed .brand-text, .app-layout.is-collapsed .nav-label, .app-layout.is-collapsed .nav-section-title { opacity: 0; width: 0; pointer-events: none; }

        .sidebar-collapse-btn { position: absolute; top: 26px; right: -13px; width: 26px; height: 26px; border-radius: 50%; background: var(--bg-surface); border: 1px solid var(--border-soft); color: var(--coffee-main); display: grid; place-items: center; box-shadow: var(--shadow-sm); z-index: 41; transition: transform .3s var(--ease-expo); }
        .app-layout.is-collapsed .sidebar-collapse-btn { transform: rotate(180deg); }
        .sidebar-collapse-btn i { font-size: 14px; }

        .nav-section-title { font-size: 11px; text-transform: uppercase; letter-spacing: .8px; color: var(--ink-faint); font-weight: 700; padding: 4px 12px 6px; white-space: nowrap; }
        .nav-menu { display: flex; flex-direction: column; gap: 6px; }
        .nav-btn { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: var(--radius); font-weight: 600; font-size: 14px; color: var(--ink-soft); transition: background .2s var(--ease-expo), color .2s ease, transform .2s var(--ease-expo); white-space: nowrap; }
        .nav-btn i { font-size: 19px; flex: none; }
        .nav-btn:hover { background: var(--bg-app); color: var(--ink); transform: translateX(2px); }
        .nav-btn.active { background: var(--coffee-gradient); color: #fff; box-shadow: var(--shadow-sm); }

        .sidebar-scrim { display: none; position: fixed; inset: 0; background: rgba(20,16,12,.45); z-index: 39; opacity: 0; transition: opacity .3s ease; }

        .topbar { height: 70px; display: flex; justify-content: space-between; align-items: center; padding: 0 32px; border-bottom: 1px solid var(--border-soft); background: var(--bg-panel); position: sticky; top: 0; z-index: 30; backdrop-filter: blur(10px); }
        .topbar-title-block .eyebrow { font-size: 11.5px; color: var(--ink-faint); font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
        .topbar-title-block .title-main { font-family: var(--font-display); font-size: 20px; font-weight: 600; }
        .hamburger-btn { display: none; width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--border-soft); background: var(--bg-surface); align-items: center; justify-content: center; font-size: 18px; }
        .topbar-actions { display: flex; gap: 12px; align-items: center; }
        .icon-btn { width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center; background: var(--bg-app); border: 1px solid var(--border-soft); font-size: 19px; transition: background .2s ease, color .2s ease, transform .15s var(--ease-expo); }
        .icon-btn:hover { background: var(--coffee-light); color: var(--coffee-main); transform: translateY(-1px); }
        .icon-btn:active { transform: scale(.92); }
        .user-profile { display: flex; align-items: center; gap: 10px; font-weight: 600; padding-left: 14px; border-left: 1px solid var(--border-soft); font-size: 14px; }
        .admin-chip { font-size: 10px; font-weight: 800; letter-spacing: .5px; padding: 3px 8px; border-radius: 20px; background: var(--coffee-light); color: var(--coffee-main); text-transform: uppercase; }

        .content { padding: 32px; max-width: 1440px; margin: 0 auto; }
        .header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 14px; }
        .title { font-family: var(--font-display); font-size: 27px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .subtitle { color: var(--ink-soft); font-size: 14px; margin-top: 4px; }

        .btn-primary { background: var(--coffee-gradient); color: #fff; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 13.5px; box-shadow: var(--shadow-sm); display: inline-flex; align-items: center; gap: 8px; transition: transform .16s var(--ease-expo), box-shadow .2s ease; }
        .btn-primary:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
        .btn-primary:active { transform: scale(.96); }
        .btn-ghost { background: var(--bg-surface); border: 1px solid var(--border-soft); color: var(--coffee-main); padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: background .2s ease, transform .15s var(--ease-expo); }
        .btn-ghost:hover { background: var(--coffee-light); }
        .btn-ghost:active { transform: scale(.96); }

        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 18px; margin-bottom: 24px; }
        .kpi-card { background: var(--bg-surface); padding: 22px; border-radius: var(--radius); border: 1px solid var(--border-soft); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 12px; transition: transform .25s var(--ease-expo), box-shadow .25s var(--ease-expo), border-color .25s ease; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--coffee-light); }
        .kpi-top { display: flex; align-items: flex-start; justify-content: space-between; }
        .kpi-card i.kpi-icon { font-size: 22px; color: var(--coffee-main); padding: 11px; background: var(--coffee-light); border-radius: 11px; width: fit-content; }
        .kpi-tag { font-size: 10.5px; font-weight: 800; padding: 4px 9px; border-radius: 20px; background: var(--success-bg); color: var(--success); text-transform: uppercase; letter-spacing: .3px; }
        .kpi-val { font-family: var(--font-display); font-size: 30px; font-weight: 600; }
        .kpi-label { color: var(--ink-soft); font-size: 13px; font-weight: 600; }

        .panel { background: var(--bg-surface); border-radius: var(--radius); border: 1px solid var(--border-soft); padding: 24px; overflow-x: auto; margin-bottom: 22px; transition: background .3s ease, border-color .3s ease; }
        .panel-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .panel-title { font-family: var(--font-display); font-size: 17px; font-weight: 600; }
        .panel-sub { font-size: 12px; color: var(--ink-faint); margin-top: 2px; }
        .grid-2col { display: grid; grid-template-columns: 1.3fr 1fr; gap: 20px; }
        .chart-box { position: relative; height: 250px; }
        .donut-wrap { position: relative; height: 220px; display: flex; align-items: center; justify-content: center; }
        .donut-center { position: absolute; text-align: center; pointer-events: none; }
        .donut-center .num { font-family: var(--font-display); font-size: 22px; font-weight: 700; }
        .donut-center .lbl { font-size: 10px; color: var(--ink-faint); font-weight: 700; text-transform: uppercase; }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { font-size: 11.5px; text-transform: uppercase; letter-spacing: .4px; color: var(--ink-faint); padding-bottom: 14px; border-bottom: 1px solid var(--border-soft); font-weight: 700; }
        td { padding: 14px 0; border-bottom: 1px solid var(--border-soft); font-weight: 500; vertical-align: middle; }
        tbody tr { transition: background .15s ease; }
        tbody tr:hover { background: var(--bg-app); }
        tr:last-child td { border: none; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
        .badge.b-red { background: var(--danger-bg); color: var(--danger); }
        .badge.b-green { background: var(--success-bg); color: var(--success); }
        .badge.b-yellow { background: var(--warning-bg); color: var(--warning); }
        .badge.b-sky { background: var(--sky-bg); color: var(--sky); }
        .chip-btn { border: 1px solid var(--border-soft); background: var(--bg-app); color: var(--coffee-main); font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 8px; transition: background .18s ease, color .18s ease; }
        .chip-btn:hover { background: var(--coffee-main); color: #fff; }
        .icon-x { width: 30px; height: 30px; border-radius: 8px; border: 1px solid var(--border-soft); display: inline-grid; place-items: center; color: var(--ink-soft); transition: background .18s ease, color .18s ease; }
        .icon-x:hover { background: var(--danger-bg); color: var(--danger); }
        .row-actions { display: flex; gap: 6px; align-items: center; }

        .form-inline { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .search-input, .select-input { padding: 10px 16px; border: 1px solid var(--border-soft); border-radius: 10px; background: var(--bg-app); color: var(--ink); min-width: 200px; font-family: inherit; font-size: 14px; }

        .stock-list { display: flex; flex-direction: column; gap: 2px; }
        .stock-row { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 14px; padding: 12px 4px; border-bottom: 1px solid var(--border-soft); }
        .stock-row:last-child { border-bottom: none; }
        .stock-thumb { width: 38px; height: 38px; border-radius: 10px; background: var(--coffee-light); display: grid; place-items: center; color: var(--coffee-main); font-size: 17px; flex: none; }
        .stock-name { font-size: 13.5px; font-weight: 700; }
        .stock-meta { font-size: 11.5px; color: var(--ink-faint); margin-top: 2px; }
        .stock-qty { text-align: right; font-weight: 700; font-size: 13px; white-space: nowrap; }
        .stock-qty small { display: block; font-weight: 500; color: var(--ink-faint); font-size: 10.5px; }

        .info-strip { display: flex; justify-content: space-between; align-items: flex-end; padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid var(--border-soft); }
        .info-strip:last-child { border: none; margin: 0; padding: 0; }

        .dark-mode .swal2-popup { background: var(--bg-surface); color: var(--ink); }
        .dark-mode .swal2-input, .dark-mode .swal2-select, .dark-mode .swal2-textarea { background: var(--bg-app); color: var(--ink); border-color: var(--border-soft); }

        @media (max-width: 1180px) { .grid-2col { grid-template-columns: 1fr; } }
        @media (max-width: 980px) {
            .app-layout { grid-template-columns: 1fr; }
            .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100vh; transform: translateX(-100%); transition: transform .35s var(--ease-expo); box-shadow: var(--shadow-lg); }
            .app-layout.mobile-open .sidebar { transform: translateX(0); }
            .app-layout.mobile-open .sidebar-scrim { display: block; opacity: 1; }
            .sidebar-collapse-btn { display: none; }
            .hamburger-btn { display: inline-flex; }
        }
    </style>
</head>
<body>

<div class="app-layout" id="appLayout">
    <aside class="sidebar">
        <button class="sidebar-collapse-btn" id="btnCollapse" title="Colapsar menú"><i class="ph ph-caret-left"></i></button>
        <div class="brand">
            <i class="ph-fill ph-paw-print"></i>
            <div class="brand-text"><div>KION</div><div class="brand-sub">Administración General</div></div>
        </div>
        <nav class="nav-menu">
            <div class="nav-section-title">Panel</div>
            <button class="nav-btn active" data-target="view-general"><i class="ph ph-squares-four"></i><span class="nav-label">Resumen General</span></button>
            <div class="nav-section-title">Gestión</div>
            <button class="nav-btn" data-target="view-sucursales"><i class="ph ph-storefront"></i><span class="nav-label">Sucursales</span></button>
            <button class="nav-btn" data-target="view-personal"><i class="ph ph-users-three"></i><span class="nav-label">Personal</span></button>
            <button class="nav-btn" data-target="view-inventario"><i class="ph ph-package"></i><span class="nav-label">Inventario</span></button>
            <button class="nav-btn" data-target="view-productos"><i class="ph ph-tag"></i><span class="nav-label">Productos</span></button>
            <button class="nav-btn" data-target="view-ventas"><i class="ph ph-chart-line-up"></i><span class="nav-label">Ventas</span></button>
            <div class="nav-section-title">Navegación</div>
            <a class="nav-btn" href="home.php"><i class="ph ph-house"></i><span class="nav-label">Volver a Home</span></a>
            <a class="nav-btn" href="cerrarSesion.php" style="color:var(--danger)"><i class="ph ph-sign-out"></i><span class="nav-label">Cerrar sesión</span></a>
        </nav>
    </aside>
    <div class="sidebar-scrim" id="sidebarScrim"></div>

    <main>
        <header class="topbar">
            <div style="display:flex; align-items:center; gap:14px;">
                <button class="hamburger-btn" id="btnMobileMenu"><i class="ph ph-list"></i></button>
                <div class="topbar-title-block">
                    <div class="eyebrow">Panel principal</div>
                    <div class="title-main" id="topbarTitle">Resumen General</div>
                </div>
            </div>
            <div class="topbar-actions">
                <button class="icon-btn" id="btnTheme" title="Modo Oscuro"><i class="ph ph-moon"></i></button>
                <div class="user-profile">
                    <i class="ph-fill ph-user-circle" style="font-size:24px; color:var(--coffee-main)"></i>
                    <span><?= htmlspecialchars($nombreAdmin) ?></span>
                    <span class="admin-chip">Admin</span>
                </div>
            </div>
        </header>

        <div class="content">

            <!-- RESUMEN GENERAL -->
            <section id="view-general" class="view-section active">
                <div class="header-row">
                    <div><h1 class="title">Hola, administración <span class="wave">👋</span></h1><p class="subtitle">Este es el pulso operativo de KION al día de hoy.</p></div>
                    <button class="btn-ghost" id="refreshDashboard"><i class="ph ph-arrows-clockwise"></i> Actualizar</button>
                </div>
                <div class="kpi-grid">
                    <div class="kpi-card"><div class="kpi-top"><i class="ph-fill ph-storefront kpi-icon"></i><span class="kpi-tag">Red</span></div><div class="kpi-val" id="metricSucursales"><?= number_format($ts) ?></div><div class="kpi-label">Sucursales</div></div>
                    <div class="kpi-card"><div class="kpi-top"><i class="ph-fill ph-users-three kpi-icon"></i><span class="kpi-tag">Equipo</span></div><div class="kpi-val" id="metricUsuarios"><?= number_format($tu) ?></div><div class="kpi-label">Personal</div></div>
                    <div class="kpi-card"><div class="kpi-top"><i class="ph-fill ph-package kpi-icon"></i><span class="kpi-tag">Catálogo</span></div><div class="kpi-val" id="metricProductos"><?= number_format($tp) ?></div><div class="kpi-label">Productos</div></div>
                    <div class="kpi-card"><div class="kpi-top"><i class="ph-fill ph-money kpi-icon"></i><span class="kpi-tag">Hoy</span></div><div class="kpi-val" id="metricIngresos">$<?= number_format($ih, 2) ?></div><div class="kpi-label" id="metricVentasCount"><?= number_format($vh) ?> ventas realizadas</div></div>
                </div>
                <div class="grid-2col">
                    <div class="panel"><div class="panel-head"><div><h2 class="panel-title">Ventas (7 días)</h2><p class="panel-sub">Ingresos consolidados de toda la red</p></div></div><div class="chart-box"><canvas id="salesChart"></canvas></div></div>
                    <div class="panel"><div class="panel-head"><div><h2 class="panel-title">Métodos de pago</h2><p class="panel-sub">Distribución histórica</p></div></div><div class="donut-wrap"><canvas id="paymentChart"></canvas><div class="donut-center"><div class="num" id="donutVentasCount"><?= number_format($vh) ?></div><div class="lbl">ventas</div></div></div></div>
                </div>
                <div class="grid-2col">
                    <div class="panel"><div class="panel-head"><div><h2 class="panel-title">Atención pendiente</h2><p class="panel-sub">Productos bajos o agotados</p></div><button class="chip-btn" data-goto="view-inventario">Ir al inventario</button></div><div id="lowStockList" class="stock-list">Cargando...</div></div>
                    <div class="panel">
                        <div class="panel-head"><div><h2 class="panel-title">Salud del Inventario</h2><p class="panel-sub">Métricas globales de mercancía</p></div></div>
                        <div class="info-strip"><div><div class="kpi-label">Total de Unidades Físicas</div><div class="kpi-val" style="font-size:22px; margin-top:4px;" id="metricInventarioTotal"><?= number_format($ti) ?></div></div><i class="ph-fill ph-cube kpi-icon"></i></div>
                        <div class="info-strip"><div><div class="kpi-label">Valor Estimado (Precio Venta)</div><div class="kpi-val" style="font-size:22px; margin-top:4px; color:var(--success);" id="metricValorInventario">$<?= number_format($valor_inv, 2) ?></div></div><i class="ph-fill ph-chart-pie-slice kpi-icon"></i></div>
                    </div>
                </div>
            </section>

            <!-- SUCURSALES -->
            <section id="view-sucursales" class="view-section">
                <div class="header-row">
                    <div><h1 class="title"><i class="ph ph-storefront"></i> Sucursales</h1><p class="subtitle">Crea, consulta, modifica y elimina puntos de venta.</p></div>
                    <button class="btn-primary" id="addSucursal"><i class="ph ph-plus"></i> Agregar sucursal</button>
                </div>
                <div class="panel"><table><thead><tr><th>Sucursal</th><th>Dirección</th><th>Teléfono</th><th>Contacto</th><th>Estado</th><th></th></tr></thead><tbody id="sucursalesTable"></tbody></table></div>
            </section>

            <!-- PERSONAL -->
            <section id="view-personal" class="view-section">
                <div class="header-row">
                    <div><h1 class="title"><i class="ph ph-users-three"></i> Personal</h1><p class="subtitle">Registra gerentes/cajeros y asígnalos a una sucursal.</p></div>
                    <button class="btn-primary" id="addPersonal"><i class="ph ph-plus"></i> Agregar personal</button>
                </div>
                <div class="panel"><table><thead><tr><th>Personal</th><th>Rol</th><th>Sucursal</th><th>Estado</th><th></th></tr></thead><tbody id="personalTable"></tbody></table></div>
            </section>

            <!-- INVENTARIO -->
            <section id="view-inventario" class="view-section">
                <div class="header-row"><div><h1 class="title"><i class="ph ph-package"></i> Inventario</h1><p class="subtitle">Consulta las existencias de todas las sucursales.</p></div></div>
                <div class="panel">
                    <div class="form-inline" style="margin-bottom:16px;">
                        <input class="search-input" id="inventorySearch" type="search" placeholder="Producto o código...">
                        <select class="select-input" id="inventoryBranch"><option value="">Todas las sucursales</option></select>
                    </div>
                    <table><thead><tr><th>Producto</th><th>Sucursal</th><th>Existencia</th><th>Precio</th><th>Nivel</th></tr></thead><tbody id="inventarioTable"></tbody></table>
                </div>
            </section>

            <!-- PRODUCTOS -->
            <section id="view-productos" class="view-section">
                <div class="header-row">
                    <div><h1 class="title"><i class="ph ph-tag"></i> Catálogo Global</h1><p class="subtitle">Registra los productos base de todo el sistema.</p></div>
                    <button class="btn-primary" id="addProducto"><i class="ph ph-plus"></i> Agregar producto</button>
                </div>
                <div class="panel">
                    <div class="form-inline" style="margin-bottom:16px;"><input class="search-input" id="productsSearch" type="search" placeholder="Buscar producto o código..."></div>
                    <table><thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Estado</th><th></th></tr></thead><tbody id="productosTable"></tbody></table>
                </div>
            </section>

            <!-- VENTAS -->
            <section id="view-ventas" class="view-section">
                <div class="header-row"><div><h1 class="title"><i class="ph ph-chart-line-up"></i> Historial de Ventas</h1><p class="subtitle">Supervisa las ventas de toda la red.</p></div></div>
                <div class="panel">
                    <div class="form-inline" style="margin-bottom:16px;">
                        <select class="select-input" id="salesBranch"><option value="">Todas las sucursales</option></select>
                        <input class="search-input" id="salesFrom" type="date"><input class="search-input" id="salesTo" type="date">
                    </div>
                    <table><thead><tr><th>Folio</th><th>Fecha</th><th>Sucursal</th><th>Personal</th><th>Pago</th><th>Total</th><th>Estado</th><th></th></tr></thead><tbody id="ventasTable"></tbody></table>
                </div>
            </section>

        </div>
    </main>
</div>

<script>
const endpoint = 'dashboard.php';
const $ = s => document.querySelector(s);
const escapeHtml = v => String(v ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
const money = v => '$' + Number(v || 0).toFixed(2);
const notify = (msg, icon='success') => Swal.fire({ toast:true, position:'top-end', icon, title: msg, showConfirmButton:false, timer:2600 });

async function request(actionStr, options = {}) {
    const r = await fetch(`${endpoint}?action=${actionStr}`, options);
    const text = await r.text();
    let data;
    try { data = JSON.parse(text); } catch(e) { console.error(text); throw new Error('[ERROR DE SISTEMA] Falló la lectura de la respuesta del servidor.'); }
    if (!data.ok) throw new Error(data.msg || 'No fue posible completar la operación.');
    return data;
}
const statusBadge = s => `<span class="badge ${String(s).toUpperCase().includes('INACT') ? 'b-red' : 'b-green'}">${escapeHtml(s)}</span>`;
const empty = (cols, text='No hay registros.') => `<tr><td colspan="${cols}" style="text-align:center;color:var(--ink-faint);padding:30px">${text}</td></tr>`;

// -------- TEMA --------
const body = document.body, btnTheme = $('#btnTheme');
btnTheme.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    const icon = btnTheme.querySelector('i');
    icon.classList.toggle('ph-moon'); icon.classList.toggle('ph-sun');
    localStorage.setItem('kion-admin-theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
});
if (localStorage.getItem('kion-admin-theme') === 'dark') { body.classList.add('dark-mode'); btnTheme.querySelector('i').classList.replace('ph-moon','ph-sun'); }

// -------- SIDEBAR: colapsar / móvil --------
const appLayout = $('#appLayout');
$('#btnCollapse').addEventListener('click', () => appLayout.classList.toggle('is-collapsed'));
$('#btnMobileMenu').addEventListener('click', () => appLayout.classList.add('mobile-open'));
$('#sidebarScrim').addEventListener('click', () => appLayout.classList.remove('mobile-open'));

// -------- NAVEGACIÓN --------
const navButtons = document.querySelectorAll('.nav-btn[data-target]');
const views = document.querySelectorAll('.view-section');
const titles = { 'view-general':'Resumen General', 'view-sucursales':'Sucursales', 'view-personal':'Personal', 'view-inventario':'Inventario', 'view-productos':'Catálogo Global', 'view-ventas':'Historial de Ventas' };
function switchView(targetId) {
    views.forEach(v => v.classList.remove('active'));
    navButtons.forEach(b => b.classList.remove('active'));
    document.getElementById(targetId).classList.add('active');
    const btn = document.querySelector(`.nav-btn[data-target="${targetId}"]`);
    if (btn) btn.classList.add('active');
    $('#topbarTitle').textContent = titles[targetId] || 'Panel';
    appLayout.classList.remove('mobile-open');
    if (targetId === 'view-general') loadDashboard();
    if (targetId === 'view-sucursales') loadSucursales();
    if (targetId === 'view-personal') loadPersonal();
    if (targetId === 'view-inventario') loadInventario();
    if (targetId === 'view-productos') loadProductos();
    if (targetId === 'view-ventas') loadVentas();
}
navButtons.forEach(btn => btn.addEventListener('click', e => switchView(e.currentTarget.dataset.target)));
document.addEventListener('click', e => { const g = e.target.closest('[data-goto]'); if (g) switchView(g.dataset.goto); });

// -------- DASHBOARD --------
let salesChartInstance = null, paymentChartInstance = null;
async function loadDashboard() {
    try {
        const { data } = await request('get_metrics');
        $('#metricSucursales').textContent = Number(data.ts).toLocaleString();
        $('#metricUsuarios').textContent = Number(data.tu).toLocaleString();
        $('#metricProductos').textContent = Number(data.tp).toLocaleString();
        $('#metricIngresos').textContent = money(data.ih);
        $('#metricVentasCount').textContent = Number(data.vh).toLocaleString() + ' ventas realizadas';
        $('#donutVentasCount').textContent = Number(data.vh).toLocaleString();
        $('#metricInventarioTotal').textContent = Number(data.ti).toLocaleString();
        $('#metricValorInventario').textContent = money(data.valor_inv);
    } catch (e) { notify(e.message, 'error'); }
    loadLowStock();
}
async function loadLowStock() {
    try {
        const { data } = await request('get_inventarios');
        const low = data.filter(x => x.nivel !== 'OK').slice(0, 5);
        $('#lowStockList').innerHTML = low.length ? low.map(x => `<div class="stock-row"><span class="stock-thumb"><i class="ph ph-package"></i></span><div><div class="stock-name">${escapeHtml(x.producto)}</div><div class="stock-meta">${escapeHtml(x.sucursal)}</div></div><div class="stock-qty">${escapeHtml(x.existencias)}<small>mín. ${escapeHtml(x.stock_minimo)}</small></div></div>`).join('') : `<p class="subtitle" style="font-size:13px; padding:10px 0;">Todo el inventario está en niveles saludables.</p>`;
    } catch (e) {}
}
$('#refreshDashboard').addEventListener('click', () => { loadDashboard(); notify('Panel actualizado.'); });

// -------- SUCURSALES --------
async function loadSucursales() {
    try {
        const { data } = await request('get_sucursales');
        $('#sucursalesTable').innerHTML = data.length ? data.map(x => `<tr>
            <td><b>${escapeHtml(x.nombre)}</b></td><td>${escapeHtml(x.direccion||'-')}</td><td>${escapeHtml(x.telefono||'-')}</td><td>${escapeHtml(x.contacto||'-')}</td>
            <td>${statusBadge(x.estado)}</td>
            <td class="row-actions"><button class="chip-btn" data-edit-suc='${JSON.stringify(x).replace(/'/g,'&#39;')}'>Editar</button><button class="icon-x" data-del-suc="${x.id_sucursal}"><i class="ph ph-trash"></i></button></td>
        </tr>`).join('') : empty(6);
    } catch (e) { notify(e.message, 'error'); }
}
async function sucursalModal(record = null) {
    const editing = !!record;
    const { value: form } = await Swal.fire({
        title: editing ? 'Editar Sucursal' : 'Nueva Sucursal',
        html: `<div style="text-align:left;">
            <label><b>Nombre *</b></label><input id="swal-nombre" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.nombre):''}">
            <label><b>Dirección</b></label><input id="swal-dir" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.direccion||''):''}">
            <label><b>Teléfono</b></label><input id="swal-tel" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.telefono||''):''}">
            <label><b>Contacto</b></label><input id="swal-cont" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.contacto||''):''}">
            ${editing ? `<label><b>Estado</b></label><select id="swal-estado" class="swal2-input" style="width:100%;margin:6px 0;"><option ${record.estado==='ACTIVA'?'selected':''}>ACTIVA</option><option ${record.estado==='INACTIVA'?'selected':''}>INACTIVA</option></select>` : ''}
        </div>`,
        confirmButtonText: 'Guardar', confirmButtonColor: 'var(--coffee-main)', showCancelButton: true, cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const nombre = document.getElementById('swal-nombre').value.trim();
            if (!nombre) { Swal.showValidationMessage('El nombre es obligatorio.'); return false; }
            const out = { nombre, direccion: document.getElementById('swal-dir').value, telefono: document.getElementById('swal-tel').value, contacto: document.getElementById('swal-cont').value };
            if (editing) { out.id = record.id_sucursal; out.estado = document.getElementById('swal-estado').value; }
            return out;
        }
    });
    if (!form) return;
    try {
        const fd = new FormData(); Object.entries(form).forEach(([k,v]) => fd.append(k,v));
        const d = await request(editing ? 'update_sucursal' : 'create_sucursal', { method:'POST', body: fd });
        notify(d.msg); loadSucursales(); loadDashboard(); loadBranchSelects();
    } catch (e) { notify(e.message, 'error'); }
}
$('#addSucursal').addEventListener('click', () => sucursalModal());
document.addEventListener('click', async e => {
    const editBtn = e.target.closest('[data-edit-suc]');
    if (editBtn) sucursalModal(JSON.parse(editBtn.dataset.editSuc));
    const delBtn = e.target.closest('[data-del-suc]');
    if (delBtn) {
        const conf = await Swal.fire({ title:'¿Eliminar sucursal?', text:'Esta acción no se puede deshacer.', icon:'warning', showCancelButton:true, confirmButtonColor:'var(--danger)', confirmButtonText:'Sí, eliminar', cancelButtonText:'Cancelar' });
        if (!conf.isConfirmed) return;
        try {
            const fd = new FormData(); fd.append('id', delBtn.dataset.delSuc);
            const d = await request('delete_sucursal', { method:'POST', body:fd });
            notify(d.msg); loadSucursales(); loadDashboard(); loadBranchSelects();
        } catch (err) { notify(err.message, 'error'); }
    }
});

// -------- PERSONAL --------
async function loadPersonal() {
    try {
        const { data } = await request('get_gerentes');
        $('#personalTable').innerHTML = data.length ? data.map(x => `<tr>
            <td><b>${escapeHtml(x.nombre)} ${escapeHtml(x.apellido||'')}</b><br><small style="color:var(--ink-faint)">${escapeHtml(x.correo)}</small></td>
            <td>${escapeHtml(x.rol||'-')}</td><td>${escapeHtml(x.sucursal)}</td><td>${statusBadge(x.estado)}</td>
            <td class="row-actions"><button class="chip-btn" data-edit-per='${JSON.stringify(x).replace(/'/g,'&#39;')}'>Editar</button><button class="icon-x" data-del-per="${x.id_usuario}"><i class="ph ph-trash"></i></button></td>
        </tr>`).join('') : empty(5);
    } catch (e) { notify(e.message, 'error'); }
}
async function personalModal(record = null) {
    const editing = !!record;
    let roles = [], sucursales = [];
    try { [roles, sucursales] = await Promise.all([request('get_roles'), request('get_sucursales_simple')]); } catch(e) { notify(e.message,'error'); return; }
    const roleOptions = roles.data.map(r => `<option value="${r.id_rol}" ${editing && +record.id_rol===+r.id_rol?'selected':''}>${escapeHtml(r.nombre)}</option>`).join('');
    const branchOptions = '<option value="">-- Sin sucursal (solo Admin) --</option>' + sucursales.data.map(s => `<option value="${s.id_sucursal}" ${editing && +record.id_sucursal===+s.id_sucursal?'selected':''}>${escapeHtml(s.nombre)}</option>`).join('');

    const { value: form } = await Swal.fire({
        title: editing ? 'Editar Personal' : 'Nuevo Personal',
        html: `<div style="text-align:left;">
            <label><b>Nombre *</b></label><input id="swal-nom" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.nombre):''}">
            <label><b>Apellido</b></label><input id="swal-ape" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.apellido||''):''}">
            <label><b>Correo *</b></label><input id="swal-correo" type="email" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.correo):''}">
            <label><b>Rol *</b></label><select id="swal-rol" class="swal2-input" style="width:100%;margin:6px 0 12px;">${roleOptions}</select>
            <label><b>Sucursal</b></label><select id="swal-suc" class="swal2-input" style="width:100%;margin:6px 0 12px;">${branchOptions}</select>
            <label><b>Contraseña ${editing?'(dejar vacío para no cambiar)':'*'}</b></label><input id="swal-pass" type="password" class="swal2-input" style="width:100%;margin:6px 0 12px;">
            ${editing ? `<label><b>Estado</b></label><select id="swal-estado" class="swal2-input" style="width:100%;margin:6px 0;"><option ${record.estado==='ACTIVO'?'selected':''}>ACTIVO</option><option ${record.estado==='INACTIVO'?'selected':''}>INACTIVO</option></select>` : ''}
        </div>`,
        confirmButtonText: 'Guardar', confirmButtonColor: 'var(--coffee-main)', showCancelButton: true, cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const nombre = document.getElementById('swal-nom').value.trim();
            const correo = document.getElementById('swal-correo').value.trim();
            if (!nombre || !correo) { Swal.showValidationMessage('Nombre y correo son obligatorios.'); return false; }
            const out = { nombre, apellido: document.getElementById('swal-ape').value, correo, id_rol: document.getElementById('swal-rol').value, id_sucursal: document.getElementById('swal-suc').value, password: document.getElementById('swal-pass').value };
            if (editing) { out.id = record.id_usuario; out.estado = document.getElementById('swal-estado').value; }
            return out;
        }
    });
    if (!form) return;
    try {
        const fd = new FormData(); Object.entries(form).forEach(([k,v]) => fd.append(k,v));
        const d = await request(editing ? 'update_gerente' : 'create_gerente', { method:'POST', body: fd });
        notify(d.msg); loadPersonal(); loadDashboard();
    } catch (e) { notify(e.message, 'error'); }
}
$('#addPersonal').addEventListener('click', () => personalModal());
document.addEventListener('click', async e => {
    const editBtn = e.target.closest('[data-edit-per]');
    if (editBtn) personalModal(JSON.parse(editBtn.dataset.editPer));
    const delBtn = e.target.closest('[data-del-per]');
    if (delBtn) {
        const conf = await Swal.fire({ title:'¿Eliminar registro?', text:'Se eliminará permanentemente este usuario.', icon:'warning', showCancelButton:true, confirmButtonColor:'var(--danger)', confirmButtonText:'Sí, eliminar', cancelButtonText:'Cancelar' });
        if (!conf.isConfirmed) return;
        try {
            const fd = new FormData(); fd.append('id', delBtn.dataset.delPer);
            const d = await request('delete_gerente', { method:'POST', body:fd });
            notify(d.msg); loadPersonal(); loadDashboard();
        } catch (err) { notify(err.message, 'error'); }
    }
});

// -------- INVENTARIO --------
async function loadBranchSelects() {
    try {
        const { data } = await request('get_sucursales_simple');
        const options = data.map(x => `<option value="${x.id_sucursal}">${escapeHtml(x.nombre)}</option>`).join('');
        ['#inventoryBranch','#salesBranch'].forEach(s => $(s).innerHTML = '<option value="">Todas las sucursales</option>' + options);
    } catch (e) {}
}
async function loadInventario() {
    try {
        const q = $('#inventorySearch').value, suc = $('#inventoryBranch').value;
        const { data } = await request(`get_inventarios&q=${encodeURIComponent(q)}&sucursal=${encodeURIComponent(suc)}`);
        $('#inventarioTable').innerHTML = data.length ? data.map(x => {
            const cls = x.nivel === 'OK' ? 'b-green' : (x.nivel === 'BAJO' ? 'b-yellow' : 'b-red');
            return `<tr><td><b>${escapeHtml(x.producto)}</b><br><small style="color:var(--ink-faint)">${escapeHtml(x.codigo)}</small></td><td>${escapeHtml(x.sucursal)}</td><td><b>${escapeHtml(x.existencias)}</b> <small>/ mín. ${escapeHtml(x.stock_minimo)}</small></td><td>${money(x.precio)}</td><td><span class="badge ${cls}">${escapeHtml(x.nivel)}</span></td></tr>`;
        }).join('') : empty(5);
    } catch (e) { notify(e.message, 'error'); }
}
['#inventorySearch','#inventoryBranch'].forEach(s => $(s).addEventListener('input', loadInventario));

// -------- PRODUCTOS --------
async function loadProductos() {
    try {
        const q = $('#productsSearch').value;
        const { data } = await request(`get_productos&q=${encodeURIComponent(q)}`);
        $('#productosTable').innerHTML = data.length ? data.map(x => `<tr>
            <td><b>${escapeHtml(x.codigo)}</b></td><td><b>${escapeHtml(x.nombre)}</b></td><td>${escapeHtml(x.categoria)}</td><td>${money(x.precio)}</td>
            <td>${statusBadge(x.estado)}</td>
            <td class="row-actions"><button class="chip-btn" data-view-prod="${x.id_producto}">Ver stock</button><button class="chip-btn" data-edit-prod='${JSON.stringify(x).replace(/'/g,'&#39;')}'>Editar</button><button class="icon-x" data-del-prod="${x.id_producto}"><i class="ph ph-trash"></i></button></td>
        </tr>`).join('') : empty(6);
    } catch (e) { notify(e.message, 'error'); }
}
$('#productsSearch').addEventListener('input', loadProductos);

async function productoModal(record = null) {
    const editing = !!record;
    let categorias = [], sucursales = [], asignadas = [];
    try {
        const promises = [request('get_categorias'), request('get_sucursales_simple')];
        if (editing) promises.push(request(`get_producto_detalle&id=${record.id_producto}`));
        const results = await Promise.all(promises);
        categorias = results[0].data; sucursales = results[1].data;
        if (editing) asignadas = results[2].data.map(x => x.id_sucursal);
    } catch (e) { notify(e.message, 'error'); return; }

    const catOptions = categorias.map(c => `<option value="${c.id_categoria}" ${editing && +record.id_categoria===+c.id_categoria?'selected':''}>${escapeHtml(c.nombre)}</option>`).join('');
    const branchChecks = sucursales.map(s => `<label style="font-size:13px; display:flex; align-items:center; gap:6px;"><input type="checkbox" class="swal-suc-chk" value="${s.id_sucursal}" ${asignadas.includes(s.id_sucursal)?'checked':''}> ${escapeHtml(s.nombre)}</label>`).join('');

    const { value: form } = await Swal.fire({
        title: editing ? 'Editar Producto' : 'Nuevo Producto',
        html: `<div style="text-align:left;">
            <label><b>Código *</b></label><input id="swal-codigo" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.codigo):''}">
            <label><b>Nombre *</b></label><input id="swal-nombre" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.nombre):''}">
            <label><b>Descripción</b></label><input id="swal-desc" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?escapeHtml(record.descripcion||''):''}">
            <label><b>Categoría</b></label><select id="swal-cat" class="swal2-input" style="width:100%;margin:6px 0 12px;">${catOptions}</select>
            <label><b>Precio base *</b></label><input id="swal-precio" type="number" step="0.01" class="swal2-input" style="width:100%;margin:6px 0 12px;" value="${editing?record.precio:''}">
            <div style="padding:14px; background:var(--bg-app); border:1px solid var(--border-soft); border-radius:10px; margin:10px 0;">
                <label><b>Asignar a sucursales:</b></label>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:8px;">${branchChecks || '<span style="font-size:12px;color:var(--ink-faint)">No hay sucursales activas.</span>'}</div>
            </div>
            ${editing ? `<label><b>Estado</b></label><select id="swal-estado" class="swal2-input" style="width:100%;margin:6px 0;"><option ${record.estado==='ACTIVO'?'selected':''}>ACTIVO</option><option ${record.estado==='INACTIVO'?'selected':''}>INACTIVO</option></select>` : ''}
        </div>`,
        confirmButtonText: 'Guardar', confirmButtonColor: 'var(--coffee-main)', showCancelButton: true, cancelButtonText: 'Cancelar', width: 520,
        preConfirm: () => {
            const codigo = document.getElementById('swal-codigo').value.trim();
            const nombre = document.getElementById('swal-nombre').value.trim();
            if (!codigo || !nombre) { Swal.showValidationMessage('Código y nombre son obligatorios.'); return false; }
            const sucs = Array.from(document.querySelectorAll('.swal-suc-chk:checked')).map(c => c.value);
            const out = { codigo, nombre, descripcion: document.getElementById('swal-desc').value, id_categoria: document.getElementById('swal-cat').value, precio: document.getElementById('swal-precio').value, sucursales: sucs };
            if (editing) { out.id = record.id_producto; out.estado = document.getElementById('swal-estado').value; }
            return out;
        }
    });
    if (!form) return;
    try {
        const fd = new FormData();
        Object.entries(form).forEach(([k,v]) => { if (k === 'sucursales') { v.forEach(s => fd.append('sucursales[]', s)); } else { fd.append(k, v); } });
        const d = await request(editing ? 'update_producto' : 'create_producto', { method:'POST', body: fd });
        notify(d.msg); loadProductos(); loadInventario(); loadDashboard();
    } catch (e) { notify(e.message, 'error'); }
}
$('#addProducto').addEventListener('click', () => productoModal());
document.addEventListener('click', async e => {
    const editBtn = e.target.closest('[data-edit-prod]');
    if (editBtn) productoModal(JSON.parse(editBtn.dataset.editProd));
    const delBtn = e.target.closest('[data-del-prod]');
    if (delBtn) {
        const conf = await Swal.fire({ title:'¿Eliminar producto?', text:'Se eliminará el producto y su inventario asociado.', icon:'warning', showCancelButton:true, confirmButtonColor:'var(--danger)', confirmButtonText:'Sí, eliminar', cancelButtonText:'Cancelar' });
        if (!conf.isConfirmed) return;
        try {
            const fd = new FormData(); fd.append('id', delBtn.dataset.delProd);
            const d = await request('delete_producto', { method:'POST', body:fd });
            notify(d.msg); loadProductos(); loadInventario(); loadDashboard();
        } catch (err) { notify(err.message, 'error'); }
    }
    const viewBtn = e.target.closest('[data-view-prod]');
    if (viewBtn) {
        try {
            const { data } = await request(`get_producto_detalle&id=${viewBtn.dataset.viewProd}`);
            Swal.fire({ title: 'Stock por sucursal', html: data.length ? `<table style="width:100%;text-align:left;"><tr><th>Sucursal</th><th style="text-align:right">Existencias</th></tr>${data.map(x=>`<tr><td>${escapeHtml(x.sucursal)}</td><td style="text-align:right"><b>${x.existencias}</b></td></tr>`).join('')}</table>` : '<p>No está asignado a ninguna sucursal.</p>' });
        } catch (err) { notify(err.message, 'error'); }
    }
});

// -------- VENTAS --------
async function loadVentas() {
    try {
        const p = new URLSearchParams({ sucursal: $('#salesBranch').value, desde: $('#salesFrom').value, hasta: $('#salesTo').value });
        const { data } = await request(`get_ventas&${p}`);
        $('#ventasTable').innerHTML = data.length ? data.map(x => `<tr>
            <td>#${x.id_venta}</td><td>${new Date(x.fecha_hora).toLocaleString('es-MX')}</td><td>${escapeHtml(x.sucursal)}</td><td>${escapeHtml(x.empleado)}</td><td>${escapeHtml(x.metodo_pago)}</td>
            <td><b>${money(x.total)}</b></td><td>${statusBadge(x.estado)}</td>
            <td><button class="chip-btn" data-ver-venta="${x.id_venta}">Ver</button></td>
        </tr>`).join('') : empty(8);
    } catch (e) { notify(e.message, 'error'); }
}
['#salesBranch','#salesFrom','#salesTo'].forEach(s => $(s).addEventListener('change', loadVentas));
document.addEventListener('click', async e => {
    const verBtn = e.target.closest('[data-ver-venta]');
    if (!verBtn) return;
    try {
        const { data } = await request(`get_venta_detalle&id=${verBtn.dataset.verVenta}`);
        Swal.fire({ title: `Detalle Venta #${verBtn.dataset.verVenta}`, html: data.length ? `<table style="width:100%;text-align:left;"><tr><th>Producto</th><th>Cant</th><th style="text-align:right">Subtotal</th></tr>${data.map(x=>`<tr><td>${escapeHtml(x.producto)}</td><td>${x.cantidad}</td><td style="text-align:right">${money(x.subtotal)}</td></tr>`).join('')}</table>` : 'Sin datos.' });
    } catch (e) { notify(e.message, 'error'); }
});

// -------- INICIO --------
const initial = <?= json_encode(['labels' => $v7['labels'] ?? [], 'ingresos' => $v7['ingresos'] ?? [], 'payments' => $mp], JSON_UNESCAPED_UNICODE) ?>;
window.addEventListener('DOMContentLoaded', () => {
    const palette = ['#936545', '#547c5c', '#648ba6', '#b3832e', '#a66d7e'];
    if (window.Chart) {
        salesChartInstance = new Chart($('#salesChart'), {
            type: 'line',
            data: { labels: initial.labels || [], datasets: [{ label: 'Ingresos', data: initial.ingresos || [], borderColor: '#6c4630', backgroundColor: 'rgba(147,101,69,.14)', fill: true, tension: .38, pointRadius: 3 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
        });
        paymentChartInstance = new Chart($('#paymentChart'), {
            type: 'doughnut',
            data: { labels: (initial.payments || []).map(x => x.metodo), datasets: [{ data: (initial.payments || []).map(x => x.total_ventas), backgroundColor: palette, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '74%', plugins: { legend: { position: 'bottom' } } }
        });
    }
    loadBranchSelects();
    loadDashboard();
});
</script>
</body>
</html>