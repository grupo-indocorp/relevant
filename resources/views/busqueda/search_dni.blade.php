@extends('layouts.app')

@section('title', 'Buscar DNI - Sistema de Búsqueda')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Encabezado -->
            <div class="mb-4">
                <h2 class="text-success">
                    <i class="fas fa-id-card"></i> Búsqueda Individual - DNI
                </h2>
                <p class="text-muted">
                    Ingresa un número de DNI (8 dígitos) para obtener información detallada de la persona o contribuyente
                </p>
                <a href="{{ route('busqueda.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al inicio
                </a>
            </div>

            <!-- Formulario de Búsqueda -->
            <div class="card shadow-lg mb-4">
                <div class="card-body">
                    <form id="searchDniForm" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="dniInput" class="form-label">Número de DNI</label>
                            <div class="input-group input-group-lg">
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="dniInput" 
                                    name="dni" 
                                    placeholder="Ej: 12345678"
                                    maxlength="8"
                                    pattern="\d{8}"
                                    required
                                >
                                <button class="btn btn-success" type="submit">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                El DNI debe contener exactamente 8 dígitos
                            </small>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Loader -->
            <div id="loader" class="text-center my-4" style="display: none;">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Realizando búsqueda...</p>
            </div>

            <!-- Área de Resultados -->
            <div id="resultsContainer" class="mt-4">
                <!-- Alertas -->
                <div id="errorAlert" class="alert alert-danger alert-dismissible fade show" role="alert" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Error:</strong>
                    <span id="errorMessage"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <div id="notFoundAlert" class="alert alert-warning alert-dismissible fade show" role="alert" style="display: none;">
                    <i class="fas fa-search"></i>
                    <strong>No encontrado:</strong>
                    <span id="notFoundMessage"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <!-- Card de resultado -->
                <div id="resultCard" class="card shadow-lg" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0" id="resultTitle">Resultado de Búsqueda</h5>
                    </div>
                    <div class="card-body" id="resultContent">
                        <!-- El contenido se llenará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('searchDniForm');
    const loader = document.getElementById('loader');
    const resultsContainer = document.getElementById('resultsContainer');
    const errorAlert = document.getElementById('errorAlert');
    const notFoundAlert = document.getElementById('notFoundAlert');
    const resultCard = document.getElementById('resultCard');
    
    // Ocultar todas las alertas al inicio
    function hideAllAlerts() {
        errorAlert.style.display = 'none';
        notFoundAlert.style.display = 'none';
        resultCard.style.display = 'none';
    }

    // Formatear DNI mientras se escribe
    document.getElementById('dniInput').addEventListener('input', function(e) {
        // Solo permitir números
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
        
        // Limitar a 8 dígitos
        if (e.target.value.length > 8) {
            e.target.value = e.target.value.slice(0, 8);
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const dni = document.getElementById('dniInput').value.trim();
        
        if (!dni) {
            showError('Por favor ingrese un número de DNI');
            return;
        }

        if (dni.length !== 8) {
            showError('El DNI debe tener exactamente 8 dígitos');
            return;
        }

        // Mostrar loader
        loader.style.display = 'block';
        hideAllAlerts();

        // Realizar búsqueda
        fetch('/busqueda/dni', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ dni: dni })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            loader.style.display = 'none';

            if (data.success) {
                showResult(data.data);
            } else {
                if (data.data === null) {
                    showNotFound(data.message);
                } else {
                    showError(data.message);
                }
            }
        })
        .catch(error => {
            loader.style.display = 'none';
            console.error('Error:', error);
            showError('Error al realizar la búsqueda. Por favor intente nuevamente.');
        });
    });

    function showError(message) {
        hideAllAlerts();
        document.getElementById('errorMessage').textContent = message;
        errorAlert.style.display = 'block';
    }

    function showNotFound(message) {
        hideAllAlerts();
        document.getElementById('notFoundMessage').textContent = message;
        notFoundAlert.style.display = 'block';
    }

    function showResult(data) {
        hideAllAlerts();
        
        // DEBUG: Verificar datos recibidos
        console.log('=== DATOS COMPLETOS BÚSQUEDA DNI ===');
        console.log('Data completa:', data);
        console.log('Consultas SUNAT:', data.consultas_sunat);
        if (data.consultas_sunat && data.consultas_sunat.length > 0) {
            console.log('Primera consulta:', data.consultas_sunat[0]);
            console.log('Campos disponibles en consulta:', Object.keys(data.consultas_sunat[0]));
        }
        
        const fullName = data.Razón_Social || (data.reniec ? 
            `${data.reniec.ap_pat || ''} ${data.reniec.ap_mat || ''}, ${data.reniec.nombres || ''}`.trim() : 
            'Sin nombre');
        
        document.getElementById('resultTitle').innerHTML = `
            <i class="fas fa-user"></i> ${fullName}
        `;
        
        let html = '';
        
        // Source badge
        if (data.source === 'reniec') {
            html += `<div class="alert alert-info mb-3"><i class="fas fa-database"></i> Datos obtenidos de <strong>RENIEC</strong> como respaldo.</div>`;
        } else if (data.reniec) {
            html += `<div class="alert alert-secondary mb-3"><i class="fas fa-database"></i> Datos complementarios disponibles desde <strong>RENIEC</strong>.</div>`;
        }
        
        html += `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-success">Información Básica</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>DNI:</strong></td>
                            <td>${data.dni || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>RUC Asociado:</strong></td>
                            <td>${data.RUC || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Razón Social:</strong></td>
                            <td>${data.Razón_Social || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Estado:</strong></td>
                            <td><span class="badge ${data.Estado === 'ACTIVO' ? 'bg-success' : 'bg-danger'}">${data.Estado || '-'}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Condición:</strong></td>
                            <td>${data.Condicion || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Dirección:</strong></td>
                            <td>${data.direccion || '-'}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-success">Ubicación</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>UBIGEO:</strong></td>
                            <td>${data.UBIGEO || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Departamento:</strong></td>
                            <td>${data.Departamento || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Provincia:</strong></td>
                            <td>${data.Provincia || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Distrito:</strong></td>
                            <td>${data.Distrito || '-'}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            ${data.Actividad_Economica_Principal ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6 class="text-success">Actividad Económica</h6>
                    <p class="text-muted">${data.Actividad_Economica_Principal}</p>
                </div>
            </div>
            ` : ''}
        `;
        
        // Teléfonos
        if (data.telefonos && Object.keys(data.telefonos).length > 0) {
            const telefonos = data.telefonos;
            const movistarList = telefonos.lista_movistar ? telefonos.lista_movistar.split(',').join(', ') : '-';
            const claroList = telefonos.lista_claro ? telefonos.lista_claro.split(',').join(', ') : '-';
            const entelList = telefonos.lista_entel ? telefonos.lista_entel.split(',').join(', ') : '-';
            
            html += `
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6 class="text-success">Líneas Telefónicas</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Movistar:</strong></td>
                                <td>${movistarList}</td>
                            </tr>
                            <tr>
                                <td><strong>Claro:</strong></td>
                                <td>${claroList}</td>
                            </tr>
                            <tr>
                                <td><strong>Entel:</strong></td>
                                <td>${entelList}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            `;
        }
        
        // Datos RENIEC
        if (data.reniec) {
            const reniec = data.reniec;
            const fullName = [reniec.ap_pat, reniec.ap_mat, reniec.nombres].filter(Boolean).join(' ');
            const formatFecha = (fecha) => {
                if (!fecha) return '-';
                const f = new Date(fecha);
                if (isNaN(f)) return fecha;
                const dia = String(f.getDate()).padStart(2, '0');
                const mes = String(f.getMonth() + 1).padStart(2, '0');
                const anio = f.getFullYear();
                return `${dia}/${mes}/${anio}`;
            };
            const formatSexo = (sexo) => {
                if (sexo === '1') return 'MASCULINO';
                if (sexo === '2') return 'FEMENINO';
                return sexo || '-';
            };
            
            html += `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-success">Datos RENIEC</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <td><strong>Apellido Paterno:</strong></td>
                                    <td>${reniec.ap_pat || '-'}</td>
                                    <td><strong>Apellido Materno:</strong></td>
                                    <td>${reniec.ap_mat || '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Nombres:</strong></td>
                                    <td>${reniec.nombres || '-'}</td>
                                    <td><strong>Nombre completo:</strong></td>
                                    <td>${fullName || '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha de Nacimiento:</strong></td>
                                    <td>${formatFecha(reniec.fecha_nac)}</td>
                                    <td><strong>Fecha de Emisión:</strong></td>
                                    <td>${formatFecha(reniec.fch_emision)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha de Caducidad:</strong></td>
                                    <td>${formatFecha(reniec.fch_caducidad)}</td>
                                    <td><strong>Sexo:</strong></td>
                                    <td>${formatSexo(reniec.sexo)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estado Civil:</strong></td>
                                    <td>${reniec.est_civil || '-'}</td>
                                    <td><strong>Madre:</strong></td>
                                    <td>${reniec.madre || '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Padre:</strong></td>
                                    <td>${reniec.padre || '-'}</td>
                                    <td><strong>Ubigeo Dirección:</strong></td>
                                    <td>${reniec.ubigeo_dir || '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Dirección RENIEC:</strong></td>
                                    <td colspan="3">${reniec.direccion || '-'}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
        html += renderConsultasSunat(data.consultas_sunat, data.representantes || [], data.dni || data.reniec && (data.reniec.nro_dni || data.reniec.dni) || '');
        // Representantes Legales

        
        // Consultas SUNAT
    function renderConsultasSunat(consultas = [], representantes = [], dni = '') {

    console.log('=== RENDER CONSULTAS SUNAT ===');
    console.log('Array de consultas:', consultas);
    console.log('Representantes asociados:', representantes);
    console.log('DNI buscado en contexto:', dni);
    
    if (!Array.isArray(consultas) || consultas.length === 0) {
        console.log('No hay consultas para renderizar');
        return `
            <div class="alert alert-warning mt-3">
                <i class="fas fa-info-circle"></i>
                No se encontraron consultas SUNAT.
            </div>
        `;
    }
    
    consultas.forEach((cons, idx) => {
        console.log(`Consulta ${idx}:`, cons);
        console.log(`Campos de consulta ${idx}:`, Object.keys(cons));
    });

    return `
        <div class="row mt-4">
            <div class="col-12">

                <div class="card border-0 shadow-sm">
                    
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-building-columns"></i>
                            Consultas SUNAT - TRABAJOS VINCULADOS
                        </h6>
                    </div>

                    <div class="card-body p-0">

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle mb-0">

                                <thead class="table-light">
                                    <tr>
                                        <th>Cargo que Ocupa Persona</th>
                                        <th>RUC LABORAL</th>
                                        <th>ENTIDAD LABORAL DONDE FIGURA</th>
                                        <th>Actividad Económica</th>
                                        <th>Estado</th>
                                        <th>Condición</th>
                                        <th>Trabajadores</th>
                                        <th>Anexos</th>

                                    </tr>
                                </thead>

                                <tbody>

                                    ${consultas.map((cons, index) => {

                                        const estado = cons.estado_contribuyente || '-';
                                        const condicion = cons.condicion_contribuyente || '-';
                                        // intentar obtener el cargo desde la consulta o desde la lista de representantes
                                        const cargo = (() => {
                                            const rep = Array.isArray(representantes) ? representantes[index] : null;
                                            if (rep && rep.cargo) return rep.cargo;
                                            if (cons.cargo) return cons.cargo;
                                            if (cons.Cargo) return cons.Cargo;
                                            if (cons.CARGO) return cons.CARGO;
                                            if (Array.isArray(representantes) && representantes.length) {
                                                const repMatch = representantes.find(r => {
                                                    if (r.consulta_id && (String(r.consulta_id) === String(cons.id) || String(r.consulta_id) === String(cons.consulta_id))) return true;
                                                    if (r.numero_documento && dni && String(r.numero_documento) === String(dni)) return true;
                                                    return false;
                                                });
                                                if (repMatch && repMatch.cargo) return repMatch.cargo;
                                            }
                                            return '-';
                                        })();

                                        return `
                                            <tr>
                                                <td>
                                                    ${cargo}
                                                </td>        

                                                <td>
                                                    ${cons.ruc || '-'}
                                                </td>

                                                <td>
                                                    ${cons.nombre_razon_social || '-'}
                                                </td>

                                                <td>
                                                    ${cons.actividades_economicas || '-'}
                                                </td>

                                                <td>
                                                    <span class="badge ${
                                                        estado === 'ACTIVO'
                                                            ? 'bg-success'
                                                            : 'bg-danger'
                                                    }">
                                                        ${estado}
                                                    </span>
                                                </td>

                                                <td>
                                                    ${condicion}
                                                </td>

                                                <td class="text-center">
                                                    ${cons.cant_trabajadores || '0'}
                                                </td>

                                                <td class="text-center">
                                                    ${cons.cant_anexos || '0'}
                                                </td>

      

                                            </tr>
                                        `;
                                    }).join('')}

                                </tbody>

                            </table>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    `;
}
        
        document.getElementById('resultContent').innerHTML = html;
        resultCard.style.display = 'block';
    }
});
</script>
@endsection
