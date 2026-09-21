<?php
declare(strict_types=1);

// Escudo contra espacios en blanco que rompan el JSON
ob_start();

if (file_exists(__DIR__ . '/../../config/conexion_BD.php')) {
    require_once __DIR__ . '/../../config/conexion_BD.php';
} elseif (file_exists(__DIR__ . '/../../config/conexion.php')) {
    require_once __DIR__ . '/../../config/conexion.php';
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $out = ['ok' => false, 'msg' => 'Acción desconocida'];
    try {
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            throw new RuntimeException('[ERROR DE SISTEMA] No fue posible conectar con la base de datos. Revisa tus credenciales.');
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($action === 'get_metrics') {
            $ts = (int)$pdo->query("SELECT COUNT(*) FROM sucursales")->fetchColumn();
            $tu = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
            $tp = (int)$pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
            $inv_data = $pdo->query("SELECT COALESCE(SUM(i.existencias),0) AS piezas, COALESCE(SUM(i.existencias * p.precio),0) AS valor FROM inventarios i JOIN productos p ON p.id_producto = i.id_producto")->fetch(PDO::FETCH_ASSOC);
            $ti = (int)($inv_data['piezas'] ?? 0);
            $valor_inv = (float)($inv_data['valor'] ?? 0);
            
            $rv = $pdo->query("SELECT COUNT(*) AS n, COALESCE(SUM(total),0) AS t FROM ventas WHERE DATE(fecha_hora)=CURDATE()")->fetch();
            $out = [
                'ok' => true,
                'data' => [
                    'ts' => $ts, 'tu' => $tu, 'tp' => $tp, 'ti' => $ti, 'valor_inv' => $valor_inv,
                    'vh' => (int)($rv['n'] ?? 0),
                    'ih' => (float)($rv['t'] ?? 0)
                ]
            ];
            
        } elseif ($action === 'get_sucursales') {
            $stmt = $pdo->query("SELECT id_sucursal, nombre, direccion, telefono, contacto, estado FROM sucursales ORDER BY id_sucursal");
            $out = ['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            
        } elseif ($action === 'create_sucursal') {
            $nombre=trim($_POST['nombre']??''); $dir=trim($_POST['direccion']??''); $tel=trim($_POST['telefono']??''); $cont=trim($_POST['contacto']??'');
            if ($nombre==='') throw new Exception('[ERROR DE USUARIO] El nombre de la sucursal es obligatorio.');
            $pdo->prepare("INSERT INTO sucursales (nombre,direccion,telefono,contacto,estado) VALUES(?,?,?,?,'ACTIVA')")->execute([$nombre,$dir,$tel,$cont]);
            $out=['ok'=>true,'msg'=>'Sucursal registrada con éxito.'];
            
        } elseif ($action === 'update_sucursal') {
            $id=(int)($_POST['id']??0); $nombre=trim($_POST['nombre']??''); $tel=trim($_POST['telefono']??'');
            if(!$id||!$nombre) throw new Exception('[ERROR DE USUARIO] Faltan datos obligatorios para actualizar.');
            $est=($_POST['estado']??'ACTIVA')==='INACTIVA'?'INACTIVA':'ACTIVA';
            $pdo->prepare("UPDATE sucursales SET nombre=?,direccion=?,telefono=?,contacto=?,estado=? WHERE id_sucursal=?")->execute([$nombre,trim($_POST['direccion']??''),$tel,trim($_POST['contacto']??''),$est,$id]);
            $out=['ok'=>true,'msg'=>'Datos de la sucursal actualizados.'];
            
        } elseif ($action === 'delete_sucursal') {
            $id=(int)($_POST['id']??0); if(!$id) throw new Exception('[ERROR DE SISTEMA] ID de sucursal inválido.');
            $pdo->prepare("DELETE FROM sucursales WHERE id_sucursal=?")->execute([$id]);
            $out=['ok'=>true,'msg'=>'Sucursal eliminada.'];
            
        } elseif ($action === 'get_gerentes') {
            $st=$pdo->query("SELECT u.id_usuario,u.nombre,u.apellido,u.correo,u.estado,r.nombre AS rol,COALESCE(s.nombre,'-- Ninguna --') AS sucursal,u.id_sucursal,u.id_rol FROM usuarios u LEFT JOIN roles r ON r.id_rol=u.id_rol LEFT JOIN sucursales s ON s.id_sucursal=u.id_sucursal ORDER BY u.id_usuario");
            $out=['ok'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)];
            
        } elseif ($action === 'delete_gerente') {
            $id=(int)($_POST['id']??0);
            $pdo->prepare("DELETE FROM usuarios WHERE id_usuario=?")->execute([$id]);
            $out=['ok'=>true,'msg'=>'Registro del personal eliminado.'];

        } elseif ($action === 'get_roles') {
            try { $data = $pdo->query("SELECT id_rol, nombre FROM roles ORDER BY id_rol")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e) { $data = []; }
            $out=['ok'=>true,'data'=>$data];
            
        } elseif ($action === 'get_categorias') {
            try { $data = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e) { $data = []; }
            $out=['ok'=>true,'data'=>$data];
            
        } elseif ($action === 'get_productos') {
            $q=trim($_GET['q']??'');
            $sql="SELECT p.id_producto,p.codigo,p.nombre,p.descripcion,p.id_categoria,p.precio,p.estado,COALESCE(c.nombre, 'Sin categoría') AS categoria FROM productos p LEFT JOIN categorias c ON c.id_categoria=p.id_categoria";
            $params=[];
            if($q!==''){$sql.=" WHERE p.codigo LIKE ? OR p.nombre LIKE ?";$params[]="%$q%";$params[]="%$q%";}
            $sql.=" ORDER BY p.nombre ASC LIMIT 200";
            $st=$pdo->prepare($sql);$st->execute($params);
            $out=['ok'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)];
            
        } elseif ($action === 'create_producto' || $action === 'update_producto') {
            $id=(int)($_POST['id']??0); $codigo=trim($_POST['codigo']??''); $nombre=trim($_POST['nombre']??'');
            $descripcion=trim($_POST['descripcion']??''); $categoria=(int)($_POST['id_categoria']??0);
            $precio = (float)str_replace(',', '.', $_POST['precio'] ?? '0');
            $estado=($_POST['estado']??'ACTIVO')==='INACTIVO'?'INACTIVO':'ACTIVO';
            $sucs = $_POST['sucursales'] ?? []; 

            if($codigo===''||$nombre==='') throw new Exception('[ERROR DE USUARIO] El código y el nombre son obligatorios.');
            
            $pdo->beginTransaction();
            try {
                if($action==='create_producto'){
                    $pdo->prepare("INSERT INTO productos(codigo,nombre,descripcion,id_categoria,precio,estado) VALUES(?,?,?,?,?,?)")->execute([$codigo,$nombre,$descripcion?:null,$categoria>0?$categoria:null,$precio,$estado]);
                    $new_id = (int)$pdo->lastInsertId();
                    
                    if(!empty($sucs)){
                        $st_inv = $pdo->prepare("INSERT INTO inventarios(id_producto, id_sucursal, existencias, stock_minimo) VALUES(?,?,0,5)");
                        foreach($sucs as $sid) $st_inv->execute([$new_id, $sid]);
                    }
                    $out=['ok'=>true,'msg'=>'Producto registrado exitosamente. (Stock inicial en 0)'];
                } else {
                    $pdo->prepare("UPDATE productos SET codigo=?,nombre=?,descripcion=?,id_categoria=?,precio=?,estado=? WHERE id_producto=?")->execute([$codigo,$nombre,$descripcion?:null,$categoria>0?$categoria:null,$precio,$estado,$id]);
                    
                    if(!empty($sucs)){
                        $st_check = $pdo->prepare("SELECT COUNT(*) FROM inventarios WHERE id_producto=? AND id_sucursal=?");
                        $st_inv = $pdo->prepare("INSERT INTO inventarios(id_producto, id_sucursal, existencias, stock_minimo) VALUES(?,?,0,5)");
                        foreach($sucs as $sid){
                            $st_check->execute([$id, $sid]);
                            if($st_check->fetchColumn() == 0) $st_inv->execute([$id, $sid]);
                        }
                    }
                    $out=['ok'=>true,'msg'=>'Producto actualizado correctamente.'];
                }
                $pdo->commit();
            } catch(Exception $e) { $pdo->rollBack(); throw $e; }

        } elseif ($action === 'delete_producto') {
            $id=(int)($_POST['id']??0);
            if(!$id) throw new Exception('[ERROR DE SISTEMA] ID de producto inválido.');
            $pdo->prepare("DELETE FROM inventarios WHERE id_producto=?")->execute([$id]);
            $pdo->prepare("DELETE FROM productos WHERE id_producto=?")->execute([$id]);
            $out=['ok'=>true,'msg'=>'Producto eliminado permanentemente.'];

        } elseif ($action === 'get_sucursales_por_producto') {
            $id_p = (int)($_GET['id_producto'] ?? 0);
            $st = $pdo->prepare("SELECT s.id_sucursal, s.nombre FROM inventarios i JOIN sucursales s ON s.id_sucursal = i.id_sucursal WHERE i.id_producto = ?");
            $st->execute([$id_p]);
            $out = ['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'ajustar_stock') {
            $id_p = (int)$_POST['id_producto'];
            $id_s = (int)$_POST['id_sucursal'];
            $cant = (int)$_POST['cantidad'];
            $tipo = $_POST['tipo'];

            if(!$id_p || !$id_s) throw new Exception('[ERROR DE USUARIO] Debes seleccionar un producto y una sucursal válidos.');
            if($cant < 0) throw new Exception('[ERROR DE USUARIO] No puedes ingresar cantidades negativas.');

            $st = $pdo->prepare("SELECT existencias FROM inventarios WHERE id_producto=? AND id_sucursal=?");
            $st->execute([$id_p, $id_s]);
            $row = $st->fetch();

            if(!$row) throw new Exception("[ERROR DE USUARIO] ¡Lógica incorrecta! Intentaste agregar stock a una sucursal donde el producto no existe. Ve a 'Productos', edítalo y asígnale esta sucursal primero.");

            $nueva_cant = $row['existencias'];
            if($tipo === 'entrada') $nueva_cant += $cant;
            elseif($tipo === 'salida') $nueva_cant = max(0, $nueva_cant - $cant);
            elseif($tipo === 'reemplazo') $nueva_cant = $cant;

            $pdo->prepare("UPDATE inventarios SET existencias=? WHERE id_producto=? AND id_sucursal=?")->execute([$nueva_cant, $id_p, $id_s]);
            $out=['ok'=>true,'msg'=>"Stock actualizado correctamente. Nueva existencia: $nueva_cant unidades."];

        } elseif ($action === 'get_producto_detalle') {
            $id = (int)($_GET['id']??0);
            $st = $pdo->prepare("SELECT s.id_sucursal, s.nombre AS sucursal, i.existencias FROM inventarios i JOIN sucursales s ON s.id_sucursal = i.id_sucursal WHERE i.id_producto = ?");
            $st->execute([$id]);
            $out = ['ok'=>true, 'data'=>$st->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'get_sucursales_simple') {
            $out=['ok'=>true,'data'=>$pdo->query("SELECT id_sucursal,nombre FROM sucursales WHERE estado='ACTIVA' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC)];
        
        } elseif ($action === 'get_productos_simple') {
            $out=['ok'=>true,'data'=>$pdo->query("SELECT id_producto, nombre, codigo FROM productos WHERE estado='ACTIVO' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC)];

        } elseif ($action === 'create_gerente' || $action === 'update_gerente') {
            $id=(int)($_POST['id']??0); $nom=trim($_POST['nombre']??''); $cor=trim($_POST['correo']??''); $pas=trim($_POST['password']??''); $rol=(int)($_POST['id_rol']??0);
            $suc=($_POST['id_sucursal']??'')!==''?(int)$_POST['id_sucursal']:null;
            
            $rname = $pdo->query("SELECT nombre FROM roles WHERE id_rol = $rol")->fetchColumn() ?: '';
            $is_admin = (stripos($rname, 'admin') !== false);
            if (!$is_admin && !$suc) throw new Exception('[ERROR DE USUARIO] Los Gerentes y Cajeros DEBEN tener una sucursal asignada.');
            if ($is_admin) $suc = null;

            if ($action === 'create_gerente') {
                $pdo->prepare("INSERT INTO usuarios(nombre,apellido,correo,password_hash,id_rol,id_sucursal,estado)VALUES(?,?,?,?,?,?,'ACTIVO')")->execute([$nom,trim($_POST['apellido']??''),$cor,password_hash($pas,PASSWORD_BCRYPT),$rol,$suc]);
                $out=['ok'=>true,'msg'=>'Personal registrado correctamente.'];
            } else {
                $est=($_POST['estado']??'ACTIVO')==='INACTIVO'?'INACTIVO':'ACTIVO';
                $pdo->prepare("UPDATE usuarios SET nombre=?,apellido=?,correo=?,id_rol=?,id_sucursal=?,estado=? WHERE id_usuario=?")->execute([$nom,trim($_POST['apellido']??''),$cor,$rol,$suc,$est,$id]);
                if($pas!=='') $pdo->prepare("UPDATE usuarios SET password_hash=? WHERE id_usuario=?")->execute([password_hash($pas,PASSWORD_BCRYPT),$id]);
                $out=['ok'=>true,'msg'=>'Información del personal actualizada.'];
            }
            
        } elseif ($action === 'get_inventarios') {
            $q=trim($_GET['q']??''); $sid=(int)($_GET['sucursal']??0);
            $sql="SELECT i.id_inventario,p.nombre AS producto,p.codigo,s.nombre AS sucursal,i.existencias,i.stock_minimo,p.precio,CASE WHEN i.existencias<=0 THEN 'AGOTADO' WHEN i.existencias<=i.stock_minimo THEN 'BAJO' ELSE 'OK' END AS nivel FROM inventarios i JOIN productos p ON p.id_producto=i.id_producto JOIN sucursales s ON s.id_sucursal=i.id_sucursal WHERE 1=1";
            $params=[];
            if($q!==''){$sql.=" AND (p.nombre LIKE ? OR p.codigo LIKE ?)";$params[]="%$q%";$params[]="%$q%";}
            if($sid>0){$sql.=" AND i.id_sucursal=?";$params[]=$sid;}
            $sql.=" ORDER BY i.existencias ASC LIMIT 100";
            $st=$pdo->prepare($sql);$st->execute($params);
            $out=['ok'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)];
            
        } elseif ($action === 'get_ventas') {
            $sid=(int)($_GET['sucursal']??0);$desde=trim($_GET['desde']??'');$hasta=trim($_GET['hasta']??'');
            $sql="SELECT v.id_venta,v.fecha_hora,v.total,v.estado,s.nombre AS sucursal,mp.nombre AS metodo_pago,CONCAT(u.nombre,' ',COALESCE(u.apellido,'')) AS empleado FROM ventas v JOIN sucursales s ON s.id_sucursal=v.id_sucursal JOIN metodos_pago mp ON mp.id_metodo_pago=v.id_metodo_pago JOIN usuarios u ON u.id_usuario=v.id_usuario WHERE 1=1";
            $params=[];
            if($sid>0){$sql.=" AND v.id_sucursal=?";$params[]=$sid;}
            if($desde!==''){$sql.=" AND DATE(v.fecha_hora)>=?";$params[]=$desde;}
            if($hasta!==''){$sql.=" AND DATE(v.fecha_hora)<=?";$params[]=$hasta;}
            $sql.=" ORDER BY v.fecha_hora DESC LIMIT 200";
            $st=$pdo->prepare($sql);$st->execute($params);
            $out=['ok'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)];
        }
    } catch(Throwable $e){ 
        $msg = $e->getMessage();
        if(strpos($msg, 'Base table or view not found') !== false) {
            $msg = "[ERROR DE SISTEMA/CÓDIGO] Te falta crear una tabla en tu base de datos: " . str_replace('SQLSTATE[42S02]: Base table or view not found: 1146 Table', '', $msg);
        } elseif(strpos($msg, 'Column not found') !== false) {
            $msg = "[ERROR DE SISTEMA/CÓDIGO] Te falta una columna en tu tabla: " . str_replace('SQLSTATE[42S22]: Column not found: 1054 Unknown column', '', $msg);
        } elseif(strpos($msg, 'Integrity constraint violation') !== false) {
            $msg = "[ERROR DE USUARIO] Estás intentando eliminar un registro que ya está vinculado a otras partes del sistema (como una venta). Es mejor ponerlo como INACTIVO.";
        }
        $out = ['ok' => false, 'msg' => $msg]; 
    }
    
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

$ts=0;$tu=0;$tp=0;$vh=0;$ih=0.0;$ti=0;$valor_inv=0;$v7=[];$mp=[];
if(isset($pdo)){
    try{
        $ts=(int)$pdo->query("SELECT COUNT(*) FROM sucursales")->fetchColumn();
        $tu=(int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        $tp=(int)$pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
        $inv_data = $pdo->query("SELECT COALESCE(SUM(i.existencias),0) AS piezas, COALESCE(SUM(i.existencias * p.precio),0) AS valor FROM inventarios i JOIN productos p ON p.id_producto = i.id_producto")->fetch(PDO::FETCH_ASSOC);
        $ti = (int)($inv_data['piezas'] ?? 0); $valor_inv = (float)($inv_data['valor'] ?? 0);
        $rv=$pdo->query("SELECT COUNT(*) AS n,COALESCE(SUM(total),0) AS t FROM ventas WHERE DATE(fecha_hora)=CURDATE()")->fetch();
        $vh=(int)($rv['n']??0);$ih=(float)($rv['t']??0);
        for($i=6;$i>=0;$i--){
            $fd=date('Y-m-d',strtotime("-{$i} days"));$fl=date('d/m',strtotime("-{$i} days"));
            $r=$pdo->prepare("SELECT COUNT(*) AS n,COALESCE(SUM(total),0) AS t FROM ventas WHERE DATE(fecha_hora)=?");$r->execute([$fd]);$rr=$r->fetch();
            $v7['labels'][]=$fl;$v7['ventas'][]=(int)($rr['n']??0);$v7['ingresos'][]=(float)($rr['t']??0);
        }
        $mp=$pdo->query("SELECT mp.nombre AS metodo,COUNT(v.id_venta) AS total_ventas FROM metodos_pago mp LEFT JOIN ventas v ON v.id_metodo_pago=mp.id_metodo_pago GROUP BY mp.id_metodo_pago,mp.nombre")->fetchAll();
    }catch(PDOException $e){}
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KION · Panel de administración</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        :root{
            --coffee-950: oklch(21% 0.030 55); --coffee-900: oklch(26% 0.035 52); --coffee-800: oklch(32% 0.040 50); --coffee-700: oklch(40% 0.045 48); --coffee-600: oklch(47% 0.050 46); --coffee-500: oklch(55% 0.055 44); --coffee-400: oklch(64% 0.045 46); --coffee-300: oklch(74% 0.035 50); --coffee-200: oklch(84% 0.025 55); --coffee-100: oklch(91% 0.018 60);
            --cream-050: oklch(98% 0.010 75); --cream-100: oklch(96.5% 0.014 72); --cream-200: oklch(94% 0.018 70); --beige-100: oklch(92% 0.022 68);
            --ink: oklch(24% 0.028 50); --ink-soft: oklch(42% 0.030 48); --ink-faint: oklch(58% 0.022 50);
            --sage: oklch(72% 0.075 152); --sage-ink: oklch(34% 0.06 152); --sage-bg: oklch(94% 0.035 152);
            --rose: oklch(78% 0.075 15); --rose-ink: oklch(40% 0.09 20); --rose-bg: oklch(95% 0.028 15);
            --sky: oklch(80% 0.06 235); --sky-ink: oklch(38% 0.07 240); --sky-bg: oklch(95% 0.022 235);
            --honey: oklch(83% 0.09 85); --honey-ink: oklch(42% 0.09 70); --honey-bg: oklch(95% 0.045 90);
            --success: var(--sage-ink); --success-bg: var(--sage-bg); --warning: var(--honey-ink); --warning-bg: var(--honey-bg); --danger: oklch(50% 0.14 25); --danger-bg: oklch(95% 0.035 20);
            --bg-app: var(--cream-100); --bg-surface: oklch(99% 0.006 75); --bg-surface-raised: oklch(100% 0 0); --border-subtle: oklch(89% 0.016 65); --border-soft: oklch(84% 0.02 60);
            --font-display: 'Fraunces', 'Georgia', serif; --font-body: 'Plus Jakarta Sans', -apple-system, sans-serif;
            --r-sm: 10px; --r-md: 14px; --r-lg: 20px; --r-xl: 28px; --r-pill: 999px;
            
            --shadow-xs: 0 1px 2px oklch(30% 0.03 50 / 0.06); --shadow-sm: 0 2px 8px oklch(30% 0.03 50 / 0.07), 0 1px 2px oklch(30% 0.03 50 / 0.05); --shadow-md: 0 8px 24px oklch(30% 0.03 50 / 0.10), 0 2px 6px oklch(30% 0.03 50 / 0.06); --shadow-lg: 0 20px 48px oklch(25% 0.035 50 / 0.16), 0 4px 12px oklch(25% 0.03 50 / 0.08);
            --sidebar-w: 272px; --sidebar-w-collapsed: 84px; --header-h: 76px; --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1); --ease-out-quart: cubic-bezier(0.25, 1, 0.5, 1);
        }

        *, *::before, *::after{ box-sizing: border-box; }
        html{ -webkit-text-size-adjust: 100%; }
        body{ margin: 0; font-family: var(--font-body); background: var(--bg-app); color: var(--ink); -webkit-font-smoothing: antialiased; overflow-x: hidden; background-image: radial-gradient(circle at 8% 0%, oklch(97% 0.02 75 / 0.9), transparent 55%), radial-gradient(circle at 100% 30%, oklch(93% 0.03 60 / 0.6), transparent 45%); background-attachment: fixed; }
        img, svg{ display:block; max-width:100%; }
        button{ font: inherit; color: inherit; border: none; cursor: pointer; }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
        a{ color: inherit; text-decoration: none; }
        ul{ list-style:none; margin:0; padding:0; }
        h1,h2,h3,h4,p{ margin:0; }

        .app-shell{ display: grid; grid-template-columns: var(--sidebar-w) 1fr; min-height: 100vh; transition: grid-template-columns .38s var(--ease-out-expo); }
        .app-shell.is-collapsed{ grid-template-columns: var(--sidebar-w-collapsed) 1fr; }

        .sidebar{ position: sticky; top: 0; height: 100vh; background: linear-gradient(185deg, var(--coffee-950), var(--coffee-900) 65%, var(--coffee-800)); color: var(--cream-100); display: flex; flex-direction: column; padding: 22px 16px 20px; z-index: 40; transition: padding .38s var(--ease-out-expo); }
        .sidebar-brand{ display: flex; align-items: center; gap: 12px; padding: 6px 10px 22px; border-bottom: 1px solid oklch(100% 0 0 / 0.08); margin-bottom: 18px; }
        .brand-mark{ width: 40px; height: 40px; flex: none; border-radius: var(--r-md); background: linear-gradient(145deg, var(--honey), var(--rose)); display: grid; place-items: center; color: var(--coffee-950); font-family: var(--font-display); font-weight: 700; font-size: 18px; box-shadow: var(--shadow-sm); }
        .brand-text{ overflow: hidden; white-space: nowrap; }
        .brand-title{ font-family: var(--font-display); font-size: 17px; font-weight: 600; letter-spacing: .2px; }
        .brand-sub{ font-size: 11.5px; color: oklch(85% 0.02 60 / 0.6); letter-spacing: .3px; }
        
        .app-shell.is-collapsed .brand-text, .app-shell.is-collapsed .nav-label, .app-shell.is-collapsed .nav-section-title, .app-shell.is-collapsed .sidebar-foot-text{ opacity: 0; width: 0; pointer-events: none; }
        .nav-section-title{ font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px; color: oklch(85% 0.02 60 / 0.45); padding: 4px 12px 8px; white-space: nowrap; transition: opacity .2s ease; }
        .nav-list{ display: flex; flex-direction: column; gap: 3px; }
        .nav-item{ position: relative; }
        .nav-link{ display: flex; align-items: center; gap: 13px; padding: 11px 13px; border-radius: var(--r-md); color: oklch(94% 0.015 65 / 0.72); cursor: pointer; white-space: nowrap; transition: background .22s var(--ease-out-quart), color .22s ease, transform .22s var(--ease-out-quart); }
        .nav-link .nav-icon{ flex: none; width: 20px; height: 20px; opacity: .9; }
        .nav-label{ font-size: 14.5px; font-weight: 500; transition: opacity .2s ease; }
        .nav-link:hover{ background: oklch(100% 0 0 / 0.07); color: var(--cream-050); transform: translateX(2px); }
        .nav-item.active .nav-link{ background: linear-gradient(100deg, oklch(60% 0.06 70 / 0.9), oklch(55% 0.05 45 / 0.85)); color: var(--cream-050); box-shadow: var(--shadow-sm), inset 0 1px 0 oklch(100% 0 0 / 0.08); }
        .nav-item.active .nav-link::before{ content: ''; position: absolute; left: -16px; top: 50%; transform: translateY(-50%); width: 4px; height: 22px; border-radius: var(--r-pill); background: var(--honey); }
        .sidebar-scroll{ flex: 1; overflow-y: auto; overflow-x: hidden; margin: 0 -6px; padding: 0 6px; }
        
        .sidebar-collapse-btn{ position: absolute; top: 30px; right: -13px; width: 26px; height: 26px; border-radius: 50%; background: var(--bg-surface-raised); border: 1px solid var(--border-soft); color: var(--coffee-700); display: grid; place-items: center; cursor: pointer; box-shadow: var(--shadow-sm); z-index: 41; }
        .app-shell.is-collapsed .sidebar-collapse-btn svg{ transform: rotate(180deg); }
        .sidebar-collapse-btn svg{ width: 14px; height: 14px; transition: transform .35s var(--ease-out-expo); }

        .main{ display: flex; flex-direction: column; min-width: 0; }
        .topbar{ position: sticky; top: 0; z-index: 30; height: var(--header-h); display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 0 32px; background: oklch(99% 0.007 75 / 0.86); backdrop-filter: blur(14px) saturate(1.3); border-bottom: 1px solid var(--border-subtle); }
        .topbar-title-block{ min-width: 0; }
        .topbar-eyebrow{ font-size: 12px; color: var(--ink-faint); font-weight: 600; letter-spacing: .3px; }
        .topbar-title{ font-family: var(--font-display); font-size: 21px; font-weight: 600; color: var(--coffee-950); }
        .hamburger-btn{ display: none; width: 38px; height: 38px; border-radius: var(--r-sm); border: 1px solid var(--border-soft); background: var(--bg-surface-raised); align-items: center; justify-content: center; cursor: pointer; flex: none; }
        .hamburger-btn svg{ width: 19px; height: 19px; color: var(--coffee-800); }
        .topbar-actions{ display: flex; align-items: center; gap: 8px; margin-left: auto; }
        .sidebar-scrim{ display: none; position: fixed; inset: 0; background: oklch(20% 0.02 50 / 0.45); z-index: 39; opacity: 0; transition: opacity .3s ease; }

        .content{ padding: 28px 32px 60px; display: flex; flex-direction: column; max-width: 1520px; width: 100%; margin: 0 auto; }
        .dashboard-view { display: flex; flex-direction: column; gap: 24px; width: 100%; }
        .dashboard-view[hidden] { display: none !important; }
        
        .greeting-row{ display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
        .greeting-title{ font-family: var(--font-display); font-size: clamp(24px, 2.6vw, 32px); font-weight: 600; color: var(--coffee-950); display: flex; align-items: center; gap: 10px; }
        .wave{ display:inline-block; transform-origin: 70% 70%; animation: wave 2.4s ease-in-out .4s 1; }
        @keyframes wave{ 0%,60%,100%{ transform: rotate(0deg); } 10%{ transform: rotate(16deg); } 20%{ transform: rotate(-8deg); } 30%{ transform: rotate(16deg); } 40%{ transform: rotate(-4deg); } }
        .greeting-sub{ margin-top: 6px; font-size: 14.5px; color: var(--ink-soft); }
        .greeting-actions{ display: flex; gap: 10px; flex-wrap: wrap; }

        .btn{ display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 18px; border-radius: var(--r-md); font-size: 13.5px; font-weight: 700; border: 1px solid transparent; cursor: pointer; transition: transform .16s var(--ease-out-quart), box-shadow .2s ease, background .2s ease; white-space: nowrap; }
        .btn:active{ transform: scale(.96); }
        .btn-primary{ background: linear-gradient(155deg, var(--coffee-700), var(--coffee-900)); color: var(--cream-050); box-shadow: var(--shadow-sm); }
        .btn-primary:hover{ box-shadow: var(--shadow-md); transform: translateY(-1px); }
        .btn-ghost{ background: var(--bg-surface-raised); color: var(--coffee-800); border-color: var(--border-soft); }
        .btn-ghost:hover{ background: var(--cream-200); }
        .btn-soft{ background: var(--cream-200); color: var(--coffee-800); }
        .btn-sm{ padding: 7px 12px; font-size: 12.5px; border-radius: var(--r-sm); }

        .stat-grid{ display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 18px; }
        .stat-card{ background: var(--bg-surface-raised); border: 1px solid var(--border-subtle); border-radius: var(--r-lg); padding: 20px 20px 18px; box-shadow: var(--shadow-xs); transition: box-shadow .28s var(--ease-out-quart), transform .28s var(--ease-out-quart); }
        .stat-card:hover{ box-shadow: var(--shadow-md); transform: translateY(-3px); border-color: var(--coffee-200); }
        .stat-top{ display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
        .stat-icon{ width: 42px; height: 42px; border-radius: var(--r-md); display: grid; place-items: center; flex: none; font-size:18px; }
        .stat-icon.tone-coffee{ background: var(--coffee-100); color: var(--coffee-700); }
        .stat-icon.tone-sage{ background: var(--sage-bg); color: var(--sage-ink); }
        .stat-icon.tone-sky{ background: var(--sky-bg); color: var(--sky-ink); }
        .stat-icon.tone-honey{ background: var(--honey-bg); color: var(--honey-ink); }
        .stat-trend{ display: flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 700; padding: 4px 8px; border-radius: var(--r-pill); }
        .stat-trend.up{ background: var(--success-bg); color: var(--success); }
        .stat-value{ font-family: var(--font-display); font-size: 30px; font-weight: 600; color: var(--coffee-950); margin-top: 16px; font-variant-numeric: tabular-nums; }
        .stat-label{ font-size: 13px; color: var(--ink-soft); margin-top: 2px; }

        .panel{ background: var(--bg-surface-raised); border: 1px solid var(--border-subtle); border-radius: var(--r-xl); padding: 24px 24px 20px; box-shadow: var(--shadow-xs); display:flex; flex-direction:column; }
        .panel-head{ display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
        .panel-title{ font-family: var(--font-display); font-size: 18px; font-weight: 600; color: var(--coffee-950); }
        .panel-sub{ font-size: 12.5px; color: var(--ink-faint); margin-top: 3px; }
        
        .grid-2col{ display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 20px; }
        .grid-branch-table{ display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; align-items: start; }
        .chart-container { position: relative; width: 100%; height: 250px; }
        .donut-wrap{ position: relative; width: 100%; height: 250px; display:flex; align-items:center; justify-content:center; }
        .donut-center{ position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); text-align: center; pointer-events: none; }
        .donut-center .num{ font-family: var(--font-display); font-size: 24px; font-weight: 700; color: var(--coffee-950); }
        .donut-center .lbl{ font-size: 10.5px; color: var(--ink-faint); font-weight: 600; }

        .table-wrap{ overflow-x: auto; margin: 0 -4px; }
        table.data-table{ width: 100%; border-collapse: collapse; min-width: 640px; }
        .data-table thead th{ text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; color: var(--ink-faint); font-weight: 700; padding: 0 12px 12px; border-bottom: 1px solid var(--border-subtle); }
        .data-table tbody td{ padding: 14px 12px; border-bottom: 1px solid var(--border-subtle); vertical-align: middle; }
        .data-table tbody tr:hover{ background: var(--cream-100); }
        .data-table tbody tr:last-child td{ border-bottom: none; }

        .badge{ display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: var(--r-pill); }
        .badge .bdot{ width: 6px; height: 6px; border-radius: 50%; }
        .badge.active{ background: var(--success-bg); color: var(--success); }
        .badge.active .bdot{ background: var(--success); }
        .badge.inactive{ background: var(--danger-bg); color: var(--danger); }
        .badge.inactive .bdot{ background: var(--danger); }

        .row-actions{ display: flex; align-items: center; gap: 6px; }
        .chip-btn{ border: 1px solid var(--border-soft); background: var(--bg-surface-raised); color: var(--coffee-800); font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: var(--r-sm); cursor: pointer; transition: all .18s ease; }
        .chip-btn:hover{ background: var(--coffee-800); color: var(--cream-050); border-color: var(--coffee-800); }
        .icon-chip{ width: 30px; height: 30px; border-radius: var(--r-sm); border: 1px solid var(--border-soft); background: var(--bg-surface-raised); display: grid; place-items: center; cursor: pointer; color: var(--ink-soft); transition: all .18s ease; font-weight:bold;}
        .icon-chip:hover{ background: var(--danger-bg); color: var(--danger); border-color: var(--danger); }
        
        .stock-list{ display: flex; flex-direction: column; gap: 4px; }
        .stock-row{ display: grid; grid-template-columns: auto 1fr auto auto; align-items: center; gap: 14px; padding: 12px 6px; border-bottom: 1px solid var(--border-subtle); }
        .stock-row:last-child{ border-bottom: none; }
        .stock-thumb{ width: 40px; height: 40px; border-radius: 11px; flex: none; background: var(--cream-200); display: grid; place-items: center; color: var(--coffee-700); font-size:18px; }
        .stock-name{ font-size: 13.5px; font-weight: 700; color: var(--coffee-950); }
        .stock-meta{ font-size: 11.5px; color: var(--ink-faint); margin-top: 2px; }
        .stock-qty{ text-align: right; font-size: 13px; font-weight: 700; color: var(--ink); white-space: nowrap; }
        .stock-qty small{ display:block; font-weight: 500; color: var(--ink-faint); font-size: 10.5px; }

        .level-tag{ display: inline-flex; align-items: center; gap: 5px; font-size: 10.5px; font-weight: 700; padding: 4px 9px; border-radius: var(--r-pill); white-space: nowrap; }
        .level-tag .bdot{ width: 6px; height: 6px; border-radius: 50%; }
        .level-tag.critical{ background: var(--danger-bg); color: var(--danger); }
        .level-tag.critical .bdot{ background: var(--danger); }
        .level-tag.low{ background: var(--warning-bg); color: var(--warning); }
        .level-tag.low .bdot{ background: var(--warning); }
        .level-tag.watch{ background: var(--honey-bg); color: var(--honey-ink); }
        .level-tag.watch .bdot{ background: var(--honey-ink); }
        
        .modal-overlay { position: fixed; inset: 0; background: oklch(20% 0.02 50 / 0.5); backdrop-filter: blur(4px); z-index: 1000; display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: opacity .3s ease, visibility .3s ease; }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-content { background: var(--bg-surface-raised); border: 1px solid var(--border-subtle); border-radius: var(--r-xl); padding: 24px; width: 100%; max-width: 460px; box-shadow: var(--shadow-lg); transform: translateY(20px) scale(0.95); transition: transform .3s var(--ease-out-expo); }
        .modal-overlay.active .modal-content { transform: translateY(0) scale(1); }
        .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .modal-title { font-family: var(--font-display); font-size: 20px; font-weight: 600; color: var(--coffee-950); }
        .modal-close { background: transparent; border: none; font-size: 24px; line-height: 1; cursor: pointer; color: var(--ink-faint); padding: 0; display: grid; place-items: center; width: 32px; height: 32px; border-radius: 50%; }
        .modal-close:hover { color: var(--danger); background: var(--danger-bg); }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--coffee-900); margin-bottom: 6px; }
        .form-input, .form-select { width: 100%; padding: 10px 14px; background: var(--cream-200); border: 1px solid var(--border-soft); border-radius: var(--r-md); font-family: inherit; font-size: 14px; color: var(--ink); }
        .form-input:focus, .form-select:focus { outline: none; border-color: var(--coffee-400); box-shadow: 0 0 0 3px oklch(70% 0.04 55 / 0.15); background: var(--bg-surface-raised); }
        .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; }
        
        .crud-toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 1010; display: flex; flex-direction: column; gap: 10px; }
        .crud-toast { background: var(--bg-surface-raised); border: 1px solid var(--border-subtle); border-left: 4px solid var(--coffee-500); padding: 14px 18px; border-radius: var(--r-md); box-shadow: var(--shadow-md); color: var(--ink); font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 12px; transform: translateX(110%); transition: transform .4s var(--ease-out-expo); max-width:420px; }
        .crud-toast.show { transform: translateX(0); }
        .crud-toast.success { border-left-color: var(--success); }
        .crud-toast.error { border-left-color: var(--danger); }
        
        @media (max-width: 1180px){ .grid-2col, .grid-branch-table{ grid-template-columns: 1fr; } }
        @media (max-width: 980px){
          .app-shell{ grid-template-columns: 1fr; }
          .sidebar{ position: fixed; left: 0; top: 0; height: 100vh; width: 280px; transform: translateX(-100%); transition: transform .35s var(--ease-out-expo); box-shadow: var(--shadow-lg); }
          .app-shell.mobile-open .sidebar{ transform: translateX(0); }
          .app-shell.mobile-open .sidebar-scrim{ display: block; opacity: 1; }
          .sidebar-collapse-btn{ display: none; }
          .hamburger-btn{ display: inline-flex; }
        }
    </style>
</head>
<body>
<div class="app-shell" id="appShell">
    <aside class="sidebar" aria-label="Navegación principal">
        <button class="sidebar-collapse-btn" id="btnCollapseSidebar" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <a class="sidebar-brand" href="home.php">
            <div class="brand-mark">🐾</div><div class="brand-text"><div class="brand-title">KION</div><div class="brand-sub">ADMINISTRADOR GENERAL</div></div>
        </a>
        <div class="sidebar-scroll">
            <div class="nav-section-title">Panel principal</div>
            <ul class="nav-list">
                <li class="nav-item active"><a class="nav-link nav-trigger" href="#general" data-view="general"><span class="nav-icon">▦</span><span class="nav-label">Resumen general</span></a></li>
            </ul>
            <div class="nav-section-title" style="margin-top:20px">Gestión</div>
           <ul class="nav-list">
    <li class="nav-item"><a class="nav-link nav-trigger" href="#sucursales" data-view="sucursales"><span class="nav-icon">⌂</span><span class="nav-label">Sucursales</span></a></li>
    <li class="nav-item"><a class="nav-link nav-trigger" href="#usuarios" data-view="usuarios"><span class="nav-icon">♙</span><span class="nav-label">Personal</span></a></li>
    <li class="nav-item"><a class="nav-link nav-trigger" href="#inventario" data-view="inventario"><span class="nav-icon">▤</span><span class="nav-label">Inventario</span></a></li>
    <li class="nav-item"><a class="nav-link nav-trigger" href="#productos" data-view="productos"><span class="nav-icon">□</span><span class="nav-label">Productos</span></a></li>
    <li class="nav-item"><a class="nav-link nav-trigger" href="#ventas" data-view="ventas"><span class="nav-icon">↗</span><span class="nav-label">Ventas</span></a></li>
            </ul>
        </div>
    </aside>
    <div class="sidebar-scrim" id="sidebarScrim"></div>
    <main class="main">
        <header class="topbar">
            <button class="hamburger-btn" id="btnMobileMenu" type="button">☰</button>
            <div class="topbar-title-block"><div class="topbar-eyebrow" id="topbarEyebrow">Panel principal</div><div class="topbar-title" id="topbarTitle">Resumen general</div></div>
        </header>

        <section class="content" id="dashboardContent">
            <!-- GENERAL -->
            <section class="dashboard-view" data-view-panel="general">
                <div class="greeting-row"><div><h1 class="greeting-title">Hola, administración <span class="wave">👋</span></h1><p class="greeting-sub">Este es el pulso operativo de KION al día de hoy.</p></div><div class="greeting-actions"><button class="btn btn-ghost" type="button" id="refreshDashboard">↻ Actualizar</button></div></div>
                <div class="stat-grid">
                    <article class="stat-card"><div class="stat-top"><span class="stat-icon tone-coffee">⌂</span><span class="stat-trend up">Red</span></div><div class="stat-value" id="metricSucursales"><?= number_format($ts) ?></div><div class="stat-label">Sucursales</div></article>
                    <article class="stat-card"><div class="stat-top"><span class="stat-icon tone-sage">♙</span><span class="stat-trend up">Equipo</span></div><div class="stat-value" id="metricUsuarios"><?= number_format($tu) ?></div><div class="stat-label">Personal</div></article>
                    <article class="stat-card"><div class="stat-top"><span class="stat-icon tone-sky">▤</span><span class="stat-trend up">Catálogo</span></div><div class="stat-value" id="metricProductos"><?= number_format($tp) ?></div><div class="stat-label">Productos</div></article>
                    <article class="stat-card"><div class="stat-top"><span class="stat-icon tone-honey">↗</span><span class="stat-trend up">Hoy</span></div><div class="stat-value" id="metricIngresos">$<?= number_format($ih, 2) ?></div><div class="stat-label" id="metricVentasCount"><?= number_format($vh) ?> ventas realizadas</div></article>
                </div>
                <div class="grid-2col">
                    <article class="panel">
                        <div class="panel-head"><div><h2 class="panel-title">Ventas (7 días)</h2></div></div>
                        <div class="chart-container"><canvas id="salesChart"></canvas></div>
                    </article>
                    <article class="panel">
                        <div class="panel-head"><div><h2 class="panel-title">Métodos de pago</h2></div></div>
                        <div class="donut-wrap"><canvas id="paymentChart"></canvas><div class="donut-center"><div class="num" id="donutVentasCount"><?= number_format($vh) ?></div><div class="lbl">ventas</div></div></div>
                    </article>
                </div>
                <div class="grid-branch-table">
                    <article class="panel"><div class="panel-head"><div><h2 class="panel-title">Atención pendiente</h2><p class="panel-sub">Productos bajos o agotados</p></div><button class="btn btn-soft btn-sm nav-trigger" data-view="inventario">Ir al inventario</button></div><div id="lowStockList" class="stock-list">Cargando...</div></article>
                    <article class="panel">
                        <div class="panel-head"><div><h2 class="panel-title">Salud del Inventario</h2><p class="panel-sub">Métricas globales de mercancía</p></div></div>
                        <div style="display:flex; justify-content:space-between; align-items:flex-end; border-bottom:1px solid var(--border-soft); padding-bottom:15px; margin-bottom:15px;">
                            <div><div class="stat-label">Total de Unidades Físicas</div><div class="stat-value" style="margin-top:5px; font-size:24px;" id="metricInventarioTotal"><?= number_format($ti) ?></div></div>
                            <span class="stat-icon tone-sky">▤</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                            <div><div class="stat-label">Valor Estimado (Precio Venta)</div><div class="stat-value" style="margin-top:5px; font-size:24px; color:var(--success);" id="metricValorInventario">$<?= number_format($valor_inv, 2) ?></div></div>
                            <span class="stat-icon tone-sage">$</span>
                        </div>
                    </article>
                </div>
            </section>

            <!-- SUCURSALES -->
            <section class="dashboard-view" data-view-panel="sucursales" hidden>
                <div class="greeting-row"><div><h1 class="greeting-title">Sucursales</h1><p class="greeting-sub">Administra los puntos de venta.</p></div><button class="btn btn-primary" id="addSucursal">+ Agregar sucursal</button></div>
                <article class="panel"><div class="table-wrap"><table class="data-table"><thead><tr><th>Sucursal</th><th>Dirección</th><th>Teléfono</th><th>Contacto</th><th>Estado</th><th></th></tr></thead><tbody id="sucursalesTable"></tbody></table></div></article>
            </section>

            <!-- PERSONAL -->
            <section class="dashboard-view" data-view-panel="usuarios" hidden>
                <div class="greeting-row"><div><h1 class="greeting-title">Personal</h1><p class="greeting-sub">Gestiona roles y asignaciones del equipo.</p></div><button class="btn btn-primary" id="addUsuario">+ Agregar personal</button></div>
                <article class="panel"><div class="table-wrap"><table class="data-table"><thead><tr><th>Personal</th><th>Rol</th><th>Sucursal</th><th>Estado</th><th></th></tr></thead><tbody id="usuariosTable"></tbody></table></div></article>
            </section>

            <!-- INVENTARIO -->
            <section class="dashboard-view" data-view-panel="inventario" hidden>
                <div class="greeting-row">
                    <div><h1 class="greeting-title">Inventario y Stock</h1><p class="greeting-sub">Consulta existencias y registra entradas/salidas de mercancía.</p></div>
                    <button class="btn btn-primary" id="btnAjustarStock">+ Ajustar Stock</button>
                </div>
                <article class="panel">
                    <div class="panel-head"><div class="filter-group"><input class="form-input" id="inventorySearch" type="search" placeholder="Producto o código" style="max-width:300px"></div><select class="form-select" id="inventoryBranch" style="width:auto"><option value="">Todas las sucursales</option></select></div>
                    <div class="table-wrap"><table class="data-table"><thead><tr><th>Producto</th><th>Sucursal</th><th>Existencia</th><th>Precio</th><th>Nivel</th></tr></thead><tbody id="inventarioTable"></tbody></table></div>
                </article>
            </section>

            <!-- PRODUCTOS -->
            <section class="dashboard-view" data-view-panel="productos" hidden>
                <div class="greeting-row"><div><h1 class="greeting-title">Catálogo Global</h1><p class="greeting-sub">Registra los productos base.</p></div><button class="btn btn-primary" id="addProducto">+ Agregar producto</button></div>
                <article class="panel"><div class="panel-head"><div class="filter-group"><input class="form-input" id="productsSearch" type="search" placeholder="Buscar..." style="max-width:350px"></div></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Estado</th><th></th></tr></thead><tbody id="productosTable"></tbody></table></div></article>
            </section>

            <!-- VENTAS -->
            <section class="dashboard-view" data-view-panel="ventas" hidden>
                <div class="greeting-row"><div><h1 class="greeting-title">Historial de Ventas</h1></div></div>
                <article class="panel"><div class="panel-head"><select class="form-select" id="salesBranch" style="width:auto"><option value="">Todas las sucursales</option></select><div style="display:flex;gap:8px"><input class="form-input" id="salesFrom" type="date"><input class="form-input" id="salesTo" type="date"></div></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Folio</th><th>Fecha</th><th>Sucursal</th><th>Personal</th><th>Pago</th><th>Total</th><th>Estado</th><th></th></tr></thead><tbody id="ventasTable"></tbody></table></div></article>
            </section>
        </section>
    </main>
</div>

<!-- Modal Principal -->
<div class="modal-overlay" id="entityModal"><div class="modal-content"><div class="modal-header"><h2 class="modal-title" id="modalTitle">Registro</h2><button class="modal-close" type="button" id="closeModal">×</button></div><form id="entityForm"></form></div></div>

<!-- Modal Detalle -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-content" style="max-width: 550px;">
        <div class="modal-header"><h2 class="modal-title" id="detailModalTitle">Detalle</h2><button class="modal-close" type="button" id="closeDetailModal">×</button></div>
        <div id="detailModalBody" style="max-height: 50vh; overflow-y: auto;">Cargando...</div>
    </div>
</div>

<div class="crud-toast-container" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script>
const endpoint = 'dashboard.php';
const initial = <?= json_encode(['labels'=>$v7['labels'] ?? [], 'ingresos'=>$v7['ingresos'] ?? [], 'payments'=>$mp], JSON_UNESCAPED_UNICODE) ?>;
const $ = s => document.querySelector(s);
const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));

// SISTEMA DE ERRORES DIRECTOS
const request = async (actionStr, options = {}) => { 
    try {
        const r = await fetch(`${endpoint}?action=${actionStr}`, options); 
        if (!r.ok) throw new Error(`[ERROR DE SISTEMA] El servidor web devolvió un error: ${r.status}`);
        const text = await r.text(); 
        try {
            const data = JSON.parse(text); 
            if (!data.ok) throw new Error(data.msg || 'No fue posible completar la operación.'); 
            return data;
        } catch (jsonErr) { 
            console.error(text);
            throw new Error('[ERROR DE SISTEMA] Falló la lectura de la Base de Datos. Revisa la consola para más detalles.'); 
        }
    } catch (e) { throw e; }
};

const statusBadge = status => `<span class="badge ${String(status).toUpperCase().includes('INACT') ? 'inactive' : 'active'}"><i class="bdot"></i>${escapeHtml(status)}</span>`;
const empty = (cols, text='No hay registros.') => `<tr><td colspan="${cols}" style="text-align:center;color:var(--ink-faint);padding:30px">${text}</td></tr>`;
function toast(message, kind='success'){ const el=document.createElement('div'); el.className=`crud-toast ${kind}`; el.textContent=message; $('#toastContainer').append(el); requestAnimationFrame(()=>el.classList.add('show')); setTimeout(()=>{el.classList.remove('show'); setTimeout(()=>el.remove(),400)},4500); }

async function updateMetricsUI(){
    try {
        const { data } = await request('get_metrics');
        $('#metricSucursales').textContent = Number(data.ts).toLocaleString();
        $('#metricUsuarios').textContent = Number(data.tu).toLocaleString();
        $('#metricProductos').textContent = Number(data.tp).toLocaleString();
        $('#metricIngresos').textContent = '$' + Number(data.ih).toFixed(2);
        $('#metricVentasCount').textContent = Number(data.vh).toLocaleString() + ' ventas';
        $('#donutVentasCount').textContent = Number(data.vh).toLocaleString();
        $('#metricInventarioTotal').textContent = Number(data.ti).toLocaleString();
        $('#metricValorInventario').textContent = '$' + Number(data.valor_inv).toFixed(2);
        $('#badgeSucursales').textContent = data.ts;
        $('#badgeProductos').textContent = data.tp;
    } catch(e) {}
}

function showView(view){ document.querySelectorAll('[data-view-panel]').forEach(p=>p.hidden=p.dataset.viewPanel!==view); document.querySelectorAll('.nav-item').forEach(li=>li.classList.toggle('active',li.querySelector(`[data-view="${view}"]`)!==null)); const titles={general:'Resumen general',sucursales:'Sucursales',usuarios:'Personal',inventario:'Inventario y Stock',productos:'Catálogo Global',ventas:'Historial de Ventas'}; $('#topbarTitle').textContent=titles[view]||'Panel'; location.hash=view; $('#appShell').classList.remove('mobile-open'); if(view==='sucursales') loadSucursales(); if(view==='usuarios') loadUsuarios(); if(view==='inventario') loadInventario(); if(view==='productos') loadProductos(); if(view==='ventas') loadVentas(); }
document.addEventListener('click', e=>{ const trigger=e.target.closest('.nav-trigger'); if(trigger){e.preventDefault();showView(trigger.dataset.view);} });

// RESTAURACIÓN BOTONES DE BORRADO
async function loadSucursales(){ try{ const {data}=await request('get_sucursales'); $('#sucursalesTable').innerHTML=data.length?data.map(x=>`<tr><td><b>${escapeHtml(x.nombre)}</b></td><td>${escapeHtml(x.direccion)}</td><td>${escapeHtml(x.telefono)}</td><td>${escapeHtml(x.contacto)}</td><td>${statusBadge(x.estado)}</td><td class="row-actions"><button class="chip-btn" data-edit-sucursal='${JSON.stringify(x).replace(/'/g,'&#39;')}'>Editar</button><button class="icon-chip" aria-label="Eliminar" data-delete-sucursal="${x.id_sucursal}">×</button></td></tr>`).join(''):empty(6); }catch(e){} }
async function loadUsuarios(){ try{ const {data}=await request('get_gerentes'); $('#usuariosTable').innerHTML=data.length?data.map(x=>`<tr><td><b>${escapeHtml(x.nombre)} ${escapeHtml(x.apellido)}</b><br><small>${escapeHtml(x.correo)}</small></td><td>${escapeHtml(x.rol)}</td><td>${escapeHtml(x.sucursal)}</td><td>${statusBadge(x.estado)}</td><td class="row-actions"><button class="chip-btn" data-edit-usuario='${JSON.stringify(x).replace(/'/g,'&#39;')}'>Editar</button><button class="icon-chip" aria-label="Eliminar" data-delete-usuario="${x.id_usuario}">×</button></td></tr>`).join(''):empty(5); }catch(e){} }
async function loadInventario(){ try{ const q=$('#inventorySearch').value, suc=$('#inventoryBranch').value; const {data}=await request(`get_inventarios&q=${encodeURIComponent(q)}&sucursal=${encodeURIComponent(suc)}`); $('#inventarioTable').innerHTML=data.length?data.map(x=>{const type=x.nivel==='AGOTADO'?'critical':x.nivel==='BAJO'?'low':'watch';return `<tr><td><b>${escapeHtml(x.producto)}</b><br><small>${escapeHtml(x.codigo)}</small></td><td>${escapeHtml(x.sucursal)}</td><td><b>${escapeHtml(x.existencias)}</b> <small>/ mín. ${escapeHtml(x.stock_minimo)}</small></td><td>$${Number(x.precio).toFixed(2)}</td><td><span class="level-tag ${type}"><i class="bdot"></i>${escapeHtml(x.nivel)}</span></td></tr>`}).join(''):empty(5); }catch(e){} }
async function loadProductos(){ try{ const q=$('#productsSearch').value; const {data}=await request(`get_productos&q=${encodeURIComponent(q)}`); $('#productosTable').innerHTML=data.length?data.map(x=>`<tr><td><b>${escapeHtml(x.codigo)}</b></td><td><b>${escapeHtml(x.nombre)}</b></td><td>${escapeHtml(x.categoria)}</td><td>$${Number(x.precio).toFixed(2)}</td><td>${statusBadge(x.estado)}</td><td class="row-actions"><button class="chip-btn" data-view-producto="${x.id_producto}">Ver stock</button><button class="chip-btn" data-edit-producto='${JSON.stringify(x).replace(/'/g,'&#39;')}'>Editar</button><button class="icon-chip" aria-label="Eliminar" data-delete-producto="${x.id_producto}">×</button></td></tr>`).join(''):empty(6); }catch(e){} }
async function loadVentas(){ try{ const p=new URLSearchParams({sucursal:$('#salesBranch').value,desde:$('#salesFrom').value,hasta:$('#salesTo').value}); const {data}=await request(`get_ventas&${p}`); $('#ventasTable').innerHTML=data.length?data.map(x=>`<tr><td>#${escapeHtml(x.id_venta)}</td><td>${new Date(x.fecha_hora).toLocaleString('es-MX')}</td><td>${escapeHtml(x.sucursal)}</td><td>${escapeHtml(x.empleado)}</td><td>${escapeHtml(x.metodo_pago)}</td><td><b>$${Number(x.total).toFixed(2)}</b></td><td>${statusBadge(x.estado)}</td><td class="row-actions"><button class="chip-btn" data-view-venta="${x.id_venta}">Ver</button></td></tr>`).join(''):empty(8); }catch(e){} }

async function loadBranches(){ try{ const {data}=await request('get_sucursales_simple'); const options=data.map(x=>`<option value="${x.id_sucursal}">${escapeHtml(x.nombre)}</option>`).join(''); ['#inventoryBranch','#salesBranch'].forEach(s=>$(s).innerHTML='<option value="">Todas las sucursales</option>'+options); }catch(e){} }
async function loadLowStock(){ try{const {data}=await request('get_inventarios'); const low=data.filter(x=>x.nivel!=='OK').slice(0,4); $('#lowStockList').innerHTML=low.length?low.map(x=>`<div class="stock-row"><span class="stock-thumb">▤</span><div><div class="stock-name">${escapeHtml(x.producto)}</div><div class="stock-meta">${escapeHtml(x.sucursal)}</div></div><div class="stock-qty">${escapeHtml(x.existencias)}<small>mín. ${escapeHtml(x.stock_minimo)}</small></div></div>`).join(''):'<p class="greeting-sub" style="font-size:13px; padding:10px 0;">Todo el inventario OK.</p>';}catch(e){} }

// FORMULARIOS CRUD (CON ESTADOS INACTIVOS RESTAURADOS)
function openModal(type, record={}){
  const editing = Object.keys(record).length > 0;
  $('#entityForm').setAttribute('data-type', type);
  $('#entityForm').setAttribute('data-editing', editing ? '1' : '0');

  let fields='';
  if(type === 'sucursal'){
    $('#modalTitle').textContent = editing ? 'Editar Sucursal' : 'Nueva Sucursal';
    fields=`<input type="hidden" name="id" value="${record.id_sucursal||''}"><div class="form-group"><label class="form-label">Nombre *</label><input class="form-input" required name="nombre" value="${escapeHtml(record.nombre||'')}"></div><div class="form-group"><label class="form-label">Dirección</label><input class="form-input" name="direccion" value="${escapeHtml(record.direccion||'')}"></div><div class="form-group"><label class="form-label">Teléfono</label><input class="form-input" type="tel" pattern="[0-9]{10}" name="telefono" value="${escapeHtml(record.telefono||'')}"></div><div class="form-group"><label class="form-label">Contacto</label><input class="form-input" name="contacto" value="${escapeHtml(record.contacto||'')}"></div>${editing?`<div class="form-group"><label class="form-label">Estado</label><select class="form-select" name="estado"><option ${record.estado==='ACTIVA'?'selected':''}>ACTIVA</option><option ${record.estado==='INACTIVA'?'selected':''}>INACTIVA</option></select></div>`:''}`;
  } else if(type === 'producto'){
    $('#modalTitle').textContent = editing ? 'Editar Producto' : 'Nuevo Producto';
    fields=`<input type="hidden" name="id" value="${record.id_producto||''}"><div class="form-group"><label class="form-label">Código *</label><input class="form-input" required name="codigo" value="${escapeHtml(record.codigo||'')}"></div><div class="form-group"><label class="form-label">Nombre *</label><input class="form-input" required name="nombre" value="${escapeHtml(record.nombre||'')}"></div><div class="form-group"><label class="form-label">Categoría</label><select class="form-select" required name="id_categoria" id="formCategory"></select></div><div class="form-group"><label class="form-label">Precio base *</label><input class="form-input" required step="any" type="number" name="precio" value="${escapeHtml(record.precio||'')}"></div><div class="form-group" style="padding:16px; background:var(--bg-surface); border:1px solid var(--border-soft); border-radius:var(--r-md);"><label class="form-label">Asignar a sucursales:</label><div id="formProductBranches" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;"></div></div>${editing?`<div class="form-group"><label class="form-label">Estado</label><select class="form-select" name="estado"><option ${record.estado!=='INACTIVO'?'selected':''}>ACTIVO</option><option ${record.estado==='INACTIVO'?'selected':''}>INACTIVO</option></select></div>`:''}`;
  } else if (type === 'ajuste') {
    $('#modalTitle').textContent = 'Registrar Movimiento de Stock';
    fields=`
        <div class="form-group"><label class="form-label">1. Selecciona el Producto *</label><select class="form-select" required name="id_producto" id="formAjusteProducto"><option value="">Cargando catálogo...</option></select></div>
        <div class="form-group"><label class="form-label">2. Sucursal de destino *</label><select class="form-select" required name="id_sucursal" id="formAjusteSucursal" disabled><option value="">Primero elige un producto</option></select></div>
        <div class="form-group"><label class="form-label">3. Tipo de Movimiento *</label>
            <select class="form-select" name="tipo" id="formAjusteTipo" disabled>
                <option value="entrada">Entrada (Sumar mercancía recibida)</option>
                <option value="salida">Salida (Restar por merma o caducidad)</option>
                <option value="reemplazo">Ajuste Fijo (Reemplazar la cantidad exacta)</option>
            </select>
        </div>
        <div class="form-group"><label class="form-label">4. Cantidad (Piezas) *</label><input class="form-input" required min="0" type="number" name="cantidad" value="1" id="formAjusteCantidad" disabled></div>
    `;
  } else {
    $('#modalTitle').textContent = editing ? 'Editar Personal' : 'Nuevo Personal';
    fields=`<input type="hidden" name="id" value="${record.id_usuario||''}"><div class="form-group"><label class="form-label">Nombre *</label><input class="form-input" required name="nombre" value="${escapeHtml(record.nombre||'')}"></div><div class="form-group"><label class="form-label">Correo *</label><input class="form-input" required type="email" name="correo" value="${escapeHtml(record.correo||'')}"></div><div class="form-group"><label class="form-label">Rol *</label><select class="form-select" required name="id_rol" id="formRole"></select></div><div class="form-group"><label class="form-label">Sucursal</label><select class="form-select" name="id_sucursal" id="formBranch"></select></div><div class="form-group"><label class="form-label">Contraseña</label><input class="form-input" ${editing?'':'required'} type="password" name="password"></div>${editing?`<div class="form-group"><label class="form-label">Estado</label><select class="form-select" name="estado"><option ${record.estado==='ACTIVO'?'selected':''}>ACTIVO</option><option ${record.estado==='INACTIVO'?'selected':''}>INACTIVO</option></select></div>`:''}`;
  }
  
  $('#entityForm').innerHTML = fields + `<div class="form-actions"><button type="button" class="btn btn-ghost" id="cancelModal">Cancelar</button><button type="submit" class="btn btn-primary" id="btnSaveSubmit">Guardar</button></div>`;
  $('#entityModal').classList.add('active');
  
  if(type === 'usuario') populateUserSelects(record); 
  if(type === 'producto') { populateProductCategories(record); populateProductBranches(record); }
  if(type === 'ajuste') populateAjusteSelects();
}

// CORRECCIÓN SUCURSAL GLOBAL ELIMINADA
async function populateUserSelects(record){try{const [roles,branches]=await Promise.all([request('get_roles'),request('get_sucursales_simple')]);$('#formRole').innerHTML=roles.data.map(x=>`<option value="${x.id_rol}" ${+record.id_rol===+x.id_rol?'selected':''}>${escapeHtml(x.nombre)}</option>`).join('');$('#formBranch').innerHTML='<option value="">-- Seleccionar --</option>'+branches.data.map(x=>`<option value="${x.id_sucursal}" ${+record.id_sucursal===+x.id_sucursal?'selected':''}>${escapeHtml(x.nombre)}</option>`).join('');}catch(e){}}
async function populateProductCategories(record){try{const {data}=await request('get_categorias');$('#formCategory').innerHTML=data.map(x=>`<option value="${x.id_categoria}" ${+record.id_categoria===+x.id_categoria?'selected':''}>${escapeHtml(x.nombre)}</option>`).join('');}catch(e){}}
async function populateProductBranches(record){
    try{
        const {data}=await request('get_sucursales_simple');
        let selected = [];
        if(record.id_producto){ const res = await request(`get_producto_detalle&id=${record.id_producto}`); selected = res.data.map(i=>i.id_sucursal); }
        $('#formProductBranches').innerHTML = data.map(x=>`<label style="font-size:13px;"><input type="checkbox" name="sucursales[]" value="${x.id_sucursal}" ${selected.includes(x.id_sucursal)?'checked':''}> ${escapeHtml(x.nombre)}</label>`).join('');
    }catch(e){}
}

// LOGICA DINÁMICA DEL FORMULARIO KARDEX
async function populateAjusteSelects() {
    try {
        $('#btnSaveSubmit').disabled = true;
        const prods = await request('get_productos_simple');
        const prodSelect = $('#formAjusteProducto');
        const sucSelect = $('#formAjusteSucursal');
        
        prodSelect.innerHTML = '<option value="">-- Selecciona el producto --</option>' + prods.data.map(x=>`<option value="${x.id_producto}">${escapeHtml(x.nombre)} (${escapeHtml(x.codigo)})</option>`).join('');

        prodSelect.addEventListener('change', async (e) => {
            const idProd = e.target.value;
            if (!idProd) {
                sucSelect.innerHTML = '<option value="">Primero elige un producto</option>';
                sucSelect.disabled = true; $('#formAjusteTipo').disabled = true; $('#formAjusteCantidad').disabled = true; $('#btnSaveSubmit').disabled = true;
                return;
            }
            
            sucSelect.innerHTML = '<option value="">Buscando sucursales asignadas...</option>';
            try {
                const {data} = await request(`get_sucursales_por_producto&id_producto=${idProd}`);
                
                if (data.length === 0) {
                    sucSelect.innerHTML = '<option value="">⚠️ No existe en ninguna sucursal</option>';
                    sucSelect.disabled = true; $('#formAjusteTipo').disabled = true; $('#formAjusteCantidad').disabled = true; $('#btnSaveSubmit').disabled = true;
                    toast('[ERROR DE USUARIO] Este producto no está asignado a ninguna tienda. ¡Ve a Productos, edítalo y asígnale sucursales primero!', 'error');
                } else {
                    sucSelect.innerHTML = data.map(x=>`<option value="${x.id_sucursal}">${escapeHtml(x.nombre)}</option>`).join('');
                    sucSelect.disabled = false; $('#formAjusteTipo').disabled = false; $('#formAjusteCantidad').disabled = false; $('#btnSaveSubmit').disabled = false;
                }
            } catch (err) { toast(err.message, 'error'); }
        });
    } catch(e) {}
}

function closeModal(){$('#entityModal').classList.remove('active');}

$('#entityForm').addEventListener('submit', async e => {
    e.preventDefault();
    const f = document.getElementById('entityForm');
    const type = f.getAttribute('data-type'), isEditing = f.getAttribute('data-editing') === '1';
    
    let actionName = '';
    if (type === 'sucursal') actionName = isEditing ? 'update_sucursal' : 'create_sucursal';
    else if (type === 'usuario') actionName = isEditing ? 'update_gerente' : 'create_gerente';
    else if (type === 'producto') actionName = isEditing ? 'update_producto' : 'create_producto';
    else if (type === 'ajuste') actionName = 'ajustar_stock';

    try {
        $('#btnSaveSubmit').disabled = true;
        const d = await request(actionName, { method: 'POST', body: new FormData(f) });
        toast(d.msg); closeModal(); updateMetricsUI(); 
        if (type === 'sucursal') loadSucursales();
        if (type === 'usuario') loadUsuarios();
        if (type === 'producto' || type === 'ajuste') { loadProductos(); loadInventario(); loadLowStock(); }
    } catch(err) { 
        toast(err.message, 'error'); 
        $('#btnSaveSubmit').disabled = false;
    }
});

// Eventos de botones
$('#addSucursal').addEventListener('click', () => openModal('sucursal'));
$('#addUsuario').addEventListener('click', () => openModal('usuario'));
$('#addProducto').addEventListener('click', () => openModal('producto'));
$('#btnAjustarStock').addEventListener('click', () => openModal('ajuste'));

// DELEGACIÓN DE CLICS PARA EDITAR Y BORRAR
document.addEventListener('click', async e=>{
    const editS=e.target.closest('[data-edit-sucursal]'), editU=e.target.closest('[data-edit-usuario]'), editP=e.target.closest('[data-edit-producto]');
    const delS=e.target.closest('[data-delete-sucursal]'), delU=e.target.closest('[data-delete-usuario]'), delP=e.target.closest('[data-delete-producto]');
    
    if(editS)openModal('sucursal',JSON.parse(editS.dataset.editSucursal)); 
    if(editU)openModal('usuario',JSON.parse(editU.dataset.editUsuario)); 
    if(editP)openModal('producto',JSON.parse(editP.dataset.editProducto)); 
    
    if(delS || delU || delP) {
        if(confirm('¿Seguro que deseas eliminar este registro permanentemente del sistema?')) {
            try {
                const fd = new FormData();
                let act = '';
                if(delS){ fd.append('id', delS.dataset.deleteSucursal); act = 'delete_sucursal'; }
                if(delU){ fd.append('id', delU.dataset.deleteUsuario); act = 'delete_gerente'; }
                if(delP){ fd.append('id', delP.dataset.deleteProducto); act = 'delete_producto'; }
                
                const d = await request(act, {method:'POST', body:fd});
                toast(d.msg); updateMetricsUI();
                if(delS) { loadSucursales(); loadBranches(); }
                if(delU) loadUsuarios();
                if(delP) { loadProductos(); loadInventario(); loadLowStock(); }
            } catch(err) { toast(err.message, 'error'); }
        }
    }
    
    const viewP = e.target.closest('[data-view-producto]');
    if(viewP) {
        $('#detailModalTitle').textContent = `Stock en sucursales`; $('#detailModalBody').innerHTML = 'Cargando...'; $('#detailModal').classList.add('active');
        try {
            const {data} = await request(`get_producto_detalle&id=${viewP.dataset.viewProducto}`);
            $('#detailModalBody').innerHTML = data.length ? `<table class="data-table"><thead><tr><th>Sucursal</th><th style="text-align:right">Existencias</th></tr></thead><tbody>${data.map(x=>`<tr><td>${escapeHtml(x.sucursal)}</td><td style="text-align:right"><b>${x.existencias}</b></td></tr>`).join('')}</tbody></table>` : '<p>No asignado.</p>';
        } catch(err) { $('#closeDetailModal').click(); }
    }
    const viewV = e.target.closest('[data-view-venta]');
    if(viewV) {
        $('#detailModalTitle').textContent = `Detalle #${viewV.dataset.viewVenta}`; $('#detailModalBody').innerHTML = 'Cargando...'; $('#detailModal').classList.add('active');
        try {
            const {data} = await request(`get_venta_detalle&id=${viewV.dataset.viewVenta}`);
            $('#detailModalBody').innerHTML = data.length ? `<table class="data-table"><thead><tr><th>Producto</th><th>Cant</th><th style="text-align:right">Subtotal</th></tr></thead><tbody>${data.map(x=>`<tr><td>${escapeHtml(x.producto)}</td><td>${x.cantidad}</td><td style="text-align:right"><b>$${Number(x.subtotal).toFixed(2)}</b></td></tr>`).join('')}</tbody></table>` : '<p>Error.</p>';
        } catch(err) { $('#closeDetailModal').click(); }
    }
});

function closeModalDet(){$('#detailModal').classList.remove('active');}
$('#closeModal').addEventListener('click',closeModal); $('#closeDetailModal').addEventListener('click',closeModalDet);
document.querySelectorAll('.modal-overlay').forEach(el=>el.addEventListener('click',e=>{if(e.target===e.currentTarget||e.target.id==='cancelModal'){closeModal(); closeModalDet();}}));

['#inventorySearch','#inventoryBranch'].forEach(s=>$(s).addEventListener('input',loadInventario)); 
$('#productsSearch').addEventListener('input',loadProductos); 
['#salesBranch','#salesFrom','#salesTo'].forEach(s=>$(s).addEventListener('change',loadVentas));

$('#btnCollapseSidebar').addEventListener('click',()=>$('#appShell').classList.toggle('is-collapsed')); 
$('#btnMobileMenu').addEventListener('click',()=>$('#appShell').classList.add('mobile-open')); 
$('#sidebarScrim').addEventListener('click',()=>$('#appShell').classList.remove('mobile-open')); 
$('#refreshDashboard').addEventListener('click',()=>{loadLowStock();updateMetricsUI();toast('Actualizado.');});

window.addEventListener('DOMContentLoaded', () => {
    const palette=['#936545','#547c5c','#648ba6','#b3832e','#a66d7e']; 
    if(window.Chart){
        new Chart($('#salesChart'),{ type:'line', data:{labels:initial.labels||[],datasets:[{label:'Ingresos',data:initial.ingresos||[],borderColor:'#6c4630',backgroundColor:'rgba(147,101,69,.14)',fill:true,tension:.38,pointRadius:3}]}, options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},scales:{y:{beginAtZero:true},x:{grid:{display:false}}}} }); 
        new Chart($('#paymentChart'),{ type:'doughnut', data:{labels:(initial.payments||[]).map(x=>x.metodo),datasets:[{data:(initial.payments||[]).map(x=>x.total_ventas),backgroundColor:palette,borderWidth:0}]}, options:{responsive:true, maintainAspectRatio:false, cutout:'74%',plugins:{legend:{position:'bottom'}}} }); 
    }
    loadLowStock(); loadBranches(); 
    const view = location.hash.replace('#','');
    if(['sucursales','usuarios','inventario','productos','ventas'].includes(view)) showView(view);
});
</script>
</body>
</html>