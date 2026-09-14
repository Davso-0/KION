<?php
declare(strict_types=1);
if (file_exists(__DIR__ . '/../../config/conexion_BD.php')) {
    require_once __DIR__ . '/../../config/conexion_BD.php';
} elseif (file_exists(__DIR__ . '/../../config/conexion.php')) {
    require_once __DIR__ . '/../../config/conexion.php';
}
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];
    $out = ['ok' => false, 'msg' => 'Accion desconocida'];
    try {
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            throw new RuntimeException('No fue posible conectar con la base de datos.');
        }
        if ($action === 'get_sucursales') {
            $stmt = $pdo->query("SELECT id_sucursal, nombre, direccion, telefono, contacto, estado FROM sucursales ORDER BY id_sucursal");
            $out = ['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } elseif ($action === 'create_sucursal') {
            $nombre=$_POST['nombre']??''; $dir=$_POST['direccion']??''; $tel=$_POST['telefono']??''; $cont=$_POST['contacto']??'';
            if (trim($nombre)==='') throw new Exception('El nombre es obligatorio.');
            $pdo->prepare("INSERT INTO sucursales (nombre,direccion,telefono,contacto,estado) VALUES(?,?,?,?,'ACTIVA')")->execute([trim($nombre),trim($dir),trim($tel),trim($cont)]);
            $out=['ok'=>true,'msg'=>'Sucursal creada.'];
        } elseif ($action === 'update_sucursal') {
            $id=(int)($_POST['id']??0); $nombre=trim($_POST['nombre']??'');
            if(!$id||!$nombre) throw new Exception('Datos incompletos.');
            $est=($_POST['estado']??'ACTIVA')==='INACTIVA'?'INACTIVA':'ACTIVA';
            $pdo->prepare("UPDATE sucursales SET nombre=?,direccion=?,telefono=?,contacto=?,estado=? WHERE id_sucursal=?")->execute([$nombre,trim($_POST['direccion']??''),trim($_POST['telefono']??''),trim($_POST['contacto']??''),$est,$id]);
            $out=['ok'=>true,'msg'=>'Sucursal actualizada.'];
        } elseif ($action === 'delete_sucursal') {
            $id=(int)($_POST['id']??0); if(!$id) throw new Exception('ID invalido.');
            $pdo->prepare("DELETE FROM sucursales WHERE id_sucursal=?")->execute([$id]);
            $out=['ok'=>true,'msg'=>'Sucursal eliminada.'];
        } elseif ($action === 'get_gerentes') {
            $st=$pdo->query("SELECT u.id_usuario,u.nombre,u.apellido,u.correo,u.estado,r.nombre AS rol,COALESCE(s.nombre,'Sin asignar') AS sucursal,u.id_sucursal,u.id_rol FROM usuarios u LEFT JOIN roles r ON r.id_rol=u.id_rol LEFT JOIN sucursales s ON s.id_sucursal=u.id_sucursal ORDER BY u.id_usuario");
            $out=['ok'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)];
        } elseif ($action === 'get_roles') {
            $out=['ok'=>true,'data'=>$pdo->query("SELECT id_rol,nombre FROM roles ORDER BY id_rol")->fetchAll(PDO::FETCH_ASSOC)];
        } elseif ($action === 'get_categorias') {
            $out=['ok'=>true,'data'=>$pdo->query("SELECT id_categoria,nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC)];
        } elseif ($action === 'get_productos') {
            $q=trim($_GET['q']??'');
            $sql="SELECT p.id_producto,p.codigo,p.nombre,p.descripcion,p.id_categoria,p.precio,p.estado,c.nombre AS categoria FROM productos p INNER JOIN categorias c ON c.id_categoria=p.id_categoria";
            $params=[];
            if($q!==''){$sql.=" WHERE p.codigo LIKE ? OR p.nombre LIKE ?";$params[]="%$q%";$params[]="%$q%";}
            $sql.=" ORDER BY p.nombre ASC LIMIT 200";
            $st=$pdo->prepare($sql);$st->execute($params);
            $out=['ok'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)];
        } elseif ($action === 'create_producto' || $action === 'update_producto') {
            $id=(int)($_POST['id']??0);$codigo=trim($_POST['codigo']??'');$nombre=trim($_POST['nombre']??'');$descripcion=trim($_POST['descripcion']??'');$categoria=(int)($_POST['id_categoria']??0);
            $precio=filter_var($_POST['precio']??null,FILTER_VALIDATE_FLOAT);$estado=($_POST['estado']??'ACTIVO')==='INACTIVO'?'INACTIVO':'ACTIVO';
            if($codigo===''||$nombre===''||!$categoria||$precio===false||$precio<0) throw new Exception('Completa código, nombre, categoría y un precio válido.');
            $valid=$pdo->prepare("SELECT COUNT(*) FROM categorias WHERE id_categoria=?");$valid->execute([$categoria]);
            if(!(int)$valid->fetchColumn()) throw new Exception('La categoría seleccionada no existe.');
            $duplicate=$pdo->prepare("SELECT COUNT(*) FROM productos WHERE codigo=? AND id_producto<>?");$duplicate->execute([$codigo,$id]);
            if((int)$duplicate->fetchColumn()) throw new Exception('El código ya está registrado en otro producto.');
            if($action==='create_producto'){
                $pdo->prepare("INSERT INTO productos(codigo,nombre,descripcion,id_categoria,precio,estado) VALUES(?,?,?,?,?,?)")->execute([$codigo,$nombre,$descripcion?:null,$categoria,$precio,$estado]);
                $out=['ok'=>true,'msg'=>'Producto registrado.'];
            }else{
                if(!$id) throw new Exception('ID de producto inválido.');
                $pdo->prepare("UPDATE productos SET codigo=?,nombre=?,descripcion=?,id_categoria=?,precio=?,estado=? WHERE id_producto=?")->execute([$codigo,$nombre,$descripcion?:null,$categoria,$precio,$estado,$id]);
                $out=['ok'=>true,'msg'=>'Producto actualizado.'];
            }
        } elseif ($action === 'get_sucursales_simple') {
            $out=['ok'=>true,'data'=>$pdo->query("SELECT id_sucursal,nombre FROM sucursales WHERE estado='ACTIVA' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC)];
        } elseif ($action === 'create_gerente') {
            $nom=trim($_POST['nombre']??''); $cor=trim($_POST['correo']??''); $pas=trim($_POST['password']??''); $rol=(int)($_POST['id_rol']??0);
            if(!$nom||!$cor||!$pas||!$rol) throw new Exception('Campos obligatorios incompletos.');
            $suc=($_POST['id_sucursal']??'')!==''?(int)$_POST['id_sucursal']:null;
            $pdo->prepare("INSERT INTO usuarios(nombre,apellido,correo,password_hash,id_rol,id_sucursal,estado)VALUES(?,?,?,?,?,?,'ACTIVO')")->execute([$nom,trim($_POST['apellido']??''),$cor,password_hash($pas,PASSWORD_BCRYPT),$rol,$suc]);
            $out=['ok'=>true,'msg'=>'Usuario registrado.'];
        } elseif ($action === 'update_gerente') {
            $id=(int)($_POST['id']??0); $nom=trim($_POST['nombre']??''); $cor=trim($_POST['correo']??''); $rol=(int)($_POST['id_rol']??0);
            if(!$id||!$nom||!$cor) throw new Exception('Datos incompletos.');
            $suc=($_POST['id_sucursal']??'')!==''?(int)$_POST['id_sucursal']:null;
            $est=($_POST['estado']??'ACTIVO')==='INACTIVO'?'INACTIVO':'ACTIVO';
            $pdo->prepare("UPDATE usuarios SET nombre=?,apellido=?,correo=?,id_rol=?,id_sucursal=?,estado=? WHERE id_usuario=?")->execute([$nom,trim($_POST['apellido']??''),$cor,$rol,$suc,$est,$id]);
            $np=trim($_POST['password']??'');
            if($np!=='') $pdo->prepare("UPDATE usuarios SET password_hash=? WHERE id_usuario=?")->execute([password_hash($np,PASSWORD_BCRYPT),$id]);
            $out=['ok'=>true,'msg'=>'Usuario actualizado.'];
        } elseif ($action === 'delete_gerente') {
            $id=(int)($_POST['id']??0); if(!$id) throw new Exception('ID invalido.');
            $pdo->prepare("DELETE FROM usuarios WHERE id_usuario=?")->execute([$id]);
            $out=['ok'=>true,'msg'=>'Usuario eliminado.'];
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
    } catch(Throwable $e){$out=['ok'=>false,'msg'=>$e->getMessage()];}
    echo json_encode($out,JSON_UNESCAPED_UNICODE);exit;
}
$ts=0;$tu=0;$tp=0;$vh=0;$ih=0.0;$ti=0;$v7=[];$mp=[];
if(isset($pdo)){
    try{
        $ts=(int)$pdo->query("SELECT COUNT(*) FROM sucursales")->fetchColumn();
        $tu=(int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        $tp=(int)$pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
        $ti=(int)$pdo->query("SELECT COALESCE(SUM(existencias),0) FROM inventarios")->fetchColumn();
        $rv=$pdo->query("SELECT COUNT(*) AS n,COALESCE(SUM(total),0) AS t FROM ventas WHERE DATE(fecha_hora)=CURDATE()")->fetch();
        $vh=(int)($rv['n']??0);$ih=(float)($rv['t']??0);
        for($i=6;$i>=0;$i--){
            $fd=date('Y-m-d',strtotime("-{$i} days"));$fl=date('d/m',strtotime("-{$i} days"));
            $r=$pdo->prepare("SELECT COUNT(*) AS n,COALESCE(SUM(total),0) AS t FROM ventas WHERE DATE(fecha_hora)=?");$r->execute([$fd]);$rr=$r->fetch();
            $v7['labels'][]=$fl;$v7['ventas'][]=(int)($rr['n']??0);$v7['ingresos'][]=(float)($rr['t']??0);
        }
        $mp=$pdo->query("SELECT mp.nombre AS metodo,COUNT(v.id_venta) AS total_ventas FROM metodos_pago mp LEFT JOIN ventas v ON v.id_metodo_pago=mp.id_metodo_pago GROUP BY mp.id_metodo_pago,mp.nombre")->fetchAll();
    }catch(PDOException $e){error_log($e->getMessage());}
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KION · Panel de administración</title>
    <link rel="stylesheet" href="../../../css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
</head>
<body>
<div class="app-shell" id="appShell">
    <aside class="sidebar" aria-label="Navegación principal">
        <button class="sidebar-collapse-btn" id="btnCollapseSidebar" type="button" aria-label="Contraer menú">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <a class="sidebar-brand" href="home.php" aria-label="Ir al inicio de KION">
            <div class="brand-mark">🐾</div><div class="brand-text"><div class="brand-title">KION</div><div class="brand-sub">ADMINISTRADOR GENERAL</div></div>
        </a>
        <div class="sidebar-scroll">
            <div class="nav-section-title">Panel principal</div>
            <ul class="nav-list">
                <li class="nav-item active"><a class="nav-link nav-trigger" href="#general" data-view="general"><span class="nav-icon">▦</span><span class="nav-label">Resumen general</span></a></li>
            </ul>
            <div class="nav-section-title" style="margin-top:20px">Gestión</div>
            <ul class="nav-list">
                <li class="nav-item"><a class="nav-link nav-trigger" href="#sucursales" data-view="sucursales"><span class="nav-icon">⌂</span><span class="nav-label">Sucursales</span><span class="nav-badge"><?= $ts ?></span></a></li>
                <li class="nav-item"><a class="nav-link nav-trigger" href="#usuarios" data-view="usuarios"><span class="nav-icon">♙</span><span class="nav-label">Usuarios</span></a></li>
                <li class="nav-item"><a class="nav-link nav-trigger" href="#inventario" data-view="inventario"><span class="nav-icon">▤</span><span class="nav-label">Inventario</span></a></li>
                <li class="nav-item"><a class="nav-link nav-trigger" href="#productos" data-view="productos"><span class="nav-icon">□</span><span class="nav-label">Productos</span><span class="nav-badge"><?= $tp ?></span></a></li>
                <li class="nav-item"><a class="nav-link nav-trigger" href="#ventas" data-view="ventas"><span class="nav-icon">↗</span><span class="nav-label">Ventas</span></a></li>
</a>
            </ul>
        </div>
        <div class="sidebar-foot"><div class="mini-avatar">AG</div><div class="sidebar-foot-text"><b>Administración</b><span>Panel general</span></div></div>
    </aside>
    <div class="sidebar-scrim" id="sidebarScrim"></div>
    <main class="main">
        <header class="topbar">
            <button class="hamburger-btn" id="btnMobileMenu" type="button" aria-label="Abrir menú">☰</button>
            <div class="topbar-title-block"><div class="topbar-eyebrow" id="topbarEyebrow">Panel principal</div><div class="topbar-title" id="topbarTitle">Resumen general</div></div>
            <div class="topbar-search"><span>⌕</span><input id="globalSearch" type="search" placeholder="Buscar en el panel…"></div>
            <div class="topbar-actions"><a class="btn btn-ghost btn-sm" href="home.php">Ver tienda</a></div>
        </header>

        <section class="content" id="dashboardContent">
            <section class="dashboard-view" data-view-panel="general">
                <div class="greeting-row"><div><h1 class="greeting-title">Hola, administración <span class="wave">👋</span></h1><p class="greeting-sub">Este es el pulso operativo de KION al día de hoy.</p></div><div class="greeting-actions"><button class="btn btn-ghost" type="button" id="refreshDashboard">↻ Actualizar</button><button class="btn btn-primary nav-trigger" type="button" data-view="sucursales">+ Nueva sucursal</button></div></div>
                <div class="stat-grid">
                    <article class="stat-card"><div class="stat-top"><span class="stat-icon tone-coffee">⌂</span><span class="stat-trend up">Red activa</span></div><div class="stat-value"><?= number_format($ts) ?></div><div class="stat-label">Sucursales registradas</div></article>
                    <article class="stat-card" style="animation-delay:.06s"><div class="stat-top"><span class="stat-icon tone-sage">♙</span><span class="stat-trend up">Equipo</span></div><div class="stat-value"><?= number_format($tu) ?></div><div class="stat-label">Usuarios registrados</div></article>
                    <article class="stat-card" style="animation-delay:.12s"><div class="stat-top"><span class="stat-icon tone-sky">▤</span><span class="stat-trend up">Catálogo</span></div><div class="stat-value"><?= number_format($tp) ?></div><div class="stat-label">Productos disponibles</div></article>
                    <article class="stat-card" style="animation-delay:.18s"><div class="stat-top"><span class="stat-icon tone-honey">↗</span><span class="stat-trend up">Hoy</span></div><div class="stat-value">$<?= number_format($ih, 2) ?></div><div class="stat-label"><?= number_format($vh) ?> ventas realizadas</div></article>
                </div>
                <div class="grid-2col">
                    <article class="panel"><div class="panel-head"><div><h2 class="panel-title">Ventas de los últimos 7 días</h2><p class="panel-sub">Ingresos registrados por jornada</p></div></div><canvas id="salesChart" height="150" aria-label="Gráfica de ventas de los últimos siete días"></canvas></article>
                    <article class="panel"><div class="panel-head"><div><h2 class="panel-title">Métodos de pago</h2><p class="panel-sub">Distribución histórica de ventas</p></div></div><div class="donut-wrap"><canvas id="paymentChart" height="190"></canvas><div class="donut-center"><div class="num"><?= number_format($vh) ?></div><div class="lbl">ventas hoy</div></div></div></article>
                </div>
                <div class="grid-branch-table">
                    <article class="panel"><div class="panel-head"><div><h2 class="panel-title">Atención pendiente</h2><p class="panel-sub">Productos con existencias bajas o agotadas</p></div><button class="btn btn-soft btn-sm nav-trigger" type="button" data-view="inventario">Ver inventario</button></div><div id="lowStockList" class="stock-list"><div class="skeleton" style="height:60px"></div><div class="skeleton" style="height:60px"></div></div></article>
                    <article class="panel"><div class="panel-head"><div><h2 class="panel-title">Inventario total</h2><p class="panel-sub">Unidades entre todas las sucursales</p></div></div><div class="stat-value" style="margin-top:0"><?= number_format($ti) ?></div><p class="greeting-sub">Consulta el detalle por producto desde el módulo de inventario.</p></article>
                </div>
            </section>

            <section class="dashboard-view" data-view-panel="sucursales" hidden>
                <div class="greeting-row"><div><h1 class="greeting-title">Sucursales</h1><p class="greeting-sub">Administra los puntos de venta y su información de contacto.</p></div><button class="btn btn-primary" type="button" id="addSucursal">+ Agregar sucursal</button></div>
                <article class="panel"><div class="table-wrap"><table class="data-table"><thead><tr><th>Sucursal</th><th>Dirección</th><th>Contacto</th><th>Estado</th><th aria-label="Acciones"></th></tr></thead><tbody id="sucursalesTable"></tbody></table></div></article>
            </section>

            <section class="dashboard-view" data-view-panel="usuarios" hidden>
                <div class="greeting-row"><div><h1 class="greeting-title">Usuarios</h1><p class="greeting-sub">Gestiona accesos, roles y asignaciones de sucursal.</p></div><button class="btn btn-primary" type="button" id="addUsuario">+ Agregar usuario</button></div>
                <article class="panel"><div class="table-wrap"><table class="data-table"><thead><tr><th>Usuario</th><th>Rol</th><th>Sucursal</th><th>Estado</th><th aria-label="Acciones"></th></tr></thead><tbody id="usuariosTable"></tbody></table></div></article>
            </section>

            <section class="dashboard-view" data-view-panel="inventario" hidden>
                <div class="greeting-row"><div><h1 class="greeting-title">Inventario</h1><p class="greeting-sub">Consulta existencias y prioriza reposiciones.</p></div></div>
                <article class="panel"><div class="panel-head"><div class="topbar-search" style="margin:0;max-width:370px"><span>⌕</span><input id="inventorySearch" type="search" placeholder="Producto o código"></div><select class="form-select" id="inventoryBranch" style="width:auto"><option value="">Todas las sucursales</option></select></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Producto</th><th>Sucursal</th><th>Existencia</th><th>Precio</th><th>Nivel</th></tr></thead><tbody id="inventarioTable"></tbody></table></div></article>
            </section>

            <section class="dashboard-view" data-view-panel="productos" hidden>
                <div class="greeting-row"><div><h1 class="greeting-title">Productos</h1><p class="greeting-sub">Registra y administra el catálogo base de productos.</p></div><button class="btn btn-primary" type="button" id="addProducto">+ Agregar producto</button></div>
                <article class="panel"><div class="panel-head"><div class="topbar-search" style="margin:0;max-width:370px"><span>⌕</span><input id="productsSearch" type="search" placeholder="Buscar por código o nombre"></div></div><div class="table-wrap"><table class="data-table"><thead><tr><th>ID</th><th>Código</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Estado</th><th aria-label="Acciones"></th></tr></thead><tbody id="productosTable"></tbody></table></div></article>
            </section>

            <section class="dashboard-view" data-view-panel="ventas" hidden>
                <div class="greeting-row"><div><h1 class="greeting-title">Ventas</h1><p class="greeting-sub">Revisa las transacciones registradas en cada sucursal.</p></div></div>
                <article class="panel"><div class="panel-head"><select class="form-select" id="salesBranch" style="width:auto"><option value="">Todas las sucursales</option></select><div style="display:flex;gap:8px"><input class="form-input" id="salesFrom" type="date"><input class="form-input" id="salesTo" type="date"></div></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Folio</th><th>Fecha</th><th>Sucursal</th><th>Empleado</th><th>Pago</th><th>Total</th><th>Estado</th></tr></thead><tbody id="ventasTable"></tbody></table></div></article>
            </section>
        </section>
    </main>
</div>

<div class="modal-overlay" id="entityModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle"><div class="modal-content"><div class="modal-header"><h2 class="modal-title" id="modalTitle">Registro</h2><button class="modal-close" type="button" aria-label="Cerrar" id="closeModal">×</button></div><form id="entityForm"></form></div></div>
<div class="crud-toast-container" id="toastContainer" aria-live="polite"></div>

<script>
const endpoint = 'dashboard.php';
const initial = <?= json_encode(['labels'=>$v7['labels'] ?? [], 'ingresos'=>$v7['ingresos'] ?? [], 'payments'=>$mp], JSON_UNESCAPED_UNICODE) ?>;
const $ = s => document.querySelector(s);
const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));
const request = async (action, options = {}) => { const r = await fetch(`${endpoint}?action=${encodeURIComponent(action)}`, options); const data = await r.json(); if (!data.ok) throw new Error(data.msg || 'No fue posible completar la operación.'); return data; };
const statusBadge = status => `<span class="badge ${String(status).toUpperCase().includes('INACT') ? 'inactive' : 'active'}"><i class="bdot"></i>${escapeHtml(status)}</span>`;
const empty = (cols, text='No hay registros para mostrar.') => `<tr><td colspan="${cols}" style="text-align:center;color:var(--ink-faint);padding:30px">${text}</td></tr>`;

function toast(message, kind='success'){ const el=document.createElement('div'); el.className=`crud-toast ${kind}`; el.textContent=message; $('#toastContainer').append(el); requestAnimationFrame(()=>el.classList.add('show')); setTimeout(()=>{el.classList.remove('show'); setTimeout(()=>el.remove(),400)},3600); }
function showView(view){ document.querySelectorAll('[data-view-panel]').forEach(p=>p.hidden=p.dataset.viewPanel!==view); document.querySelectorAll('.nav-item').forEach(li=>li.classList.toggle('active',li.querySelector(`[data-view="${view}"]`)!==null)); const titles={general:'Resumen general',sucursales:'Sucursales',usuarios:'Usuarios',inventario:'Inventario',productos:'Productos',ventas:'Ventas'}; $('#topbarTitle').textContent=titles[view]||'Panel'; location.hash=view; $('#appShell').classList.remove('mobile-open'); if(view==='sucursales') loadSucursales(); if(view==='usuarios') loadUsuarios(); if(view==='inventario') loadInventario(); if(view==='productos') loadProductos(); if(view==='ventas') loadVentas(); }
document.addEventListener('click', e=>{ const trigger=e.target.closest('.nav-trigger'); if(trigger){e.preventDefault();showView(trigger.dataset.view);} });

async function loadSucursales(){ try{ const {data}=await request('get_sucursales'); $('#sucursalesTable').innerHTML=data.length?data.map(x=>`<tr><td><b>${escapeHtml(x.nombre)}</b></td><td>${escapeHtml(x.direccion || '—')}</td><td>${escapeHtml(x.contacto || x.telefono || '—')}</td><td>${statusBadge(x.estado)}</td><td class="row-actions"><button class="chip-btn" data-edit-sucursal='${JSON.stringify(x).replace(/'/g,'&#39;')}'>Editar</button><button class="icon-chip" aria-label="Eliminar ${escapeHtml(x.nombre)}" data-delete-sucursal="${x.id_sucursal}">×</button></td></tr>`).join(''):empty(5); }catch(e){toast(e.message,'error');} }
async function loadUsuarios(){ try{ const {data}=await request('get_gerentes'); $('#usuariosTable').innerHTML=data.length?data.map(x=>`<tr><td><b>${escapeHtml(x.nombre)} ${escapeHtml(x.apellido)}</b><br><small>${escapeHtml(x.correo)}</small></td><td>${escapeHtml(x.rol || '—')}</td><td>${escapeHtml(x.sucursal)}</td><td>${statusBadge(x.estado)}</td><td class="row-actions"><button class="chip-btn" data-edit-usuario='${JSON.stringify(x).replace(/'/g,'&#39;')}'>Editar</button><button class="icon-chip" aria-label="Eliminar usuario" data-delete-usuario="${x.id_usuario}">×</button></td></tr>`).join(''):empty(5); }catch(e){toast(e.message,'error');} }
async function loadInventario(){ try{ const q=$('#inventorySearch').value, suc=$('#inventoryBranch').value; const {data}=await request(`get_inventarios&q=${encodeURIComponent(q)}&sucursal=${encodeURIComponent(suc)}`); $('#inventarioTable').innerHTML=data.length?data.map(x=>{const type=x.nivel==='AGOTADO'?'critical':x.nivel==='BAJO'?'low':'watch';return `<tr><td><b>${escapeHtml(x.producto)}</b><br><small>${escapeHtml(x.codigo)}</small></td><td>${escapeHtml(x.sucursal)}</td><td>${escapeHtml(x.existencias)} <small>/ mín. ${escapeHtml(x.stock_minimo)}</small></td><td>$${Number(x.precio).toFixed(2)}</td><td><span class="level-tag ${type}"><i class="bdot"></i>${escapeHtml(x.nivel)}</span></td></tr>`}).join(''):empty(5); }catch(e){toast(e.message,'error');} }
async function loadProductos(){ try{ const q=$('#productsSearch').value; const {data}=await request(`get_productos&q=${encodeURIComponent(q)}`); $('#productosTable').innerHTML=data.length?data.map(x=>`<tr><td>#${escapeHtml(x.id_producto)}</td><td><b>${escapeHtml(x.codigo)}</b></td><td><b>${escapeHtml(x.nombre)}</b><br><small>${escapeHtml(x.descripcion||'Sin descripción')}</small></td><td>${escapeHtml(x.categoria)}</td><td>$${Number(x.precio).toFixed(2)}</td><td>${statusBadge(x.estado)}</td><td class="row-actions"><button class="chip-btn" data-edit-producto='${JSON.stringify(x).replace(/'/g,'&#39;')}'>Editar</button></td></tr>`).join(''):empty(7); }catch(e){toast(e.message,'error');} }
async function loadVentas(){ try{ const p=new URLSearchParams({sucursal:$('#salesBranch').value,desde:$('#salesFrom').value,hasta:$('#salesTo').value}); const {data}=await request(`get_ventas&${p}`); $('#ventasTable').innerHTML=data.length?data.map(x=>`<tr><td>#${escapeHtml(x.id_venta)}</td><td>${new Date(x.fecha_hora).toLocaleString('es-MX')}</td><td>${escapeHtml(x.sucursal)}</td><td>${escapeHtml(x.empleado)}</td><td>${escapeHtml(x.metodo_pago)}</td><td><b>$${Number(x.total).toFixed(2)}</b></td><td>${statusBadge(x.estado)}</td></tr>`).join(''):empty(7); }catch(e){toast(e.message,'error');} }
async function loadBranches(){ try{ const {data}=await request('get_sucursales_simple'); const options=data.map(x=>`<option value="${x.id_sucursal}">${escapeHtml(x.nombre)}</option>`).join(''); ['#inventoryBranch','#salesBranch'].forEach(s=>$(s).innerHTML='<option value="">Todas las sucursales</option>'+options); }catch(e){ console.warn(e); } }
async function loadLowStock(){ try{const {data}=await request('get_inventarios'); const low=data.filter(x=>x.nivel!=='OK').slice(0,4); $('#lowStockList').innerHTML=low.length?low.map(x=>`<div class="stock-row"><span class="stock-thumb">▤</span><div><div class="stock-name">${escapeHtml(x.producto)}</div><div class="stock-meta">${escapeHtml(x.sucursal)} · ${escapeHtml(x.codigo)}</div></div><div class="stock-qty">${escapeHtml(x.existencias)}<small>mín. ${escapeHtml(x.stock_minimo)}</small></div><span class="level-tag ${x.nivel==='AGOTADO'?'critical':'low'}"><i class="bdot"></i>${escapeHtml(x.nivel)}</span></div>`).join(''):'<p class="greeting-sub">Todo el inventario se encuentra en niveles adecuados.</p>';}catch(e){$('#lowStockList').innerHTML='<p class="greeting-sub">No fue posible cargar el inventario.</p>';} }

function openModal(type, record={}){
  const editing=Object.keys(record).length>0;
  const labels={sucursal:'sucursal',usuario:'usuario',producto:'producto'};
  $('#modalTitle').textContent=`${editing?'Editar':'Agregar'} ${labels[type]}`;
  let fields='';
  if(type==='sucursal'){
    fields=`<input type="hidden" name="id" value="${record.id_sucursal||''}"><div class="form-group"><label class="form-label">Nombre *</label><input class="form-input" required name="nombre" value="${escapeHtml(record.nombre||'')}"></div><div class="form-group"><label class="form-label">Dirección</label><input class="form-input" name="direccion" value="${escapeHtml(record.direccion||'')}"></div><div class="form-group"><label class="form-label">Teléfono</label><input class="form-input" name="telefono" value="${escapeHtml(record.telefono||'')}"></div><div class="form-group"><label class="form-label">Contacto</label><input class="form-input" name="contacto" value="${escapeHtml(record.contacto||'')}"></div>${editing?`<div class="form-group"><label class="form-label">Estado</label><select class="form-select" name="estado"><option ${record.estado==='ACTIVA'?'selected':''}>ACTIVA</option><option ${record.estado==='INACTIVA'?'selected':''}>INACTIVA</option></select></div>`:''}`;
  }else if(type==='producto'){
    fields=`<input type="hidden" name="id" value="${record.id_producto||''}"><div class="form-group"><label class="form-label">Código *</label><input class="form-input" required maxlength="50" name="codigo" value="${escapeHtml(record.codigo||'')}"></div><div class="form-group"><label class="form-label">Nombre *</label><input class="form-input" required maxlength="150" name="nombre" value="${escapeHtml(record.nombre||'')}"></div><div class="form-group"><label class="form-label">Descripción</label><textarea class="form-input" name="descripcion" rows="3">${escapeHtml(record.descripcion||'')}</textarea></div><div class="form-group"><label class="form-label">Categoría *</label><select class="form-select" required name="id_categoria" id="formCategory"></select></div><div class="form-group"><label class="form-label">Precio *</label><input class="form-input" required min="0" step="0.01" type="number" name="precio" value="${escapeHtml(record.precio||'')}"></div><div class="form-group"><label class="form-label">Estado</label><select class="form-select" name="estado"><option ${record.estado!=='INACTIVO'?'selected':''}>ACTIVO</option><option ${record.estado==='INACTIVO'?'selected':''}>INACTIVO</option></select></div>`;
  }else{
    fields=`<input type="hidden" name="id" value="${record.id_usuario||''}"><div class="form-group"><label class="form-label">Nombre *</label><input class="form-input" required name="nombre" value="${escapeHtml(record.nombre||'')}"></div><div class="form-group"><label class="form-label">Apellido</label><input class="form-input" name="apellido" value="${escapeHtml(record.apellido||'')}"></div><div class="form-group"><label class="form-label">Correo *</label><input class="form-input" required type="email" name="correo" value="${escapeHtml(record.correo||'')}"></div><div class="form-group"><label class="form-label">Rol *</label><select class="form-select" required name="id_rol" id="formRole"></select></div><div class="form-group"><label class="form-label">Sucursal</label><select class="form-select" name="id_sucursal" id="formBranch"><option value="">Sin asignar</option></select></div><div class="form-group"><label class="form-label">${editing?'Nueva contraseña (opcional)':'Contraseña *'}</label><input class="form-input" ${editing?'':'required'} type="password" name="password"></div>${editing?`<div class="form-group"><label class="form-label">Estado</label><select class="form-select" name="estado"><option ${record.estado==='ACTIVO'?'selected':''}>ACTIVO</option><option ${record.estado==='INACTIVO'?'selected':''}>INACTIVO</option></select></div>`:''}`;
  }
  $('#entityForm').innerHTML=fields+`<div class="form-actions"><button type="button" class="btn btn-ghost" id="cancelModal">Cancelar</button><button class="btn btn-primary">Guardar</button></div>`;
  $('#entityForm').dataset.type=type; $('#entityForm').dataset.editing=editing?'1':''; $('#entityModal').classList.add('active');
  if(type==='usuario') populateUserSelects(record); if(type==='producto') populateProductCategories(record);
}
async function populateUserSelects(record){try{const [roles,branches]=await Promise.all([request('get_roles'),request('get_sucursales_simple')]);$('#formRole').innerHTML=roles.data.map(x=>`<option value="${x.id_rol}" ${+record.id_rol===+x.id_rol?'selected':''}>${escapeHtml(x.nombre)}</option>`).join('');$('#formBranch').innerHTML='<option value="">Sin asignar</option>'+branches.data.map(x=>`<option value="${x.id_sucursal}" ${+record.id_sucursal===+x.id_sucursal?'selected':''}>${escapeHtml(x.nombre)}</option>`).join('');}catch(e){toast(e.message,'error');}}
async function populateProductCategories(record){try{const {data}=await request('get_categorias');$('#formCategory').innerHTML=data.map(x=>`<option value="${x.id_categoria}" ${+record.id_categoria===+x.id_categoria?'selected':''}>${escapeHtml(x.nombre)}</option>`).join('');}catch(e){toast(e.message,'error');}}
function closeModal(){$('#entityModal').classList.remove('active');}
$('#addSucursal').addEventListener('click',()=>openModal('sucursal')); $('#addUsuario').addEventListener('click',()=>openModal('usuario')); $('#addProducto').addEventListener('click',()=>openModal('producto')); $('#closeModal').addEventListener('click',closeModal); $('#entityModal').addEventListener('click',e=>{if(e.target===e.currentTarget||e.target.id==='cancelModal')closeModal();});
$('#entityForm').addEventListener('submit',async e=>{e.preventDefault();const f=e.currentTarget, type=f.dataset.type, names={sucursal:'sucursal',usuario:'gerente',producto:'producto'}, action=`${f.dataset.editing?'update':'create'}_${names[type]}`;try{const d=await request(action,{method:'POST',body:new FormData(f)});toast(d.msg);closeModal();if(type==='sucursal')loadSucursales();if(type==='usuario')loadUsuarios();if(type==='producto')loadProductos();loadBranches();}catch(err){toast(err.message,'error');}});
document.addEventListener('click', async e=>{const editS=e.target.closest('[data-edit-sucursal]'),editU=e.target.closest('[data-edit-usuario]'),editP=e.target.closest('[data-edit-producto]'),delS=e.target.closest('[data-delete-sucursal]'),delU=e.target.closest('[data-delete-usuario]'); if(editS)openModal('sucursal',JSON.parse(editS.dataset.editSucursal)); if(editU)openModal('usuario',JSON.parse(editU.dataset.editUsuario)); if(editP)openModal('producto',JSON.parse(editP.dataset.editProducto)); const del=delS||delU;if(del&&confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')){try{const fd=new FormData();fd.append('id',delS?delS.dataset.deleteSucursal:delU.dataset.deleteUsuario);const d=await request(delS?'delete_sucursal':'delete_gerente',{method:'POST',body:fd});toast(d.msg);delS?loadSucursales():loadUsuarios();loadBranches();}catch(err){toast(err.message,'error');}}});
['#inventorySearch','#inventoryBranch'].forEach(s=>$(s).addEventListener('input',loadInventario)); $('#productsSearch').addEventListener('input',loadProductos); ['#salesBranch','#salesFrom','#salesTo'].forEach(s=>$(s).addEventListener('change',loadVentas));
$('#btnCollapseSidebar').addEventListener('click',()=>$('#appShell').classList.toggle('is-collapsed')); $('#btnMobileMenu').addEventListener('click',()=>$('#appShell').classList.add('mobile-open')); $('#sidebarScrim').addEventListener('click',()=>$('#appShell').classList.remove('mobile-open')); $('#refreshDashboard').addEventListener('click',()=>{loadLowStock();toast('Información actualizada.');});
window.addEventListener('DOMContentLoaded',()=>{const palette=['#936545','#547c5c','#648ba6','#b3832e','#a66d7e']; const labels=initial.labels||[]; new Chart($('#salesChart'),{type:'line',data:{labels,datasets:[{label:'Ingresos',data:initial.ingresos||[],borderColor:'#6c4630',backgroundColor:'rgba(147,101,69,.14)',fill:true,tension:.38,pointRadius:3}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{callback:v=>'$'+v}},x:{grid:{display:false}}}}}); new Chart($('#paymentChart'),{type:'doughnut',data:{labels:(initial.payments||[]).map(x=>x.metodo),datasets:[{data:(initial.payments||[]).map(x=>x.total_ventas),backgroundColor:palette,borderWidth:0}]},options:{cutout:'72%',plugins:{legend:{position:'bottom',labels:{boxWidth:10,padding:14}}}}}); loadLowStock();loadBranches(); const view=location.hash.replace('#','');if(['sucursales','usuarios','inventario','productos','ventas'].includes(view))showView(view);});
</script>
</body>
</html>
