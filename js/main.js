// ============================================
// js/main.js
// Interactividad del formulario de nueva venta.
// Actualiza el estado visual de cada slot de PDF
// cuando el usuario selecciona un archivo.
// ============================================

document.addEventListener('DOMContentLoaded', function () {

    // ============================================
    // 1. Escuchar cambios en los 5 inputs de PDF
    //    Cada input tiene data-tipo con el nombre
    //    del tipo: Poliza, Factura, Contrato...
    // ============================================
    const inputsPdf = document.querySelectorAll('.pdf-file-input');

    inputsPdf.forEach(function (input) {
        input.addEventListener('change', function () {
            const tipo  = this.dataset.tipo;
            const label = document.getElementById('label_' + tipo);
            const statusEl = document.getElementById('status_' + tipo);
            const slotEl   = document.getElementById('slot_' + tipo);

            if (this.files && this.files[0]) {
                const archivo = this.files[0];

                // Validar extension en el cliente
                const ext = archivo.name.split('.').pop().toLowerCase();
                if (ext !== 'pdf') {
                    mostrarToast('El archivo "' + archivo.name + '" no es un PDF.', 'error');
                    this.value = '';
                    return;
                }

                // Validar tamano: 30 MB maximo
                if (archivo.size > 30 * 1024 * 1024) {
                    mostrarToast(tipo + ': el archivo supera 30 MB.', 'error');
                    this.value = '';
                    return;
                }

                // Actualizar etiqueta del boton con el nombre del archivo
                const tamano = archivo.size >= 1024 * 1024
                    ? (archivo.size / (1024 * 1024)).toFixed(1) + ' MB'
                    : Math.round(archivo.size / 1024) + ' KB';

                if (label) {
                    label.textContent = archivo.name + ' (' + tamano + ')';
                }

                // Cambiar indicador de estado a "Listo"
                if (statusEl) {
                    statusEl.innerHTML = '<span class="status-listo">'
                        + '<i class="ti ti-check"></i> Listo'
                        + '</span>';
                }

                // Agregar clase visual al slot completo
                if (slotEl) slotEl.classList.add('listo');

            } else {
                // Si el usuario cancela la seleccion, regresar al estado inicial
                if (label)    label.textContent = 'Seleccionar archivo PDF';
                if (statusEl) {
                    statusEl.innerHTML = '<span class="status-empty">'
                        + '<i class="ti ti-upload"></i> Sin archivo'
                        + '</span>';
                }
                if (slotEl) slotEl.classList.remove('listo');
            }
        });
    });

    // ============================================
    // 2. Validacion del formulario antes de enviar
    //    Verifica que todos los campos y los 5 PDFs
    //    esten completos antes de hacer el POST.
    // ============================================
    const formVenta = document.getElementById('formVenta');

    if (formVenta) {
        formVenta.addEventListener('submit', function (e) {

            // Validar campos de texto
            const nombre       = document.getElementById('nombre_cliente');
            const telefono     = document.getElementById('telefono');
            const numSolicitud = document.getElementById('num_solicitud');
            const producto     = document.getElementById('producto');

            if (!nombre.value.trim() || !telefono.value.trim()
                || !numSolicitud.value.trim() || !producto.value) {
                e.preventDefault();
                mostrarToast('Por favor completa todos los campos del cliente.', 'error');
                return;
            }

            // Validar que los 5 inputs de PDF tengan archivo seleccionado
            const tiposFaltantes = [];
            inputsPdf.forEach(function (input) {
                if (!input.files || input.files.length === 0) {
                    tiposFaltantes.push(input.dataset.tipo);
                }
            });

            if (tiposFaltantes.length > 0) {
                e.preventDefault();
                mostrarToast(
                    'Faltan los siguientes PDFs: ' + tiposFaltantes.join(', '),
                    'error'
                );
                return;
            }

            // Todo correcto: deshabilitar boton para evitar doble envio
            const btnGuardar = document.getElementById('btnGuardar');
            if (btnGuardar) {
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<i class="ti ti-loader-2"></i> Guardando...';
            }
        });
    }

    // ============================================
    // 3. Toast de notificacion ligero
    //    Se muestra en la esquina inferior derecha
    // ============================================
    function mostrarToast(mensaje, tipo) {
        const colores = {
            error: '#DC2626',
            info:  '#2563EB',
            ok:    '#16A34A',
        };
        const toast = document.createElement('div');
        Object.assign(toast.style, {
            position:     'fixed',
            bottom:       '24px',
            right:        '24px',
            background:   colores[tipo] || colores.info,
            color:        '#fff',
            padding:      '12px 20px',
            borderRadius: '8px',
            fontSize:     '13.5px',
            fontFamily:   'DM Sans, sans-serif',
            boxShadow:    '0 4px 20px rgba(0,0,0,.20)',
            zIndex:       '9999',
            maxWidth:     '340px',
            lineHeight:   '1.5',
        });
        toast.textContent = mensaje;
        document.body.appendChild(toast);
        setTimeout(function () { toast.remove(); }, 3800);
    }

}); // fin DOMContentLoaded

// ============================================
// Funciones para los modales de editar
// y eliminar venta (listar_ventas.php)
// ============================================

// Abrir modal de confirmacion de eliminacion
function confirmarEliminar(idVenta, nombreCliente) {
    document.getElementById('eliminarId').value    = idVenta;
    document.getElementById('eliminarNombre').textContent = nombreCliente;
    document.getElementById('modalEliminar').style.display = 'flex';
}

// Cerrar modal de eliminacion
function cerrarModalEliminar() {
    document.getElementById('modalEliminar').style.display = 'none';
}

// Cerrar modales al hacer clic en el fondo oscuro
document.addEventListener('click', function (e) {
    const modalEl = document.getElementById('modalEliminar');
    if (modalEl && e.target === modalEl) {
        cerrarModalEliminar();
    }
});

// Cerrar con tecla Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        cerrarModalEliminar();
    }
});
