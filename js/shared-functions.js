/**
 * Funciones JavaScript compartidas para el sistema
 * Elimina duplicación de código entre vistas
 */

// Función para mostrar llamadas pendientes
function mostrarLlamadasPendientes() {
    const llamadasPendientes = window.llamadasPendientesData || [];
    
    if (llamadasPendientes.length === 0) {
        alert('No tienes llamadas pendientes para hoy.');
        return;
    }
    
    // Mostrar el modal
    const modal = document.getElementById('modalLlamadasPendientesAsesor') || 
                  document.getElementById('modalLlamadasPendientes');
    
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Cargar contenido del modal
        mostrarLlamadasPendientesEnModal(llamadasPendientes);
    } else {
        console.error('Modal de llamadas pendientes no encontrado');
    }
}

// Función para cerrar modal de llamadas pendientes
function cerrarModalLlamadasPendientes() {
    const modal = document.getElementById('modalLlamadasPendientesAsesor') || 
                  document.getElementById('modalLlamadasPendientes');
    
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Función para mostrar contenido del modal de llamadas pendientes
function mostrarLlamadasPendientesEnModal(llamadasPendientes) {
    const contenidoModal = document.getElementById('contenidoLlamadasPendientes');
    
    if (!contenidoModal) {
        console.error('Elemento contenidoLlamadasPendientes no encontrado');
        return;
    }
    
    let html = '<div class="llamadas-pendientes-container">';
    
    if (llamadasPendientes.length === 0) {
        html += '<p class="text-center text-muted">No hay llamadas pendientes para hoy.</p>';
    } else {
        html += '<h4>📞 Llamadas Pendientes para Hoy</h4>';
        html += '<div class="llamadas-list">';
        
        llamadasPendientes.forEach((llamada, index) => {
            html += `
                <div class="llamada-item">
                    <div class="llamada-info">
                        <strong>${llamada.nombre || 'Cliente'}</strong>
                        <span class="cedula">Cédula: ${llamada.cedula || 'N/A'}</span>
                        <span class="telefono">Tel: ${llamada.telefono || 'N/A'}</span>
                    </div>
                    <div class="llamada-acciones">
                        <a href="index.php?action=gestionar_cliente&id=${llamada.id}" 
                           class="btn btn-primary btn-sm">
                            📞 Gestionar Cliente
                        </a>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
    }
    
    html += '</div>';
    contenidoModal.innerHTML = html;
}

// Función para cargar estadísticas de productos de forma segura
function cargarEstadisticasProductos() {
    const totalProductos = document.getElementById('total-productos');
    const totalRecaudado = document.getElementById('total-recaudado-productos');
    
    if (!totalProductos || !totalRecaudado) {
        console.warn('Elementos de estadísticas de productos no encontrados');
        return;
    }
    
    fetch('index.php?action=obtener_estadisticas_productos')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                totalProductos.textContent = data.estadisticas.total_productos || 0;
                totalRecaudado.textContent = 
                    '$' + (data.estadisticas.total_recaudado || 0).toLocaleString('es-CO');
            }
        })
        .catch(error => {
            console.error('Error cargando estadísticas de productos:', error);
        });
}

// Función para alternar secciones
function toggleSection(sectionName) {
    const content = document.getElementById('content-' + sectionName);
    const toggle = document.getElementById('toggle-' + sectionName);
    
    if (!content || !toggle) {
        console.error(`Elementos para sección ${sectionName} no encontrados`);
        return;
    }
    
    if (content.classList.contains('collapsed')) {
        content.classList.remove('collapsed');
        toggle.classList.remove('collapsed');
        toggle.textContent = '▼';
    } else {
        content.classList.add('collapsed');
        toggle.classList.add('collapsed');
        toggle.textContent = '▶';
    }
}

// Función para cambiar período
function cambiarPeriodo(periodo) {
    window.location.href = 'index.php?action=dashboard&periodo=' + periodo;
}

// Inicialización común
document.addEventListener('DOMContentLoaded', function() {
    // Configurar event listeners comunes
    const modalLlamadasPendientes = document.getElementById('modalLlamadasPendientesAsesor') || 
                                   document.getElementById('modalLlamadasPendientes');
    
    if (modalLlamadasPendientes) {
        modalLlamadasPendientes.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalLlamadasPendientes();
            }
        });
    }
    
    // Cargar estadísticas de productos si los elementos existen
    cargarEstadisticasProductos();
});
