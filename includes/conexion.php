<?php
// ============================================
// includes/conexion.php
// Conexión a MySQL mediante PDO.
// Ajusta DB_USER y DB_PASS según tu entorno.
// ============================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'empresa_ventas');
define('DB_USER',    'root');      // Cambiar en producción
define('DB_PASS',    '');          // Cambiar en producción
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST
         . ";dbname="    . DB_NAME
         . ";charset="   . DB_CHARSET;

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);

} catch (PDOException $e) {
    // En producción nunca mostrar el error real al usuario
    die('<div style="font-family:sans-serif;padding:40px;color:#c0392b">
        <h2>⚠️ Error de conexión</h2>
        <p>No se pudo conectar a la base de datos.<br>
        Verifica que XAMPP esté ejecutándose y que la BD exista.</p>
        <small>' . htmlspecialchars($e->getMessage()) . '</small>
    </div>');
}

// ============================================
// Función auxiliar: sanear texto de entrada
// ============================================
function sanitizar(string $valor): string
{
    return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
}
?>
