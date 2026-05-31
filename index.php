<?php
// ============================================
// index.php
// Dashboard principal de Global Energy.
// Muestra resumen de ventas por producto
// y las ultimas 5 ventas registradas.
// ============================================
require 'includes/conexion.php';

$paginaActiva = 'inicio';
$tituloPagina = 'Inicio';

// ---- Estadisticas globales ----
$stats = $pdo->query("
    SELECT
        COUNT(*)                                             AS total,
        SUM(producto = 'Lavadora')                          AS lavadoras,
        SUM(producto = 'Refrigerador')                      AS refrigeradores,
        SUM(producto = 'Aire acondicionado')                AS aires,
        SUM(DATE(fecha_registro) = CURDATE())               AS hoy
    FROM venta
")->fetch();

// ---- Ultimas 5 ventas ----
$ultimas = $pdo->query("
    SELECT v.id_venta, v.num_solicitud, v.nombre_cliente,
           v.telefono, v.producto, v.fecha_registro,
           COUNT(p.id_pdf) AS total_pdfs
    FROM venta v
    LEFT JOIN pdf p ON p.id_venta = v.id_venta
    GROUP BY v.id_venta
    ORDER BY v.fecha_registro DESC
    LIMIT 5
")->fetchAll();

require 'includes/header.php';

// ---- Helper: badge de producto ----
function badge_producto(string $p): string
{
    $mapa = [
        'Lavadora'            => ['badge-blue',   'Lavadora'],
        'Refrigerador'        => ['badge-teal',   'Refrigerador'],
        'Aire acondicionado'  => ['badge-orange', 'Aire acondicionado'],
    ];
    [$clase, $label] = $mapa[$p] ?? ['badge-gray', $p];
    return '<span class="badge ' . $clase . '">' . htmlspecialchars($label) . '</span>';
}
?>

<!-- Topbar -->
<div class="topbar">
    <div>
        <div class="page-title">Dashboard</div>
        <div class="page-subtitle">Bienvenido al sistema de ventas &mdash; Global Energy</div>
    </div>
    <a href="registrar_venta.php" class="btn btn-primary">
        <i class="ti ti-plus"></i> Nueva venta
    </a>
</div>

<!-- ============================================
     Tarjetas de estadisticas
     ============================================ -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="ti ti-shopping-cart"></i></div>
        <div class="stat-label">Total ventas</div>
        <div class="stat-value purple"><?= (int) $stats['total'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="ti ti-device-speaker"></i></div>
        <div class="stat-label">Lavadoras</div>
        <div class="stat-value"><?= (int) $stats['lavadoras'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="ti ti-snowflake"></i></div>
        <div class="stat-label">Refrigeradores</div>
        <div class="stat-value"><?= (int) $stats['refrigeradores'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="ti ti-wind"></i></div>
        <div class="stat-label">Aires acondicionados</div>
        <div class="stat-value"><?= (int) $stats['aires'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="ti ti-calendar"></i></div>
        <div class="stat-label">Registradas hoy</div>
        <div class="stat-value"><?= (int) $stats['hoy'] ?></div>
    </div>
</div>

<!-- ============================================
     Ultimas ventas registradas
     ============================================ -->
<div class="card">
    <div class="card-header">
        <h3><i class="ti ti-clock-hour-4"></i> Ultimas ventas</h3>
        <a href="listar_ventas.php" class="btn btn-ghost btn-sm">Ver todas &rarr;</a>
    </div>

    <?php if (empty($ultimas)): ?>
        <div class="empty-state">
            <i class="ti ti-inbox"></i>
            <p>Aun no hay ventas registradas.</p>
            <a href="registrar_venta.php" class="btn btn-primary btn-sm">
                Registrar primera venta
            </a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>No. Solicitud</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Fecha</th>
                        <th style="text-align:center">Docs</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ultimas as $v): ?>
                    <tr>
                        <td class="td-id"><?= $v['id_venta'] ?></td>
                        <td><span class="td-solicitud"><?= sanitizar($v['num_solicitud']) ?></span></td>
                        <td>
                            <div class="td-with-avatar">
                                <div class="avatar">
                                    <?= strtoupper(mb_substr($v['nombre_cliente'], 0, 1)) ?>
                                </div>
                                <span><?= sanitizar($v['nombre_cliente']) ?></span>
                            </div>
                        </td>
                        <td><?= badge_producto($v['producto']) ?></td>
                        <td class="td-muted"><?= date('d/m/Y H:i', strtotime($v['fecha_registro'])) ?></td>
                        <td style="text-align:center">
                            <span class="badge <?= $v['total_pdfs'] >= 5 ? 'badge-green' : ($v['total_pdfs'] > 0 ? 'badge-orange' : 'badge-gray') ?>">
                                <i class="ti ti-file-type-pdf"></i>
                                <?= $v['total_pdfs'] ?>/5
                            </span>
                        </td>
                        <td>
                            <a href="listar_ventas.php?detalle=<?= $v['id_venta'] ?>"
                               class="btn btn-ghost btn-sm btn-icon" title="Ver documentos">
                                <i class="ti ti-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
