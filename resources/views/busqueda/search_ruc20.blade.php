@extends('layouts.app')

@section('title', 'Buscar RUC 20 - Sistema de Búsqueda')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Encabezado -->
            <div class="mb-4">
                <h2 class="text-primary">
                    <i class="fas fa-search"></i> Búsqueda Individual - RUC 20
                </h2>
                <p class="text-muted">
                    Ingresa un número de RUC (11 dígitos) para obtener información detallada de la empresa
                </p>
                <a href="{{ route('busqueda.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al inicio
                </a>
            </div>

            <!-- Formulario de Búsqueda -->
            <div class="card shadow-lg mb-4">
                <div class="card-body">
                    <form id="searchRuc20Form" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="ruc20Input" class="form-label">Número de RUC</label>
                            <div class="input-group input-group-lg">
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="ruc20Input" 
                                    name="ruc" 
                                    placeholder="Ej: 20101010101"
                                    maxlength="11"
                                    pattern="\d{11}"
                                    required
                                >
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                El RUC debe contener exactamente 11 dígitos y comenzar con 20
                            </small>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Loader -->
            <div id="loader" class="text-center my-4" style="display: none;">
                <div class="spinner-border text-primary" role="status">
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
                    <div class="card-header bg-primary text-white">
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
    const form = document.getElementById('searchRuc20Form');
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

    // Formatear RUC mientras se escribe
    document.getElementById('ruc20Input').addEventListener('input', function(e) {
        // Solo permitir números
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
        
        // Limitar a 11 dígitos
        if (e.target.value.length > 11) {
            e.target.value = e.target.value.slice(0, 11);
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const ruc = document.getElementById('ruc20Input').value.trim();
        
        if (!ruc) {
            showError('Por favor ingrese un número de RUC');
            return;
        }

        if (ruc.length !== 11) {
            showError('El RUC debe tener exactamente 11 dígitos');
            return;
        }

        if (!ruc.startsWith('20')) {
            showError('El RUC debe comenzar con 20 para personas jurídicas');
            return;
        }

        // Mostrar loader
        loader.style.display = 'block';
        hideAllAlerts();

        // Realizar búsqueda
        fetch('/busqueda/ruc20', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ ruc: ruc })
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
        
        document.getElementById('resultTitle').innerHTML = `
            <i class="fas fa-building"></i> ${data.Razón_Social || 'Sin razón social'}
        `;
        
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">Información General</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>RUC:</strong></td>
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
                            <td><strong>Tipo:</strong></td>
                            <td>${data.Tipo || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Dirección:</strong></td>
                            <td>${data.direccion || '-'}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">Información Empresarial</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Actividad Económica:</strong></td>
                            <td>${data.Actividad_Economica_Principal || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Motivo:</strong></td>
                            <td>${data.motivo || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Subsegmento Agosto:</strong></td>
                            <td>${data.subsegmento_agosto || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Ganado por:</strong></td>
                            <td>${data.ganado_por || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Gerente:</strong></td>
                            <td>${data.gerente || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>SML:</strong></td>
                            <td>${data.s_m_l || '-'}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <h6 class="text-primary">Ubicación</h6>
                    <table class="table table-sm">
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
                        <tr>
                            <td><strong>UBIGEO:</strong></td>
                            <td>${data.UBIGEO || '-'}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">Líneas Telefónicas</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Movistar:</strong></td>
                            <td>${data.movistar_lines || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Claro:</strong></td>
                            <td>${data.claro_lines || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Entel:</strong></td>
                            <td>${data.entel_lines || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Competencia:</strong></td>
                            <td>${data.competence_lines || '-'}</td>
                        </tr>
                    </table>
                </div>
            </div>
        `;
        
        // Representantes Legales
        if (data.representantes && data.representantes.length > 0) {
            html += `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-primary">Representantes Legales</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Cargo</th>
                                        <th>Tipo Doc.</th>
                                        <th>Número Doc.</th>
                                        <th>Teléfonos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.representantes.map(rep => `
                                        <tr>
                                            <td>${rep.nombre || '-'}</td>
                                            <td>${rep.cargo || '-'}</td>
                                            <td>${rep.tipo_documento || '-'}</td>
                                            <td>${rep.numero_documento || '-'}</td>
                                            <td>
                                                ${rep.telefonos && Object.keys(rep.telefonos).length > 0 ? 
                                                    `Movistar: ${rep.telefonos.lista_movistar || '-'}, Claro: ${rep.telefonos.lista_claro || '-'}, Entel: ${rep.telefonos.lista_entel || '-'}` : 
                                                    '-'}
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Consultas SUNAT
        if (data.consultas_sunat && data.consultas_sunat.length > 0) {
            html += `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="text-primary">
    Consultas SUNAT - RUC: ${data.RUC || '-'}    - Ultima actualizacion:
    ${data.consultas_sunat[0]?.fecha_consulta || '-'}
</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Actividad Economica</th> 
                                        <th>Estado</th>
                                        <th>Condición</th>
                                        <th>Trabajadores</th>
                                        <th>Anexos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.consultas_sunat.map(cons => `
                                        <tr>
                                            <td>${cons.nombre_razon_social || '-'}</td>
                                            <td>${cons.actividades_economicas || '-'}</td>
                                            <td><span class="badge ${cons.estado_contribuyente === 'ACTIVO' ? 'bg-success' : 'bg-danger'}">${cons.estado_contribuyente || '-'}</span></td>
                                            <td>${cons.condicion_contribuyente || '-'}</td>
                                            <td>${cons.cant_trabajadores || '-'}</td>
                                            <td>${cons.cant_anexos || '-'}</td>
                                        </tr>
                                    `).join('')}

                                </tbody>
                            </table>
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
