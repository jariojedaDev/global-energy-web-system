<?php
// ============================================
// registrar_venta.php
// Formulario para registrar nueva venta.
// Campos: nombre_cliente, telefono,
//         num_solicitud, producto.
// PDFs fijos (5 obligatorios):
//   Poliza, Factura, Contrato,
//   Presupuesto, Tramite.
// ============================================
require 'includes/conexion.php';

$paginaActiva = 'registrar';
$tituloPagina = 'Nueva venta';

// ---- Ruta fisica donde se guardan los PDFs ----
define('DIR_PDFS',     __DIR__ . '/pdfs/');
define('MAX_PDF_SIZE', 30 * 1024 * 1024);   // 30 MB por archivo

// ---- Los 5 tipos de PDF fijos del sistema ----
// Clave = valor que se guarda en la BD (ENUM)
// Valor = etiqueta visible en pantalla
define('TIPOS_PDF', [
    'Poliza'      => 'Poliza',
    'Factura'     => 'Factura',
    'Contrato'    => 'Contrato',
    'Presupuesto' => 'Presupuesto',
    'Tramite'     => 'Tramite',
]);

$mensaje = '';
$error   = '';

// ============================================
// POST: procesar formulario
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -- Recuperar y sanear campos de texto --
    $nombre       = sanitizar($_POST['nombre_cliente'] ?? '');
    $telefono     = sanitizar($_POST['telefono']        ?? '');
    $numSolicitud = sanitizar($_POST['num_solicitud']   ?? '');
    $producto     = sanitizar($_POST['producto']        ?? '');

    // -- Productos permitidos segun ENUM de la BD --
    $productosPermitidos = ['Lavadora', 'Refrigerador', 'Aire acondicionado'];

    // -- Validar campos obligatorios de texto --
    if (!$nombre || !$telefono || !$numSolicitud || !$producto) {
        $error = 'Por favor completa todos los campos obligatorios.';

    } elseif (!in_array($producto, $productosPermitidos, true)) {
        $error = 'Producto no valido.';

    } else {

        // ---- Validar los 5 PDFs, uno por uno ----
        // Cada tipo tiene su propio campo: pdf_Poliza, pdf_Factura...
        $erroresPdf    = [];
        $archivosValidos = [];  // [tipo => [tmp, nombre]]

        foreach (array_keys(TIPOS_PDF) as $tipo) {
            $campo = 'pdf_' . $tipo;

            // Verificar que el campo llegó y no está vacío
            if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
                $erroresPdf[] = 'Falta el PDF: <strong>' . TIPOS_PDF[$tipo] . '</strong>';
                continue;
            }

            // Error del servidor al subir
            if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
                $erroresPdf[] = 'Error al subir el PDF: <strong>' . TIPOS_PDF[$tipo] . '</strong>';
                continue;
            }

            $tmpPath        = $_FILES[$campo]['tmp_name'];
            $nombreOriginal = $_FILES[$campo]['name'];
            $tamano         = $_FILES[$campo]['size'];

            // Validar tamano maximo
            if ($tamano > MAX_PDF_SIZE) {
                $erroresPdf[] = TIPOS_PDF[$tipo] . ': el archivo supera 30 MB.';
                continue;
            }

            // Validar extension .pdf
            $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $erroresPdf[] = TIPOS_PDF[$tipo] . ': "' . htmlspecialchars($nombreOriginal) . '" no tiene extension .pdf.';
                continue;
            }

            // Validar MIME real con finfo (evita archivos renombrados)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            if ($mime !== 'application/pdf') {
                $erroresPdf[] = TIPOS_PDF[$tipo] . ': "' . htmlspecialchars($nombreOriginal) . '" no es un PDF valido.';
                continue;
            }

            // Archivo correcto: agregarlo al mapa de validos
            $archivosValidos[$tipo] = [
                'tmp'    => $tmpPath,
                'nombre' => $nombreOriginal,
            ];
        }

        // Si algún PDF falló, mostrar todos los errores y no guardar nada
        if (!empty($erroresPdf)) {
            $error = 'Corrige los siguientes problemas con los PDFs:<br>'
                   . implode('<br>', $erroresPdf);
        }
    }

    // ---- Guardar en BD si todo está correcto ----
    if (!$error) {
        try {
            $pdo->beginTransaction();

            // 1) Insertar la venta (prepared statement)
            $stmtVenta = $pdo->prepare("
                INSERT INTO venta (num_solicitud, nombre_cliente, telefono, producto)
                VALUES (?, ?, ?, ?)
            ");
            $stmtVenta->execute([$numSolicitud, $nombre, $telefono, $producto]);
            $idVenta = (int) $pdo->lastInsertId();

            // 2) Mover cada PDF y registrar en la BD
            $stmtPdf = $pdo->prepare("
                INSERT INTO pdf (nombre_pdf, ruta_archivo, tipo_pdf, id_venta)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($archivosValidos as $tipo => $info) {

                // Nombre único en disco para evitar colisiones:
                // Formato: Tipo_ventaID_timestamp_hash.pdf
                // Ej:      Poliza_venta12_1716240000_a3f2c1b0.pdf
                $nombreUnico = $tipo
                             . '_venta' . $idVenta
                             . '_' . time()
                             . '_' . bin2hex(random_bytes(4))
                             . '.pdf';

                $rutaFisica   = DIR_PDFS . $nombreUnico;
                $rutaRelativa = 'pdfs/' . $nombreUnico;

                // Mover el archivo temporal a la carpeta definitiva
                if (!move_uploaded_file($info['tmp'], $rutaFisica)) {
                    throw new Exception('No se pudo guardar el archivo: ' . $tipo);
                }

                // Registrar la referencia del PDF en la base de datos
                $stmtPdf->execute([
                    $info['nombre'],  // Nombre original del archivo subido
                    $rutaRelativa,    // Ruta relativa para acceso desde el navegador
                    $tipo,            // Tipo ENUM: Poliza | Factura | Contrato | Presupuesto | Tramite
                    $idVenta,
                ]);
            }

            $pdo->commit();

            // Mensaje de exito con el ID y la solicitud registrada
            $mensaje = 'Venta registrada correctamente (ID #' . $idVenta
                     . ' &mdash; Solicitud: <strong>' . htmlspecialchars($numSolicitud) . '</strong>).';

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

require 'includes/header.php';

// ---- Configuracion visual de cada slot de PDF ----
$configPdf = [
    'Poliza'      => ['icon' => 'ti-shield-check',  'color' => 'slot-green',  'desc' => 'Documento de garantia y cobertura'],
    'Factura'     => ['icon' => 'ti-receipt',        'color' => 'slot-blue',   'desc' => 'Comprobante fiscal de la venta'],
    'Contrato'    => ['icon' => 'ti-file-text',      'color' => 'slot-purple', 'desc' => 'Contrato firmado entre las partes'],
    'Presupuesto' => ['icon' => 'ti-calculator',     'color' => 'slot-orange', 'desc' => 'Cotizacion y desglose de costos'],
    'Tramite'     => ['icon' => 'ti-clipboard-list', 'color' => 'slot-teal',   'desc' => 'Documentacion del tramite realizado'],
];
?>

<!-- Topbar -->
<div class="topbar">
    <div>
        <a href="index.php" class="back-link">
            <i class="ti ti-arrow-left"></i> Volver al inicio
        </a>
        <div class="page-title">Nueva venta</div>
        <div class="page-subtitle">Completa los datos del cliente y adjunta los 5 documentos PDF</div>
    </div>
</div>

<!-- ============================================
     Mensajes de exito / error
     ============================================ -->
<?php if ($mensaje): ?>
    <div class="alert alert-success">
        <i class="ti ti-circle-check"></i>
        <span><?= $mensaje ?></span>
        <a href="listar_ventas.php" class="alert-link">Ver ventas &rarr;</a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="ti ti-alert-circle"></i>
        <span><?= $error ?></span>
    </div>
<?php endif; ?>

<!-- ============================================
     Formulario principal
     enctype="multipart/form-data" es obligatorio
     para la subida de archivos PDF
     ============================================ -->
<form method="POST" action="registrar_venta.php"
      enctype="multipart/form-data" id="formVenta" novalidate>

    <!-- ============================================
         Seccion 1: Datos del cliente
         4 campos en una sola fila (responsive)
         ============================================ -->
    <div class="card form-section mb-20">
        <div class="card-header">
            <h3><i class="ti ti-user"></i> Datos del cliente</h3>
        </div>

        <div class="form-row-4">

            <!-- Nombre completo -->
            <div class="form-group">
                <label for="nombre_cliente">
                    Nombre completo <span class="required">*</span>
                </label>
                <input type="text"
                       id="nombre_cliente"
                       name="nombre_cliente"
                       class="form-control"
                       placeholder="Ej. Maria Garcia Lopez"
                       value="<?= sanitizar($_POST['nombre_cliente'] ?? '') ?>"
                       required
                       maxlength="120">
            </div>

            <!-- Telefono -->
            <div class="form-group">
                <label for="telefono">
                    Telefono <span class="required">*</span>
                </label>
                <input type="tel"
                       id="telefono"
                       name="telefono"
                       class="form-control"
                       placeholder="Ej. 664-123-4567"
                       value="<?= sanitizar($_POST['telefono'] ?? '') ?>"
                       required
                       maxlength="20">
            </div>

            <!-- Numero de solicitud -->
            <div class="form-group">
                <label for="num_solicitud">
                    No. de solicitud <span class="required">*</span>
                </label>
                <input type="text"
                       id="num_solicitud"
                       name="num_solicitud"
                       class="form-control"
                       placeholder="Ej. GE-2024-001"
                       value="<?= sanitizar($_POST['num_solicitud'] ?? '') ?>"
                       required
                       maxlength="60">
                <span class="form-hint">Folio interno de la solicitud</span>
            </div>

            <!-- Producto adquirido -->
            <div class="form-group">
                <label for="producto">
                    Producto adquirido <span class="required">*</span>
                </label>
                <select id="producto" name="producto" class="form-control" required>
                    <option value="">-- Selecciona --</option>
                    <option value="Lavadora"
                        <?= ($_POST['producto'] ?? '') === 'Lavadora' ? 'selected' : '' ?>>
                        Lavadora
                    </option>
                    <option value="Refrigerador"
                        <?= ($_POST['producto'] ?? '') === 'Refrigerador' ? 'selected' : '' ?>>
                        Refrigerador
                    </option>
                    <option value="Aire acondicionado"
                        <?= ($_POST['producto'] ?? '') === 'Aire acondicionado' ? 'selected' : '' ?>>
                        Aire acondicionado
                    </option>
                </select>
            </div>

        </div><!-- /form-row-4 -->
    </div>


    <!-- ============================================
         Seccion 2: Los 5 PDFs obligatorios
         Cada tipo tiene su propio campo de archivo.
         El JS actualiza el estado visual al elegir.
         ============================================ -->
    <div class="card form-section mb-20">
        <div class="card-header">
            <h3><i class="ti ti-file-type-pdf"></i> Documentos PDF</h3>
            <span class="card-badge">5 documentos obligatorios</span>
        </div>

        <div class="pdf-slots">

            <?php foreach (TIPOS_PDF as $tipo => $etiqueta):
                $cfg = $configPdf[$tipo]; ?>

            <div class="pdf-slot" id="slot_<?= $tipo ?>">

                <!-- Icono + nombre + descripcion del tipo -->
                <div class="pdf-slot-header <?= $cfg['color'] ?>">
                    <div class="pdf-slot-icon">
                        <i class="ti <?= $cfg['icon'] ?>"></i>
                    </div>
                    <div class="pdf-slot-info">
                        <div class="pdf-slot-nombre"><?= $etiqueta ?></div>
                        <div class="pdf-slot-desc"><?= $cfg['desc'] ?></div>
                    </div>
                    <!-- Estado dinamico (JS lo actualiza) -->
                    <div class="pdf-slot-status" id="status_<?= $tipo ?>">
                        <span class="status-empty">
                            <i class="ti ti-upload"></i> Sin archivo
                        </span>
                    </div>
                </div>

                <!-- Boton / label de seleccion de archivo -->
                <div class="pdf-slot-body">
                    <label class="pdf-file-label" for="pdf_<?= $tipo ?>">
                        <i class="ti ti-paperclip"></i>
                        <span id="label_<?= $tipo ?>">Seleccionar archivo PDF</span>
                    </label>
                    <!-- name="pdf_Poliza", "pdf_Factura", etc. -->
                    <input type="file"
                           id="pdf_<?= $tipo ?>"
                           name="pdf_<?= $tipo ?>"
                           accept=".pdf,application/pdf"
                           class="pdf-file-input"
                           data-tipo="<?= $tipo ?>"
                           required>
                </div>

            </div><!-- /pdf-slot -->

            <?php endforeach; ?>

        </div><!-- /pdf-slots -->

        <p class="form-hint" style="padding: 0 20px 16px;">
            <i class="ti ti-info-circle"></i>
            Solo se aceptan archivos <strong>.pdf</strong> &mdash; maximo 30 MB por archivo.
        </p>
    </div>


    <!-- ---- Botones de accion ---- -->
    <div class="form-actions">
        <a href="index.php" class="btn btn-ghost">
            <i class="ti ti-x"></i> Cancelar
        </a>
        <button type="submit" class="btn btn-primary" id="btnGuardar">
            <i class="ti ti-device-floppy"></i> Guardar venta
        </button>
    </div>

</form>

<?php require 'includes/footer.php'; ?>
