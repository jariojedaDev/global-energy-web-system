<?php
// ============================================
// listar_ventas.php
// Muestra todas las ventas registradas.
// Funciones: buscar, filtrar, ver PDFs,
//            editar venta, eliminar venta.
// ============================================
require 'includes/conexion.php';

$paginaActiva = 'listar';
$tituloPagina = 'Ventas registradas';

// ============================================
// ACCION: Eliminar venta (POST con token CSRF)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $idEliminar = (int) ($_POST['id_venta'] ?? 0);
    if ($idEliminar > 0) {
        // Obtener rutas de PDFs para borrarlos del disco tambien
        $stmtRutas = $pdo->prepare("SELECT ruta_archivo FROM pdf WHERE id_venta = ?");
        $stmtRutas->execute([$idEliminar]);
        $rutas = $stmtRutas->fetchAll();

        // Eliminar venta (CASCADE borra los PDFs de la BD automaticamente)
        $stmtDel = $pdo->prepare("DELETE FROM venta WHERE id_venta = ?");
        $stmtDel->execute([$idEliminar]);

        // Borrar archivos fisicos del servidor
        foreach ($rutas as $r) {
            $ruta = __DIR__ . '/' . $r['ruta_archivo'];
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }

        $mensajeGlobal = 'Venta #' . $idEliminar . ' eliminada correctamente.';
    }
}

// ============================================
// ACCION: Guardar edicion de venta (POST)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $idEditar     = (int) sanitizar($_POST['id_venta']       ?? '0');
    $nombre       = sanitizar($_POST['nombre_cliente']        ?? '');
    $telefono     = sanitizar($_POST['telefono']              ?? '');
    $numSolicitud = sanitizar($_POST['num_solicitud']         ?? '');
    $producto     = sanitizar($_POST['producto']              ?? '');

    $productosPermitidos = ['Lavadora', 'Refrigerador', 'Aire acondicionado'];

    if (!$nombre || !$telefono || !$numSolicitud || !$producto) {
        $errorGlobal = 'Todos los campos son obligatorios.';
    } elseif (!in_array($producto, $productosPermitidos, true)) {
        $errorGlobal = 'Producto no valido.';
    } elseif ($idEditar <= 0) {
        $errorGlobal = 'ID de venta no valido.';
    } else {
        $stmtEdit = $pdo->prepare("
            UPDATE venta
            SET nombre_cliente = ?, telefono = ?, num_solicitud = ?, producto = ?
            WHERE id_venta = ?
        ");
        $stmtEdit->execute([$nombre, $telefono, $numSolicitud, $producto, $idEditar]);
        $mensajeGlobal = 'Venta #' . $idEditar . ' actualizada correctamente.';
    }
}

// ---- Parametros de busqueda y filtro ----
$busqueda       = sanitizar($_GET['q']        ?? '');
$filtroProducto = sanitizar($_GET['producto'] ?? '');

// ---- Consulta dinamica con prepared statements ----
$where  = [];
$params = [];

if ($busqueda !== '') {
    $where[]  = "(v.nombre_cliente LIKE ? OR v.telefono LIKE ? OR v.num_solicitud LIKE ?)";
    $params[] = "%{$busqueda}%";
    $params[] = "%{$busqueda}%";
    $params[] = "%{$busqueda}%";
}
if ($filtroProducto !== '') {
    $where[]  = "v.producto = ?";
    $params[] = $filtroProducto;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT v.id_venta, v.num_solicitud, v.nombre_cliente,
           v.telefono, v.producto, v.fecha_registro,
           COUNT(p.id_pdf) AS total_pdfs
    FROM venta v
    LEFT JOIN pdf p ON p.id_venta = v.id_venta
    {$whereSQL}
    GROUP BY v.id_venta
    ORDER BY v.fecha_registro DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll();

// ---- Totales por producto para el filtro ----
$totalesProd = $pdo->query("
    SELECT producto, COUNT(*) AS cant FROM venta GROUP BY producto
")->fetchAll(PDO::FETCH_KEY_PAIR);

// ---- Panel de detalle de PDFs ----
$ventaDetalle = null;
$pdfsDetalle  = [];

if (isset($_GET['detalle'])) {
    $idDetalle = (int) $_GET['detalle'];
    $stmtD = $pdo->prepare("
        SELECT id_venta, num_solicitud, nombre_cliente, telefono, producto, fecha_registro
        FROM venta WHERE id_venta = ?
    ");
    $stmtD->execute([$idDetalle]);
    $ventaDetalle = $stmtD->fetch();

    if ($ventaDetalle) {
        $stmtP = $pdo->prepare("
            SELECT id_pdf, nombre_pdf, ruta_archivo, tipo_pdf
            FROM pdf WHERE id_venta = ?
            ORDER BY FIELD(tipo_pdf,'Poliza','Factura','Contrato','Presupuesto','Tramite')
        ");
        $stmtP->execute([$idDetalle]);
        foreach ($stmtP->fetchAll() as $row) {
            $pdfsDetalle[$row['tipo_pdf']] = $row;
        }
    }
}

// ---- Cargar datos de venta para el modal de edicion ----
$ventaEditar = null;
if (isset($_GET['editar'])) {
    $idEdit = (int) $_GET['editar'];
    $stmtEd = $pdo->prepare("SELECT * FROM venta WHERE id_venta = ?");
    $stmtEd->execute([$idEdit]);
    $ventaEditar = $stmtEd->fetch();
}

require 'includes/header.php';

// ---- Helpers ----
function badge_producto(string $p): string {
    $mapa = [
        'Lavadora'           => ['badge-blue',   'Lavadora'],
        'Refrigerador'       => ['badge-teal',   'Refrigerador'],
        'Aire acondicionado' => ['badge-orange', 'Aire acondicionado'],
    ];
    [$clase, $label] = $mapa[$p] ?? ['badge-gray', $p];
    return '<span class="badge ' . $clase . '">' . htmlspecialchars($label) . '</span>';
}

$configPdf = [
    'Poliza'      => ['icon' => 'ti-shield-check',  'color' => 'slot-green',  'label' => 'Poliza'],
    'Factura'     => ['icon' => 'ti-receipt',        'color' => 'slot-blue',   'label' => 'Factura'],
    'Contrato'    => ['icon' => 'ti-file-text',      'color' => 'slot-purple', 'label' => 'Contrato'],
    'Presupuesto' => ['icon' => 'ti-calculator',     'color' => 'slot-orange', 'label' => 'Presupuesto'],
    'Tramite'     => ['icon' => 'ti-clipboard-list', 'color' => 'slot-teal',   'label' => 'Tramite'],
];
?>

<!-- Topbar -->
<div class="topbar">
    <div>
        <div class="page-title">Ventas registradas</div>
        <div class="page-subtitle">
            <?= count($ventas) ?> resultado<?= count($ventas) !== 1 ? 's' : '' ?> encontrado<?= count($ventas) !== 1 ? 's' : '' ?>
        </div>
    </div>
    <a href="registrar_venta.php" class="btn btn-primary">
        <i class="ti ti-plus"></i> Nueva venta
    </a>
</div>

<!-- Alertas globales de acciones -->
<?php if (!empty($mensajeGlobal)): ?>
    <div class="alert alert-success">
        <i class="ti ti-circle-check"></i> <?= htmlspecialchars($mensajeGlobal) ?>
    </div>
<?php endif; ?>
<?php if (!empty($errorGlobal)): ?>
    <div class="alert alert-error">
        <i class="ti ti-alert-circle"></i> <?= htmlspecialchars($errorGlobal) ?>
    </div>
<?php endif; ?>

<!-- ============================================
     Barra de busqueda y filtros
     ============================================ -->
<form method="GET" action="listar_ventas.php" id="formFiltro">
    <div class="toolbar">
        <div class="search-wrap">
            <i class="ti ti-search search-icon"></i>
            <input type="text" name="q"
                   placeholder="Buscar por nombre, telefono o solicitud..."
                   value="<?= htmlspecialchars($busqueda) ?>"
                   class="search-input">
        </div>
        <select name="producto" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">Todos los productos</option>
            <option value="Lavadora"           <?= $filtroProducto === 'Lavadora'           ? 'selected' : '' ?>>Lavadora (<?= $totalesProd['Lavadora'] ?? 0 ?>)</option>
            <option value="Refrigerador"       <?= $filtroProducto === 'Refrigerador'       ? 'selected' : '' ?>>Refrigerador (<?= $totalesProd['Refrigerador'] ?? 0 ?>)</option>
            <option value="Aire acondicionado" <?= $filtroProducto === 'Aire acondicionado' ? 'selected' : '' ?>>Aire acondicionado (<?= $totalesProd['Aire acondicionado'] ?? 0 ?>)</option>
        </select>
        <button type="submit" class="btn btn-outline"><i class="ti ti-filter"></i> Filtrar</button>
        <?php if ($busqueda || $filtroProducto): ?>
            <a href="listar_ventas.php" class="btn btn-ghost"><i class="ti ti-x"></i> Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<!-- ============================================
     Panel de detalle: los 5 PDFs de la venta
     ============================================ -->
<?php if ($ventaDetalle): ?>
<div class="card detalle-panel" id="panelDetalle">
    <div class="card-header">
        <div>
            <h3 style="margin-bottom:4px">
                <i class="ti ti-file-type-pdf"></i>
                Documentos de: <strong><?= sanitizar($ventaDetalle['nombre_cliente']) ?></strong>
            </h3>
            <div style="font-size:12px;color:var(--muted)">
                Solicitud: <strong><?= sanitizar($ventaDetalle['num_solicitud']) ?></strong>
                &nbsp;&bull;&nbsp;
                <?= badge_producto($ventaDetalle['producto']) ?>
                &nbsp;&bull;&nbsp;
                <?= date('d/m/Y', strtotime($ventaDetalle['fecha_registro'])) ?>
            </div>
        </div>
        <a href="listar_ventas.php<?= $busqueda || $filtroProducto ? '?q='.urlencode($busqueda).'&producto='.urlencode($filtroProducto) : '' ?>"
           class="btn btn-ghost btn-sm btn-icon" title="Cerrar">
            <i class="ti ti-x"></i>
        </a>
    </div>

    <!-- Grid de 5 columnas con scroll horizontal en movil -->
    <div class="detalle-pdf-grid">
        <?php foreach ($configPdf as $tipo => $cfg):
            $pdf = $pdfsDetalle[$tipo] ?? null; ?>
        <div class="detalle-pdf-card <?= $pdf ? 'tiene-pdf' : 'sin-pdf' ?>">
            <div class="detalle-pdf-icon <?= $cfg['color'] ?>">
                <i class="ti <?= $cfg['icon'] ?>"></i>
            </div>
            <div class="detalle-pdf-body">
                <div class="detalle-pdf-tipo"><?= $cfg['label'] ?></div>
                <?php if ($pdf): ?>
                    <div class="detalle-pdf-nombre" title="<?= sanitizar($pdf['nombre_pdf']) ?>">
                        <?= sanitizar($pdf['nombre_pdf']) ?>
                    </div>
                    <div class="detalle-pdf-acciones">
                        <a href="<?= htmlspecialchars($pdf['ruta_archivo']) ?>" target="_blank" class="btn btn-ghost btn-sm">
                            <i class="ti ti-eye"></i> Ver
                        </a>
                        <a href="<?= htmlspecialchars($pdf['ruta_archivo']) ?>" download="<?= htmlspecialchars($pdf['nombre_pdf']) ?>" class="btn btn-outline btn-sm">
                            <i class="ti ti-download"></i> Descargar
                        </a>
                    </div>
                <?php else: ?>
                    <div class="detalle-pdf-faltante"><i class="ti ti-alert-circle"></i> No subido</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================
     Tabla principal de ventas
     ============================================ -->
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:48px">#</th>
                    <th>No. Solicitud</th>
                    <th>Cliente</th>
                    <th>Telefono</th>
                    <th>Producto</th>
                    <th>Fecha</th>
                    <th style="width:80px;text-align:center">Docs</th>
                    <th style="width:160px;text-align:center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ventas)): ?>
                <tr><td colspan="8">
                    <div class="empty-state">
                        <i class="ti ti-inbox"></i>
                        <p>No se encontraron ventas<?= $busqueda ? ' para "' . htmlspecialchars($busqueda) . '"' : '' ?>.</p>
                        <?php if (!$busqueda && !$filtroProducto): ?>
                            <a href="registrar_venta.php" class="btn btn-primary btn-sm">Registrar primera venta</a>
                        <?php endif; ?>
                    </div>
                </td></tr>
            <?php else: foreach ($ventas as $v): ?>
                <tr class="<?= isset($_GET['detalle']) && (int)$_GET['detalle'] === $v['id_venta'] ? 'fila-activa' : '' ?>">
                    <td class="td-id"><?= $v['id_venta'] ?></td>
                    <td><span class="td-solicitud"><?= sanitizar($v['num_solicitud']) ?></span></td>
                    <td>
                        <div class="td-with-avatar">
                            <div class="avatar"><?= strtoupper(mb_substr($v['nombre_cliente'], 0, 1)) ?></div>
                            <span class="td-name"><?= sanitizar($v['nombre_cliente']) ?></span>
                        </div>
                    </td>
                    <td class="td-muted"><?= sanitizar($v['telefono']) ?></td>
                    <td><?= badge_producto($v['producto']) ?></td>
                    <td class="td-muted">
                        <?= date('d/m/Y', strtotime($v['fecha_registro'])) ?>
                        <span class="td-hour"><?= date('H:i', strtotime($v['fecha_registro'])) ?></span>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $v['total_pdfs'] >= 5 ? 'badge-green' : ($v['total_pdfs'] > 0 ? 'badge-orange' : 'badge-gray') ?>">
                            <i class="ti ti-file-type-pdf"></i> <?= $v['total_pdfs'] ?>/5
                        </span>
                    </td>
                    <!-- Tres botones: ver docs, editar, eliminar -->
                    <td>
                        <div class="acciones-row">
                            <!-- Ver PDFs -->
                            <a href="listar_ventas.php?detalle=<?= $v['id_venta'] ?><?= $busqueda ? '&q='.urlencode($busqueda) : '' ?><?= $filtroProducto ? '&producto='.urlencode($filtroProducto) : '' ?>"
                               class="btn btn-ghost btn-sm btn-icon" title="Ver documentos">
                                <i class="ti ti-file-type-pdf"></i>
                            </a>
                            <!-- Editar -->
                            <a href="listar_ventas.php?editar=<?= $v['id_venta'] ?><?= $busqueda ? '&q='.urlencode($busqueda) : '' ?><?= $filtroProducto ? '&producto='.urlencode($filtroProducto) : '' ?>"
                               class="btn btn-outline btn-sm btn-icon" title="Editar venta">
                                <i class="ti ti-pencil"></i>
                            </a>
                            <!-- Eliminar -->
                            <button type="button"
                                    class="btn btn-danger btn-sm btn-icon"
                                    title="Eliminar venta"
                                    onclick="confirmarEliminar(<?= $v['id_venta'] ?>, '<?= addslashes(sanitizar($v['nombre_cliente'])) ?>')">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================
     Modal: Editar venta
     Se activa cuando ?editar=ID esta en la URL
     ============================================ -->
<?php if ($ventaEditar): ?>
<div class="modal-overlay" id="modalEditar">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="ti ti-pencil"></i> Editar venta #<?= $ventaEditar['id_venta'] ?></h3>
            <a href="listar_ventas.php<?= $busqueda || $filtroProducto ? '?q='.urlencode($busqueda).'&producto='.urlencode($filtroProducto) : '' ?>"
               class="btn btn-ghost btn-sm btn-icon" title="Cerrar">
                <i class="ti ti-x"></i>
            </a>
        </div>
        <form method="POST" action="listar_ventas.php<?= $busqueda || $filtroProducto ? '?q='.urlencode($busqueda).'&producto='.urlencode($filtroProducto) : '' ?>">
            <input type="hidden" name="accion"   value="editar">
            <input type="hidden" name="id_venta" value="<?= $ventaEditar['id_venta'] ?>">

            <div class="modal-body">
                <!-- Nombre -->
                <div class="form-group-modal">
                    <label>Nombre completo <span class="required">*</span></label>
                    <input type="text" name="nombre_cliente" class="form-control"
                           value="<?= sanitizar($ventaEditar['nombre_cliente']) ?>"
                           required maxlength="120">
                </div>
                <!-- Telefono -->
                <div class="form-group-modal">
                    <label>Telefono <span class="required">*</span></label>
                    <input type="tel" name="telefono" class="form-control"
                           value="<?= sanitizar($ventaEditar['telefono']) ?>"
                           required maxlength="20">
                </div>
                <!-- Numero de solicitud -->
                <div class="form-group-modal">
                    <label>No. de solicitud <span class="required">*</span></label>
                    <input type="text" name="num_solicitud" class="form-control"
                           value="<?= sanitizar($ventaEditar['num_solicitud']) ?>"
                           required maxlength="60">
                </div>
                <!-- Producto -->
                <div class="form-group-modal">
                    <label>Producto adquirido <span class="required">*</span></label>
                    <select name="producto" class="form-control" required>
                        <option value="Lavadora"           <?= $ventaEditar['producto'] === 'Lavadora'           ? 'selected' : '' ?>>Lavadora</option>
                        <option value="Refrigerador"       <?= $ventaEditar['producto'] === 'Refrigerador'       ? 'selected' : '' ?>>Refrigerador</option>
                        <option value="Aire acondicionado" <?= $ventaEditar['producto'] === 'Aire acondicionado' ? 'selected' : '' ?>>Aire acondicionado</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <a href="listar_ventas.php" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ============================================
     Formulario oculto para confirmar eliminacion
     Se envía desde JavaScript
     ============================================ -->
<form id="formEliminar" method="POST" action="listar_ventas.php" style="display:none">
    <input type="hidden" name="accion"   value="eliminar">
    <input type="hidden" name="id_venta" id="eliminarId" value="">
</form>

<!-- Modal de confirmacion de eliminacion -->
<div class="modal-overlay" id="modalEliminar" style="display:none">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3><i class="ti ti-alert-triangle" style="color:var(--red)"></i> Confirmar eliminacion</h3>
        </div>
        <div class="modal-body">
            <p style="font-size:14px;color:var(--text-body)">
                Estas a punto de eliminar la venta de
                <strong id="eliminarNombre"></strong>.
            </p>
            <p style="font-size:13px;color:var(--red);margin-top:8px">
                <i class="ti ti-alert-circle"></i>
                Esta accion eliminara tambien todos los PDFs asociados y no se puede deshacer.
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="cerrarModalEliminar()">Cancelar</button>
            <button type="button" class="btn btn-danger" onclick="document.getElementById('formEliminar').submit()">
                <i class="ti ti-trash"></i> Si, eliminar
            </button>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
