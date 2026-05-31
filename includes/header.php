<?php // includes/header.php ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'Global Energy') ?> – Global Energy B.C.</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Iconos Tabler (CDN libre) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
</head>
<body>
<div class="app-shell">

<!-- ============================================
     Barra lateral de navegacion
     ============================================ -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <h2>Global Energy</h2>
        <span>B.C. · Sistema de Ventas</span>
    </div>

    <span class="nav-section">Menu principal</span>
    <a href="index.php"
       class="nav-item <?= ($paginaActiva ?? '') === 'inicio'    ? 'active' : '' ?>">
        <i class="ti ti-dashboard nav-icon"></i> Inicio
    </a>
    <a href="registrar_venta.php"
       class="nav-item <?= ($paginaActiva ?? '') === 'registrar' ? 'active' : '' ?>">
        <i class="ti ti-plus nav-icon"></i> Nueva venta
    </a>
    <a href="listar_ventas.php"
       class="nav-item <?= ($paginaActiva ?? '') === 'listar'    ? 'active' : '' ?>">
        <i class="ti ti-list nav-icon"></i> Ver ventas
    </a>
</aside>

<!-- ============================================
     Contenido principal (se cierra en footer.php)
     ============================================ -->
<main class="main-content">
