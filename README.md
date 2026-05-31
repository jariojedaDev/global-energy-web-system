# 📦 empresa_ventas – Sistema de Ventas de Electrodomésticos

Sistema web desarrollado en **PHP + MySQL + HTML/CSS/JS**  
Diseñado para funcionar en **XAMPP** (Windows / macOS / Linux).

---

## 📁 Estructura del proyecto

```
empresa_ventas/
├── index.php              ← Dashboard / pantalla de inicio
├── registrar_venta.php    ← Formulario: nueva venta + subida de PDFs
├── listar_ventas.php      ← Listado de ventas + visualización/descarga de PDFs
├── includes/
│   ├── conexion.php       ← Conexión PDO a MySQL
│   ├── header.php         ← Cabecera HTML + sidebar
│   └── footer.php         ← Cierre HTML
├── css/
│   └── style.css          ← Hoja de estilos principal
├── js/
│   └── main.js            ← JavaScript: drag & drop, validación
├── pdfs/                  ← PDFs subidos (NO deben borrarse)
├── uploads/               ← Reservada para uso futuro
└── sql/
    └── empresa_ventas.sql ← Script SQL para crear la BD
```

---

## 🚀 Cómo configurar e iniciar el proyecto en XAMPP

### 1. Instalar XAMPP

Descarga XAMPP desde: https://www.apachefriends.org  
Instala e inicia los módulos **Apache** y **MySQL** desde el panel de control.

---

### 2. Copiar el proyecto

Copia la carpeta `empresa_ventas` dentro de la carpeta raíz de XAMPP:

- **Windows:** `C:\xampp\htdocs\empresa_ventas`
- **macOS/Linux:** `/Applications/XAMPP/htdocs/empresa_ventas`  
  o `/opt/lampp/htdocs/empresa_ventas`

---

### 3. Importar la base de datos

**Opción A — phpMyAdmin (recomendado para principiantes):**

1. Abre tu navegador en `http://localhost/phpmyadmin`
2. Haz clic en **"Nueva"** (panel izquierdo) para crear una BD
3. Escribe `empresa_ventas` y haz clic en **"Crear"**
4. Selecciona la BD `empresa_ventas` en el panel izquierdo
5. Ve a la pestaña **"Importar"**
6. Haz clic en **"Elegir archivo"** y selecciona `sql/empresa_ventas.sql`
7. Haz clic en **"Continuar"**

**Opción B — Línea de comandos:**

```bash
# Windows (con XAMPP iniciado)
C:\xampp\mysql\bin\mysql -u root -p empresa_ventas < sql\empresa_ventas.sql

# macOS / Linux
/opt/lampp/bin/mysql -u root empresa_ventas < sql/empresa_ventas.sql
```

---

### 4. Permisos de la carpeta pdfs/

La carpeta `pdfs/` debe tener permisos de escritura para que PHP pueda guardar archivos:

```bash
# macOS / Linux
chmod 755 /opt/lampp/htdocs/empresa_ventas/pdfs
```

En **Windows** XAMPP esto funciona automáticamente.

---

### 5. Configurar la conexión (si es necesario)

Abre `includes/conexion.php` y verifica:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'empresa_ventas');
define('DB_USER', 'root');   // Usuario de MySQL en XAMPP
define('DB_PASS', '');       // Contraseña (vacía por defecto en XAMPP)
```

Si tu instalación de XAMPP tiene contraseña para `root`, cámbiala aquí.

---

### 6. Abrir en el navegador

Con Apache y MySQL iniciados en XAMPP, accede a:

```
http://localhost/empresa_ventas/
```

---

## 🧪 Cómo probar la subida de PDFs

1. Ve a **"Nueva venta"** (menú izquierdo o botón en el dashboard)
2. Completa: nombre del cliente, teléfono, producto
3. En la zona de PDFs:
   - Arrastra archivos PDF o haz clic en la zona para seleccionarlos
   - Opcionalmente asigna un tipo (Póliza, Contrato, Factura…) y número de páginas
4. Haz clic en **"Guardar venta"**
5. Verás el mensaje de confirmación con el ID de la venta creada
6. En **"Ver ventas"**, haz clic en el botón **PDFs** de cualquier fila
   - Aparece un panel con los documentos subidos
   - Cada PDF tiene botón **ver** (abre en el navegador) y **descargar**

---

## 🔐 Seguridad implementada

| Medida                             | Dónde                              |
|------------------------------------|------------------------------------|
| Prepared statements (PDO)          | `conexion.php`, todos los archivos |
| Validación de extensión (.pdf)     | `registrar_venta.php` (servidor)   |
| Validación de MIME real (finfo)    | `registrar_venta.php`              |
| Validación de tamaño (30 MB)       | Cliente (JS) + servidor (PHP)      |
| `htmlspecialchars` en salidas HTML | Todos los archivos                 |
| Nombres únicos con `random_bytes`  | `registrar_venta.php`              |
| `strip_tags` en función sanitizar  | `includes/conexion.php`            |
| Bloqueo de listado en /pdfs/       | `pdfs/index.php`                   |

---

## 📋 Productos disponibles

El campo `producto` en la tabla `venta` acepta únicamente:

- `Lavadora`
- `Refrigerador`
- `Aire acondicionado`

Definido como `ENUM` en MySQL para garantizar integridad referencial.

---

## 🗄️ Diagrama de la base de datos

```
venta
─────────────────────────────
id_venta        INT PK AI
nombre_cliente  VARCHAR(120)
telefono        VARCHAR(20)
producto        ENUM(...)
fecha_registro  TIMESTAMP

        1 ──────── N

pdf
─────────────────────────────
id_pdf          INT PK AI
nombre_pdf      VARCHAR(255)
ruta_archivo    VARCHAR(400)
tipo_pdf        VARCHAR(80)
numero_paginas  INT
id_venta        INT FK → venta.id_venta
```

---

*Desarrollado para XAMPP · PHP 7.4+ · MySQL 5.7+*
