# empresa_ventas – Sistema de Ventas de Electrodomésticos

Sistema web desarrollado en **PHP + MySQL + HTML/CSS/JS**  

Diseñado para funcionar en **XAMPP** (Windows / macOS / Linux).

---

## Estructura del proyecto

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
