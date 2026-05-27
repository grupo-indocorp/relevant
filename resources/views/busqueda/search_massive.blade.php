@extends('layouts.app')

@section('title', 'Búsqueda Masiva - Sistema de Búsqueda')

@section('content')
<style>
    .cursor-pointer {
        cursor: pointer;
    }

    .cursor-pointer:hover {
        background: #f8f9fa;
    }

    .table td,
    .table th {
        vertical-align: middle;
    }

    #detailDrawer .offcanvas-body {
        overflow: auto;
    }

    .table-responsive {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 600px;
        -webkit-overflow-scrolling: touch;
    }

    /* Scrollbar visible styling */
    .table-responsive::-webkit-scrollbar {
        width: 12px;
        height: 12px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 6px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 6px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .actividad-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        background-color: #e7f1ff;
        color: #0d6efd;
        border: 1px solid #cfe2ff;
        font-size: 0.85rem;
    }

    .actividad-tag button {
        border: none;
        background: transparent;
        color: #0d6efd;
        cursor: pointer;
        line-height: 1;
        padding: 0;
    }

    .actividad-option-highlight {
        background-color: #fff3cd;
        border-radius: 0.2rem;
        padding: 0 0.2rem;
    }

    .actividad-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 0.75rem;
    }

    .actividad-grid-item {
        cursor: pointer;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 0.75rem 0.9rem;
        background: #fff;
        transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        min-height: 58px;
    }

    .actividad-grid-item:hover {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.08);
    }

    .actividad-grid-item.selected {
        background: #e7f1ff;
        border-color: #0d6efd;
    }

    .actividad-list-empty {
        min-height: 120px;
    }

    .table thead th {
        position: sticky;
        top: 0;
        background-color: #343a40;
        z-index: 10;
    }

    .table {
        min-width: 1000px;
    }

    @media (max-width: 768px) {
        .table {
            font-size: 0.875rem;
        }
        
        .btn-lg {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        
        .card-body {
            padding: 1rem;
        }
    }

    @media (max-width: 576px) {
        .row .col-md-4,
        .row .col-md-6 {
            margin-bottom: 0.5rem;
        }
        
        .table {
            font-size: 0.75rem;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Encabezado -->
            <div class="mb-4">
                <h2 class="text-warning">
                    <i class="fas fa-table"></i> Búsqueda Masiva
                </h2>
                <p class="text-muted">
                    Consulta múltiples registros de RUC 10 o RUC 20 con filtros avanzados
                </p>
                <a href="{{ route('busqueda.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al inicio
                </a>
            </div>

            <!-- Selector de Tipo de Búsqueda -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <button class="btn btn-outline-primary btn-lg w-100" id="btnRuc20">
                        <i class="fas fa-search"></i> Buscar RUC 20 (Empresas)
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-outline-success btn-lg w-100" id="btnRuc10">
                        <i class="fas fa-search"></i> Buscar RUC 10 (Personas)
                    </button>
                </div>
            </div>

            <!-- Contenedor de Búsqueda Masiva, Filtros-->
            <div id="massiveSearchContainer" style="display: none;">
                <!-- Filtros -->
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros de Búsqueda</h5>
                    </div>
                    <div class="card-body">
                        <form id="filterForm">
                            <div class="row mb-3">
                                <!-- Búsqueda por Identificador -->
                                <div class="col-md-6">
                                    <label for="searchId" class="form-label" id="labelSearchId">Buscar por RUC/DNI</label>
                                    <input type="text" class="form-control" id="searchId" placeholder="Ingrese RUC o DNI..."
                                        maxlength="11">
                                </div>

                                  <!-- 
                                <div class="col-md-6">
                                    <label for="searchRazonSocial" class="form-label">Razón Social / Nombres</label>
                                    <input type="text" class="form-control" id="searchRazonSocial" placeholder="Ingrese razón social o nombres...">
                                </div>
-->
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="searchEstado" class="form-label">Estado</label>
                                    <select class="form-select" id="searchEstado">
                                        <option value="">-- Todos --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="searchCondicion" class="form-label">Condición</label>
                                    <select class="form-select" id="searchCondicion">
                                        <option value="">-- Todas --</option>
                                    </select>
                                </div>
                                <div class="col-md-4" id="trabajadoresFilter" style="display: none;">
                                    <label for="searchTrabajadores" class="form-label">Trabajadores (última consulta)</label>
                                    <select class="form-select" id="searchTrabajadores">
                                        <option value="">-- Todos --</option>
                                        <option value="0-5">0 - 5</option>
                                        <option value="5-10">5 - 10</option>
                                        <option value="10-50">10 - 50</option>
                                        <option value="50-100">50 - 100</option>
                                        <option value="100-500">100 - 500</option>
                                        <option value="500+">500 a más</option>
                                    </select>
                                </div>
                                <div class="col-md-4" id="anexosFilter" style="display: none;">
                                    <label for="searchAnexos" class="form-label">Anexos (última consulta)</label>
                                    <select class="form-select" id="searchAnexos">
                                        <option value="">-- Todos --</option>
                                        <option value="0-5">0 - 5</option>
                                        <option value="5-10">5 - 10</option>
                                        <option value="10-50">10 - 50</option>
                                        <option value="50-100">50 - 100</option>
                                        <option value="100-500">100 - 500</option>
                                        <option value="500+">500 a más</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="searchDepartamento" class="form-label">Departamento</label>
                                    <select class="form-select" id="searchDepartamento">
                                        <option value="">-- Todos --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="searchProvincia" class="form-label">Provincia</label>
                                    <select class="form-select" id="searchProvincia">
                                        <option value="">-- Todos --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="searchDistrito" class="form-label">Distrito</label>
                                    <select class="form-select" id="searchDistrito">
                                        <option value="">-- Todos --</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Actividad Económica</label>
                                    <div class="d-flex gap-2 flex-wrap align-items-center">
                                        <button type="button" class="btn btn-outline-secondary flex-grow-1 text-start" id="btnOpenActividadEconomicaModal">
                                            <i class="fas fa-search me-2"></i> Buscar actividad económica...
                                        </button>
                                        <span id="selectedActividadesSummary" class="text-muted small">Ninguna seleccionada</span>
                                    </div>
                                    <div id="selectedActividadesContainer" class="mt-2 d-flex flex-wrap gap-2"></div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="perPageSelect" class="form-label">Filas por página</label>
                                    <select class="form-select" id="perPageSelect">
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="500">500</option>
                                        <option value="1000">1000</option>
                                        <option value="1500">1500</option>
                                        <option value="2000">2000</option>
                                        <option value="3000">3000</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-danger w-100" id="resetFiltersBtn">
                                        <i class="fas fa-redo"></i> Limpiar Filtros
                                    </button>
                                </div>
                            </div>

                            <!-- Botón central para abrir modal de exportación -->
                            <div class="row mb-3">
                                <div class="col-12 text-center">
                                    <button type="button" class="btn btn-success btn-lg w-75" id="btnOpenExportModal" style="box-shadow: 0 4px 14px rgba(0,0,0,0.08);">
                                        <i class="fas fa-file-download"></i> DESCARGAR COMO CSV
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 text-center">
                                    <button type="button" class="btn btn-primary btn-lg" id="btnSearch">
                                        <i class="fas fa-search"></i> Realizar Búsqueda
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal de selección de Actividad Económica -->
                <div class="modal fade" id="actividadEconomicaModal" tabindex="-1" aria-labelledby="actividadEconomicaModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="actividadEconomicaModalLabel">Seleccionar Actividad Económica</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <input type="text" id="actividadEconomicaSearchInput" class="form-control" placeholder="Buscar actividad económica..." autocomplete="off">
                                </div>
                                <div id="actividadEconomicaList" class="actividad-grid actividad-list-empty border rounded p-2" style="max-height: 420px; overflow-y: auto;"></div>
                                <div class="mt-2 text-muted small">Las actividades se precargan aquí. Escribe para resaltar y filtrar en el frente, selecciona y guarda.</div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="actividadEconomicaSaveBtn">Guardar selección</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loader -->
                <div id="loader" class="text-center my-4" style="display: none;">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Realizando búsqueda masiva...</p>
                </div>

                <!-- Tabla de Resultados -->
                <div id="resultsContainer" style="display: none;">
                    <div class="card shadow-lg">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-table"></i> 
                                Resultados de Búsqueda 
                                <span id="resultCount" class="badge bg-dark ms-2">0</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="resultsTable">
                                    <thead class="table-dark">
                                        <tr id="tableHeaders">
                                            <!-- Headers dinámicos -->
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody">
                                        <!-- Filas dinámicas -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Paginación -->
                            <nav aria-label="Paginación de resultados" id="paginationContainer" class="mt-3">
                                <!-- Paginación dinámica -->
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                <!-- Modal para exportar CSV (Cantidad y confirmación) -->
                <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exportModalLabel"><i class="fas fa-file-export"></i> Exportar a CSV</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">CANTIDAD DE FILAS A EXPORTAR:</label>
                                    <div class="input-group">
                                        <input type="number" id="modalExportLimit" class="form-control" placeholder="Deja en blanco para exportar todos" min="1">
                                        <span class="input-group-text" id="modalTotalText">de 0 total</span>
                                    </div>
                                </div>
                                <div class="alert alert-info" role="alert">
                                    <strong>Nota:</strong> La exportación se realiza por lotes para optimizar memoria y velocidad. Registros con 100+ filas pueden tomar algunos segundos.
                                </div>
                                <input type="hidden" id="modalExportBatchSize" value="10000">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                                <button type="button" class="btn btn-primary" id="modalExportBtn"><i class="fas fa-file-csv"></i> EXPORTAR</button>
                            </div>
                        </div>
                    </div>
                </div>
<!-- Modal para detalles -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="detailModalLabel">Detalles del Registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailContent" style="max-height: 70vh; overflow-y: auto;">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentSearchType = null; // 'ruc20' o 'ruc10'
    let currentPage = 1;
    let totalRecords = 0;
    let totalPages = 0;

    // Botones de selección de tipo
    document.getElementById('btnRuc20').addEventListener('click', function() {
        currentSearchType = 'ruc20';
        selectedActividadesEconomicas = [];
        renderSelectedActividadesSummary();
        showSearchContainer('RUC 20 (Empresas)', 'ruc20');
        document.getElementById('trabajadoresFilter').style.display = 'block';
        document.getElementById('anexosFilter').style.display = 'block';
        loadFilterOptions('ruc20');
        fetchActividadEconomicaOptions('');
    });

    document.getElementById('btnRuc10').addEventListener('click', function() {
        currentSearchType = 'ruc10';
        selectedActividadesEconomicas = [];
        renderSelectedActividadesSummary();
        showSearchContainer('RUC 10 (Personas)', 'ruc10');
        document.getElementById('trabajadoresFilter').style.display = 'none';
        document.getElementById('anexosFilter').style.display = 'none';
        loadFilterOptions('ruc10');
        fetchActividadEconomicaOptions('');
    });

    function showSearchContainer(title, type) {
        document.getElementById('massiveSearchContainer').style.display = 'block';
        document.getElementById('labelSearchId').textContent = type === 'ruc20' ? 'Buscar por RUC' : 'Buscar por DNI';
        document.getElementById('searchId').placeholder = type === 'ruc20' ? 'Ingrese RUC...' : 'Ingrese DNI...';
        document.getElementById('searchId').maxLength = type === 'ruc20' ? 11 : 8;
        
        // Actualizar estilos de botones
        document.getElementById('btnRuc20').classList.toggle('btn-primary', type === 'ruc20');
        document.getElementById('btnRuc20').classList.toggle('btn-outline-primary', type !== 'ruc20');
        document.getElementById('btnRuc10').classList.toggle('btn-success', type === 'ruc10');
        document.getElementById('btnRuc10').classList.toggle('btn-outline-success', type !== 'ruc10');
    }

    // Cargar opciones de filtros dinámicamente
    function loadFilterOptions(searchType) {
        // Cargar opciones de Estado
        fetch(`/api/busqueda/filter-options/${searchType}/Estado`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.options) {
                    const select = document.getElementById('searchEstado');
                    select.innerHTML = '<option value="">-- Todos --</option>';
                    data.options.forEach(option => {
                        select.innerHTML += `<option value="${option}">${option}</option>`;
                    });
                }
            })
            .catch(error => console.error('Error cargando estados:', error));

        // Cargar opciones de Condición
        fetch(`/api/busqueda/filter-options/${searchType}/Condicion`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.options) {
                    const select = document.getElementById('searchCondicion');
                    select.innerHTML = '<option value="">-- Todas --</option>';
                    data.options.forEach(option => {
                        select.innerHTML += `<option value="${option}">${option}</option>`;
                    });
                }
            })
            .catch(error => console.error('Error cargando condiciones:', error));

        // Cargar opciones de Departamento
        fetch(`/api/busqueda/filter-options/${searchType}/Departamento`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.options) {
                    const select = document.getElementById('searchDepartamento');
                    select.innerHTML = '<option value="">-- Todos --</option>';
                    data.options.forEach(option => {
                        select.innerHTML += `<option value="${option}">${option}</option>`;
                    });
                }
            })
            .catch(error => console.error('Error cargando departamentos:', error));
    }

    // Cargar provincias cuando se selecciona un departamento
    document.getElementById('searchDepartamento').addEventListener('change', function() {
        const departamento = this.value;
        if (!departamento || !currentSearchType) {
            document.getElementById('searchProvincia').innerHTML = '<option value="">-- Todos --</option>';
            document.getElementById('searchDistrito').innerHTML = '<option value="">-- Todos --</option>';
            return;
        }

        fetch(`/api/busqueda/locations/${currentSearchType}/provincias/${departamento}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.options) {
                    const select = document.getElementById('searchProvincia');
                    select.innerHTML = '<option value="">-- Todos --</option>';
                    data.options.forEach(option => {
                        select.innerHTML += `<option value="${option}">${option}</option>`;
                    });
                }
            })
            .catch(error => console.error('Error cargando provincias:', error));
    });

    // Cargar distritos cuando se selecciona una provincia
    document.getElementById('searchProvincia').addEventListener('change', function() {
        const departamento = document.getElementById('searchDepartamento').value;
        const provincia = this.value;
        if (!departamento || !provincia || !currentSearchType) {
            document.getElementById('searchDistrito').innerHTML = '<option value="">-- Todos --</option>';
            return;
        }

        fetch(`/api/busqueda/locations/${currentSearchType}/distritos/${departamento}/${provincia}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.options) {
                    const select = document.getElementById('searchDistrito');
                    select.innerHTML = '<option value="">-- Todos --</option>';
                    data.options.forEach(option => {
                        select.innerHTML += `<option value="${option}">${option}</option>`;
                    });
                }
            })
            .catch(error => console.error('Error cargando distritos:', error));
    });

    let selectedActividadesEconomicas = [];
    let allActividadesEconomicas = {
        ruc20: [],
        ruc10: []
    };
    let lastActividadesFetch = [];
    let actividadSearchDebounceTimer = null;
    let isLoadingActividadesEconomicas = false;

    document.getElementById('btnOpenActividadEconomicaModal').addEventListener('click', openActividadEconomicaModal);
    document.getElementById('actividadEconomicaSearchInput').addEventListener('input', function() {
        const query = this.value.trim();
        clearTimeout(actividadSearchDebounceTimer);
        actividadSearchDebounceTimer = setTimeout(() => {
            if (allActividadesEconomicas[currentSearchType] && allActividadesEconomicas[currentSearchType].length > 0) {
                lastActividadesFetch = allActividadesEconomicas[currentSearchType];
                renderActividadEconomicaOptions(lastActividadesFetch, query);
            } else {
                fetchActividadEconomicaOptions('');
            }
        }, 400);
    });
    document.getElementById('actividadEconomicaSaveBtn').addEventListener('click', saveActividadEconomicaSelection);

    function openActividadEconomicaModal() {
        if (!currentSearchType) {
            alert('Por favor seleccione RUC 10 o RUC 20 antes de buscar actividad económica.');
            return;
        }

        document.getElementById('actividadEconomicaSearchInput').value = '';
        renderSelectedActividadesSummary();

        if (allActividadesEconomicas[currentSearchType] && allActividadesEconomicas[currentSearchType].length > 0) {
            lastActividadesFetch = allActividadesEconomicas[currentSearchType];
            renderActividadEconomicaOptions(lastActividadesFetch, '');
        } else {
            fetchActividadEconomicaOptions('');
        }

        const modal = new bootstrap.Modal(document.getElementById('actividadEconomicaModal'), {
            backdrop: 'static',
            keyboard: false
        });
        modal.show();
    }

    function fetchActividadEconomicaOptions(query) {
        if (!currentSearchType) return;
        const normalizedQuery = query.trim();

        if (allActividadesEconomicas[currentSearchType] && allActividadesEconomicas[currentSearchType].length > 0) {
            lastActividadesFetch = allActividadesEconomicas[currentSearchType];
            renderActividadEconomicaOptions(lastActividadesFetch, normalizedQuery);
            return;
        }

        isLoadingActividadesEconomicas = true;
        const list = document.getElementById('actividadEconomicaList');
        list.innerHTML = '<div class="p-3 text-center text-muted">Cargando actividades económicas...</div>';

        const params = new URLSearchParams();
        params.append('query', '');
        params.append('limit', 2000);

        fetch(`/api/busqueda/${currentSearchType}/actividades-economicas?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                isLoadingActividadesEconomicas = false;
                if (data.success && Array.isArray(data.data)) {
                    allActividadesEconomicas[currentSearchType] = data.data;
                    lastActividadesFetch = data.data;
                    renderActividadEconomicaOptions(data.data, normalizedQuery);
                } else {
                    renderActividadEconomicaOptions([], normalizedQuery);
                }
            })
            .catch(error => {
                isLoadingActividadesEconomicas = false;
                console.error('Error obteniendo actividades económicas:', error);
                list.innerHTML = '<div class="p-3 text-danger">No se pudo cargar actividades económicas. Recarga la página e intenta de nuevo.</div>';
            });
    }

    function renderActividadEconomicaOptions(items, query) {
        const list = document.getElementById('actividadEconomicaList');
        list.innerHTML = '';

        if (!Array.isArray(items) || items.length === 0) {
            list.innerHTML = '<div class="p-3 text-muted">No se encontraron actividades económicas. Prueba con otra búsqueda.</div>';
            return;
        }

        const normalizedQuery = query.trim().toLowerCase();
        const sortedItems = normalizedQuery === ''
            ? items
            : [...items].sort((a, b) => {
                const aValue = (a || '').toLowerCase();
                const bValue = (b || '').toLowerCase();
                const aIndex = aValue.indexOf(normalizedQuery);
                const bIndex = bValue.indexOf(normalizedQuery);

                if (aIndex === -1 && bIndex === -1) return 0;
                if (aIndex === -1) return 1;
                if (bIndex === -1) return -1;
                if (aIndex !== bIndex) return aIndex - bIndex;
                return aValue.localeCompare(bValue);
            });

        sortedItems.forEach((item, index) => {
            const value = item || '';
            const isSelected = selectedActividadesEconomicas.includes(value);
            const itemId = `actividadOption_${index}`;
            const div = document.createElement('div');
            div.className = `actividad-grid-item ${isSelected ? 'selected' : ''}`;
            div.setAttribute('data-value', value);
            div.innerHTML = `
                <div class="form-check d-flex align-items-center justify-content-between w-100 mb-0">
                    <div class="form-check form-check-inline flex-grow-1 mb-0">
                        <input class="form-check-input" type="checkbox" id="${itemId}" ${isSelected ? 'checked' : ''}>
                        <label class="form-check-label mb-0" for="${itemId}">${highlightTerm(value, normalizedQuery)}</label>
                    </div>
                    <span class="badge ${isSelected ? 'bg-primary' : 'bg-secondary'}">${isSelected ? 'Seleccionado' : 'Seleccionar'}</span>
                </div>
            `;

            div.addEventListener('click', function(event) {
                if (event.target.tagName !== 'INPUT') {
                    toggleActividadEconomicaSelection(value);
                }
            });

            const checkbox = div.querySelector('input');
            checkbox.addEventListener('change', function() {
                toggleActividadEconomicaSelection(value);
            });

            list.appendChild(div);
        });
    }

    function toggleActividadEconomicaSelection(value) {
        const index = selectedActividadesEconomicas.indexOf(value);
        if (index === -1) {
            selectedActividadesEconomicas.push(value);
        } else {
            selectedActividadesEconomicas.splice(index, 1);
        }
        renderSelectedActividadesSummary();
        renderActividadEconomicaOptions(lastActividadesFetch, document.getElementById('actividadEconomicaSearchInput').value.trim());
    }

    function renderSelectedActividadesSummary() {
        const container = document.getElementById('selectedActividadesContainer');
        const summary = document.getElementById('selectedActividadesSummary');
        container.innerHTML = '';

        if (selectedActividadesEconomicas.length === 0) {
            summary.textContent = 'Ninguna seleccionada';
            return;
        }

        summary.textContent = `${selectedActividadesEconomicas.length} seleccionada${selectedActividadesEconomicas.length === 1 ? '' : 's'}`;
        selectedActividadesEconomicas.forEach((value, idx) => {
            const tag = document.createElement('div');
            tag.className = 'actividad-tag';
            tag.innerHTML = `<span>${value}</span><button type="button" aria-label="Eliminar actividad ${value}" data-index="${idx}">&times;</button>`;
            tag.querySelector('button').addEventListener('click', function() {
                selectedActividadesEconomicas.splice(idx, 1);
                renderSelectedActividadesSummary();
                renderActividadEconomicaOptions(lastActividadesFetch, document.getElementById('actividadEconomicaSearchInput').value.trim());
            });
            container.appendChild(tag);
        });
    }

    function saveActividadEconomicaSelection() {
        document.getElementById('selectedActividadesSummary').textContent = selectedActividadesEconomicas.length === 0
            ? 'Ninguna seleccionada'
            : `${selectedActividadesEconomicas.length} seleccionada${selectedActividadesEconomicas.length === 1 ? '' : 's'}`;

        const modalElement = document.getElementById('actividadEconomicaModal');
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
    }

    function buildSearchParams(filters) {
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach(item => {
                    params.append(`${key}[]`, item);
                });
            } else {
                params.append(key, value);
            }
        });
        return params;
    }

    function getFilters() {
        const filters = {
            page: currentPage,
            per_page: parseInt(document.getElementById('perPageSelect').value)
        };

        const id = document.getElementById('searchId').value.trim();
        if (id) {
            if (currentSearchType === 'ruc20') {
                filters.ruc = id;
            } else {
                filters.dni = id;
            }
        }

        const razonSocialElement = document.getElementById('searchRazonSocial');
        const razonSocial = razonSocialElement ? razonSocialElement.value.trim() : '';
        if (razonSocial) filters.razon_social = razonSocial;

        const estadoElement = document.getElementById('searchEstado');
        const estado = estadoElement ? estadoElement.value : '';
        if (estado) filters.estado = estado;

        const condicionElement = document.getElementById('searchCondicion');
        const condicion = condicionElement ? condicionElement.value : '';
        if (condicion) filters.condicion = condicion;

        const departamentoElement = document.getElementById('searchDepartamento');
        const departamento = departamentoElement ? departamentoElement.value : '';
        if (departamento) filters.departamento = departamento;

        const provinciaElement = document.getElementById('searchProvincia');
        const provincia = provinciaElement ? provinciaElement.value : '';
        if (provincia) filters.provincia = provincia;

        const distritoElement = document.getElementById('searchDistrito');
        const distrito = distritoElement ? distritoElement.value : '';
        if (distrito) filters.distrito = distrito;

        if (selectedActividadesEconomicas.length > 0) {
            filters.actividad_economica = selectedActividadesEconomicas;
        }

        if (currentSearchType === 'ruc20') {
            const trabajadores = document.getElementById('searchTrabajadores').value;
            if (trabajadores) filters.min_trabajadores = trabajadores;
            
            const anexos = document.getElementById('searchAnexos').value;
            if (anexos) filters.min_anexos = anexos;
        }

        return filters;
    }

    function performSearch() {
        if (!currentSearchType) {
            alert('Por favor seleccione un tipo de búsqueda (RUC 20 o RUC 10)');
            return;
        }

        currentPage = 1;
        const filters = getFilters();
        const params = buildSearchParams(filters);

        document.getElementById('loader').style.display = 'block';
        document.getElementById('resultsContainer').style.display = 'none';

        const endpoint = currentSearchType === 'ruc20' ? 
            '/busqueda/ruc20/masivo' : 
            '/busqueda/ruc10/masivo';

        fetch(`${endpoint}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                console.log('====================================');
                console.log('RESPUESTA COMPLETA BACKEND');
                console.log(data);
                console.log('JSON STRINGIFY');
                console.log(JSON.stringify(data, null, 2));
                console.log('====================================');
                document.getElementById('loader').style.display = 'none';
                
                if (data.success) {
                    displayResults(data);
                } else {
                    alert('Error en la búsqueda: ' + data.message);
                }
            })
            .catch(error => {
                document.getElementById('loader').style.display = 'none';
                console.error('Error:', error);
                alert('Error al realizar la búsqueda. Por favor intente nuevamente.');
            });
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\\]\\]/g, '\\\\$&');
    }

    function highlightTerm(text, query) {
        if (!query) return escapeHtml(text);
        const escapedQuery = escapeRegExp(query);
        const regex = new RegExp(escapedQuery, 'gi');
        return escapeHtml(text).replace(regex, match => `<span class=\"actividad-option-highlight\">${match}</span>`);
    }

    // Validar entrada de ID
    document.getElementById('searchId').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
        if (e.target.value.length > parseInt(e.target.maxLength)) {
            e.target.value = e.target.value.slice(0, e.target.maxLength);
        }
    });

    // Botón de búsqueda
    document.getElementById('btnSearch').addEventListener('click', performSearch);

    // Botón limpiar filtros
    document.getElementById('resetFiltersBtn').addEventListener('click', function() {
        document.getElementById('filterForm').reset();
        document.getElementById('searchProvincia').innerHTML = '<option value="">-- Todos --</option>';
        document.getElementById('searchDistrito').innerHTML = '<option value="">-- Todos --</option>';
        selectedActividadesEconomicas = [];
        renderSelectedActividadesSummary();
        document.getElementById('resultsContainer').style.display = 'none';
        currentPage = 1;
        document.getElementById('actividadEconomicaSearchInput').value = '';
        if (currentSearchType) {
            loadFilterOptions(currentSearchType);
            lastActividadesFetch = allActividadesEconomicas[currentSearchType] || [];
        }
    });

    function displayResults(data) {
        const records = data.data || [];
        const pagination = data.pagination || {};
        
        console.log('=== BÚSQUEDA MASIVA RUC20 DEBUG ===');
        console.log('Records recibidos:', records);
        
        if (currentSearchType === 'ruc20' && records.length > 0) {
            console.log('Primer registro:', records[0]);
            console.log('Campos del primer registro:', Object.keys(records[0]));
            console.log('Buscando campos de representantes...');
            const repFields = Object.keys(records[0]).filter(k => k.startsWith('rep_'));
            console.log('Campos de representantes encontrados:', repFields);
        }
        
        totalRecords = pagination.total || 0;
        totalPages = pagination.total_pages || 0;
        currentPage = pagination.page || 1;

        document.getElementById('resultCount').textContent = totalRecords.toLocaleString();

        // Configurar headers de tabla
        configureTableHeaders();

        // Llenar tabla
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = '';

        if (records.length === 0) {
            tbody.innerHTML = '<tr><td colspan="100%" class="text-center text-muted">No se encontraron resultados</td></tr>';
        } else {
            records.forEach((record, index) => {
                const row = createTableRow(record, index);
                tbody.appendChild(row);
            });
        }

        // Configurar paginación
        configurePagination();

        // Mostrar contenedor
        document.getElementById('resultsContainer').style.display = 'block';
    }

    function configureTableHeaders() {
        const headers = document.getElementById('tableHeaders');
        headers.innerHTML = '';

        if (currentSearchType === 'ruc20') {
            headers.innerHTML = `
                <tr>
                    <th>Acciones</th>
                    <th>RUC</th>
                    <th>Razón Social</th>
                    <th>Estado</th>
                    <th>Condición</th>
                    <th>UBIGEO</th>
                    <th>Departamento</th>
                    <th>Provincia</th>
                    <th>Distrito</th>
                    <th>Dirección</th>
                    <th>Actividades</th>
                    <th>Trabajadores</th>
                    <th>Anexos</th>
                </tr>
            `;
        } else {
            headers.innerHTML = `
                <tr>
                    <th>Acciones</th>
                    <th>DNI</th>
                    <th>RUC</th>
                    <th>Razón Social</th>
                    <th>Estado</th>
                    <th>Condición</th>
                    <th>UBIGEO</th>
                    <th>Departamento</th>
                    <th>Provincia</th>
                    <th>Distrito</th>
                    <th>Dirección</th>
                </tr>
            `;
        }
    }

    function createTableRow(record, index) {
        const row = document.createElement('tr');

        const globalIndex = ((currentPage - 1) * parseInt(document.getElementById('perPageSelect').value)) + index + 1;

        // Obtener datos de consultas SUNAT si existen
        const consulta = {
                fecha_consulta: record.consulta_fecha_consulta,
                nombre_razon_social: record.consulta_nombre_razon_social,
                estado_contribuyente: record.consulta_estado_contribuyente,
                condicion_contribuyente: record.consulta_condicion_contribuyente,
                actividades_economicas: record.consulta_actividades_economicas,
                cant_trabajadores: record.consulta_cant_trabajadores,
                cant_anexos: record.consulta_cant_anexos
            };

        if (currentSearchType === 'ruc20') {
            row.innerHTML = `
                <td>
                    <button class="btn btn-sm btn-info" onclick="event.stopPropagation(); showDetails(${JSON.stringify(record).replace(/"/g, '&quot;')})">
                        <i class="fas fa-eye">+</i>
                    </button>
                </td>
                <td><strong>${record.RUC || '-'}</strong></td>
                <td>${record.Razón_Social || '-'}</td>
                <td><span class="badge ${record.Estado === 'ACTIVO' ? 'bg-success' : 'bg-danger'}">${record.Estado || '-'}</span></td>
                <td>${record.Condicion || '-'}</td>
                <td>${record.UBIGEO || '-'}</td>
                <td>${record.Departamento || '-'}</td>
                <td>${record.Provincia || '-'}</td>
                <td>${record.Distrito || '-'}</td>
                <td>${record.direccion || '-'}</td>
                <td>${consulta.actividades_economicas || '-'}</td>
                <td>${consulta.cant_trabajadores !== undefined ? consulta.cant_trabajadores : '-'}</td>
                <td>${consulta.cant_anexos !== undefined ? consulta.cant_anexos : '-'}</td>
            `;
        } else {
            row.innerHTML = `
                <td>
                    <button class="btn btn-sm btn-info" onclick="event.stopPropagation(); showDetails(${JSON.stringify(record).replace(/"/g, '&quot;')})">
                        <i class="fas fa-eye">+</i>
                    </button>
                </td>
                <td><strong>${record.dni || '-'}</strong></td>
                <td>${record.RUC || '-'}</td>
                <td>${record.Razón_Social || '-'}</td>
                <td><span class="badge ${record.Estado === 'ACTIVO' ? 'bg-success' : 'bg-danger'}">${record.Estado || '-'}</span></td>
                <td>${record.Condicion || '-'}</td>
                <td>${record.UBIGEO || '-'}</td>
                <td>${record.Departamento || '-'}</td>
                <td>${record.Provincia || '-'}</td>
                <td>${record.Distrito || '-'}</td>
                <td>${record.direccion || '-'}</td>
            `;
        }

        return row;
    }

    function configurePagination() {
        const container = document.getElementById('paginationContainer');
        
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHTML = '<ul class="pagination justify-content-center">';
        
        // Botón anterior
        if (currentPage > 1) {
            paginationHTML += `<li class="page-item">
                <a class="page-link" href="#" onclick="goToPage(${currentPage - 1}); return false;">Anterior</a>
            </li>`;
        }

        // Páginas
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a>
            </li>`;
        }

        // Botón siguiente
        if (currentPage < totalPages) {
            paginationHTML += `<li class="page-item">
                <a class="page-link" href="#" onclick="goToPage(${currentPage + 1}); return false;">Siguiente</a>
            </li>`;
        }

        paginationHTML += '</ul>';
        container.innerHTML = paginationHTML;
    }

    window.goToPage = function(page) {
        currentPage = page;
        performSearch();
    };

    window.showDetails = function(record) {
        const content = document.getElementById('detailContent');
        
        console.log('=== SHOW DETAILS DEBUG RUC20 ===');
        console.log('Record completo:', record);
        console.log('Campos del record:', Object.keys(record));
        
        if (currentSearchType === 'ruc20') {
            const consulta = {
                fecha_consulta: record.consulta_fecha_consulta,
                nombre_razon_social: record.consulta_nombre_razon_social,
                estado_contribuyente: record.consulta_estado_contribuyente,
                condicion_contribuyente: record.consulta_condicion_contribuyente,
                actividades_economicas: record.consulta_actividades_economicas,
                cant_trabajadores: record.consulta_cant_trabajadores,
                cant_anexos: record.consulta_cant_anexos
            };

        const representantes = [];
        
        console.log('Extrayendo representantes...');

        if (Array.isArray(record.representantes) && record.representantes.length > 0) {
            console.log('Representantes provistos por backend:', record.representantes);
            representantes.push(...record.representantes.map(r => ({
                nombre: r.nombre || r.Nombre || '-',
                cargo: r.cargo || r.Cargo || '-',
                tipo_documento: r.tipo_documento || r.tipo || r.tipoDocumento || '-',
                numero_documento: r.numero_documento || r.numero || r.numeroDocumento || '',
                telefonos: r.telefonos || r.telefono || {}
            })));
        } else {
            console.log('No hay array `representantes` en el record; buscando campos rep_*');
            for (let i = 1; i <= 5; i++) {
                if (record[`rep_${i}_nombre`]) {
                    console.log(`Representante ${i} encontrado:`, record[`rep_${i}_nombre`]);
                    representantes.push({
                        nombre: record[`rep_${i}_nombre`],
                        cargo: record[`rep_${i}_cargo`],
                        tipo_documento: record[`rep_${i}_tipo_documento`],
                        numero_documento: record[`rep_${i}_numero_documento`],
                        telefonos: {
                            lista_movistar: record[`rep_${i}_lista_movistar`] || record[`rep_${i}_movistar`] || '',
                            lista_claro: record[`rep_${i}_lista_claro`] || record[`rep_${i}_claro`] || '',
                            lista_entel: record[`rep_${i}_lista_entel`] || record[`rep_${i}_entel`] || '',
                            lista_otros: record[`rep_${i}_lista_otros`] || record[`rep_${i}_otros`] || ''
                        }
                    });
                }
            }
            console.log('Representantes extraídos por patrón rep_*:', representantes);
        }

            
            content.innerHTML = `
                <h6 class="text-primary">Información General</h6>
                <table class="table table-sm table-bordered">
                    <tr><td><strong>RUC:</strong></td><td>${record.RUC || '-'}</td></tr>
                    <tr><td><strong>Razón Social:</strong></td><td>${record.Razón_Social || '-'}</td></tr>
                    <tr><td><strong>Estado:</strong></td><td><span class="badge ${record.Estado === 'ACTIVO' ? 'bg-success' : 'bg-danger'}">${record.Estado || '-'}</span></td></tr>
                    <tr><td><strong>Condición:</strong></td><td>${record.Condicion || '-'}</td></tr>
                    <tr><td><strong>Tipo:</strong></td><td>${record.Tipo || '-'}</td></tr>
                    <tr><td><strong>Trabajadores:</strong></td><td>${record.NroTrab || '-'}</td></tr>
                </table>
                
                <h6 class="text-primary mt-3">Información Empresarial</h6>
                <table class="table table-sm table-bordered">
                    <tr><td><strong>Actividad Económica:</strong></td><td>${record.Actividad_Economica_Principal || '-'}</td></tr>
                    <tr><td><strong>Motivo:</strong></td><td>${record.motivo || '-'}</td></tr>
                    <tr><td><strong>Subsegmento Agosto:</strong></td><td>${record.subsegmento_agosto || '-'}</td></tr>
                    <tr><td><strong>Ganado por:</strong></td><td>${record.ganado_por || '-'}</td></tr>
                    <tr><td><strong>Gerente:</strong></td><td>${record.gerente || '-'}</td></tr>
                    <tr><td><strong>SML:</strong></td><td>${record.s_m_l || '-'}</td></tr>
                </table>
                
                <h6 class="text-primary mt-3">Ubicación</h6>
                <table class="table table-sm table-bordered">
                    <tr><td><strong>Departamento:</strong></td><td>${record.Departamento || '-'}</td></tr>
                    <tr><td><strong>Provincia:</strong></td><td>${record.Provincia || '-'}</td></tr>
                    <tr><td><strong>Distrito:</strong></td><td>${record.Distrito || '-'}</td></tr>
                    <tr><td><strong>UBIGEO:</strong></td><td>${record.UBIGEO || '-'}</td></tr>
                    <tr><td><strong>Dirección:</strong></td><td>${record.direccion || '-'}</td></tr>
                </table>
                
                <h6 class="text-primary mt-3">Líneas Telefónicas</h6>
                <table class="table table-sm table-bordered">
                    <tr><td><strong>Movistar:</strong></td><td>${record.movistar_lines || '-'}</td></tr>
                    <tr><td><strong>Claro:</strong></td><td>${record.claro_lines || '-'}</td></tr>
                    <tr><td><strong>Entel:</strong></td><td>${record.entel_lines || '-'}</td></tr>
                    <tr><td><strong>Competencia:</strong></td><td>${record.competence_lines || '-'}</td></tr>
                </table>
                
                ${representantes && representantes.length > 0 ? `
                <h6 class="text-primary mt-3">Representantes Legales</h6>
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
                            ${representantes.map(rep => {
                                const telefonosMovistar = rep.telefonos?.lista_movistar ? rep.telefonos.lista_movistar.split(',').map(t => t.trim()).filter(t => t).join(', ') : '-';
                                const telefonosClaro = rep.telefonos?.lista_claro ? rep.telefonos.lista_claro.split(',').map(t => t.trim()).filter(t => t).join(', ') : '-';
                                const telefonosEntel = rep.telefonos?.lista_entel ? rep.telefonos.lista_entel.split(',').map(t => t.trim()).filter(t => t).join(', ') : '-';
                                const telefonosOtros = rep.telefonos?.lista_otros ? rep.telefonos.lista_otros.split(',').map(t => t.trim()).filter(t => t).join(', ') : '-';
                                
                                return `
                                <tr>
                                    <td>${rep.nombre || '-'}</td>
                                    <td>${rep.cargo || '-'}</td>
                                    <td>${rep.tipo_documento || '-'}</td>
                                    <td>${rep.numero_documento || '-'}</td>
                                    <td>
                                        <div class="small">
                                            ${telefonosMovistar !== '-' ? `<div><strong>Movistar:</strong> ${telefonosMovistar}</div>` : ''}
                                            ${telefonosClaro !== '-' ? `<div><strong>Claro:</strong> ${telefonosClaro}</div>` : ''}
                                            ${telefonosEntel !== '-' ? `<div><strong>Entel:</strong> ${telefonosEntel}</div>` : ''}
                                            ${telefonosOtros !== '-' ? `<div><strong>Otros:</strong> ${telefonosOtros}</div>` : ''}
                                            ${[telefonosMovistar, telefonosClaro, telefonosEntel, telefonosOtros].every(t => t === '-') ? '<div>-</div>' : ''}
                                        </div>
                                    </td>
                                </tr>
                            `}).join('')}
                        </tbody>
                    </table>
                </div>
                ` : ''}
                
                ${[consulta] && [consulta].length > 0 ? `
                <h6 class="text-primary mt-3">Consultas SUNAT - Última Actualización: ${consulta.fecha_consulta || '-'}</h6>
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

                            ${[consulta].map(cons => `
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
                ` : ''}
            `;
        } else {
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
            
            content.innerHTML = `
                ${record.source === 'reniec' ? `<div class="alert alert-info mb-3"><i class="fas fa-database"></i> Datos obtenidos de <strong>RENIEC</strong> como respaldo.</div>` : ''}
                
                <h6 class="text-success">Información Básica</h6>
                <table class="table table-sm table-bordered">
                    <tr><td><strong>DNI:</strong></td><td>${record.dni || '-'}</td></tr>
                    <tr><td><strong>RUC Asociado:</strong></td><td>${record.RUC || '-'}</td></tr>
                    <tr><td><strong>Razón Social:</strong></td><td>${record.Razón_Social || '-'}</td></tr>
                    <tr><td><strong>Estado:</strong></td><td><span class="badge ${record.Estado === 'ACTIVO' ? 'bg-success' : 'bg-danger'}">${record.Estado || '-'}</span></td></tr>
                    <tr><td><strong>Condición:</strong></td><td>${record.Condicion || '-'}</td></tr>
                    <tr><td><strong>Dirección:</strong></td><td>${record.direccion || '-'}</td></tr>
                </table>
                
                <h6 class="text-success mt-3">Ubicación</h6>
                <table class="table table-sm table-bordered">
                    <tr><td><strong>UBIGEO:</strong></td><td>${record.UBIGEO || '-'}</td></tr>
                    <tr><td><strong>Departamento:</strong></td><td>${record.Departamento || '-'}</td></tr>
                    <tr><td><strong>Provincia:</strong></td><td>${record.Provincia || '-'}</td></tr>
                    <tr><td><strong>Distrito:</strong></td><td>${record.Distrito || '-'}</td></tr>
                </table>
                
                    ${record.Actividad_Economica_Principal ? `
                    <h6 class="text-success mt-3">Actividad Económica</h6>
                    <p class="text-muted">${record.Actividad_Economica_Principal}</p>
                    ` : ''}
                    
                    ${record.telefonos && Object.keys(record.telefonos).length > 0 ? `
                    <h6 class="text-success mt-3">Líneas Telefónicas</h6>
                    <table class="table table-sm table-bordered">
                        <tr><td><strong>Movistar:</strong></td><td>${record.telefonos.lista_movistar || '-'}</td></tr>
                        <tr><td><strong>Claro:</strong></td><td>${record.telefonos.lista_claro || '-'}</td></tr>
                        <tr><td><strong>Entel:</strong></td><td>${record.telefonos.lista_entel || '-'}</td></tr>
                        <tr><td><strong>Otros:</strong></td><td>${record.telefonos.lista_otros || '-'}</td></tr>
                    </table>
                    ` : ''}
                    
                    ${record.reniec ? `
                    <h6 class="text-success mt-3">Datos RENIEC</h6>
                    <table class="table table-sm table-bordered">
                        <tr><td><strong>Apellido Paterno:</strong></td><td>${record.reniec.ap_pat || '-'}</td></tr>
                        <tr><td><strong>Apellido Materno:</strong></td><td>${record.reniec.ap_mat || '-'}</td></tr>
                        <tr><td><strong>Nombres:</strong></td><td>${record.reniec.nombres || '-'}</td></tr>
                        <tr><td><strong>Nombre completo:</strong></td><td>${[record.reniec.ap_pat, record.reniec.ap_mat, record.reniec.nombres].filter(Boolean).join(' ') || '-'}</td></tr>
                        <tr><td><strong>Fecha de Nacimiento:</strong></td><td>${formatFecha(record.reniec.fecha_nac)}</td></tr>
                        <tr><td><strong>Fecha de Emisión:</strong></td><td>${formatFecha(record.reniec.fch_emision)}</td></tr>
                        <tr><td><strong>Fecha de Caducidad:</strong></td><td>${formatFecha(record.reniec.fch_caducidad)}</td></tr>
                        <tr><td><strong>Sexo:</strong></td><td>${formatSexo(record.reniec.sexo)}</td></tr>
                        <tr><td><strong>Estado Civil:</strong></td><td>${record.reniec.est_civil || '-'}</td></tr>
                        <tr><td><strong>Madre:</strong></td><td>${record.reniec.madre || '-'}</td></tr>
                        <tr><td><strong>Padre:</strong></td><td>${record.reniec.padre || '-'}</td></tr>
                        <tr><td><strong>Ubigeo Dirección:</strong></td><td>${record.reniec.ubigeo_dir || '-'}</td></tr>
                        <tr><td><strong>Dirección RENIEC:</strong></td><td>${record.reniec.direccion || '-'}</td></tr>
                    </table>
                    ` : ''}
                    
${
record.consultas_sunat && record.consultas_sunat.length > 0 ? `
<h5 class="text-success mt-3 fw-bold">VINCULACIÓN LABORAL Y EMPRESARIAL</h5>
------------
<div class="row">
    ${record.consultas_sunat.map((cons, index) => {

        const rep = record.representantes?.[index];

        return `
        <div class="col-12 mb-3">
            <div class="card shadow-sm border-success">

                <!-- HEADER EMPRESA -->
                <div class="card-header bg-light">
                    <div class="row">

                        <div class="col-md-8">
                            <strong>Empresa donde figura vinculada la persona:</strong> ${cons.nombre_razon_social || '-'} - ${cons.ruc}
                        </div>

                        <div class="col-md-4 text-end">
                            <span class="badge ${cons.estado_contribuyente === 'ACTIVO' ? 'bg-success' : 'bg-danger'}">
                                ${cons.estado_contribuyente || '-'}
                            </span>
                        </div>

                    </div>
                </div>

                <div class="card-body">

                    <!-- CARGO (ENFASIS PRINCIPAL) -->
                    <h6 class="text-primary fw-bold mb-2">
                        CARGO OCUPADO POR LA PERSONA EN LA EMPRESA
                    </h6>

                    ${
                        rep ? `
                            <div class="alert alert-primary py-2 mb-3">
                                <h5 class="mb-0 fw-bold text-dark">
                                    ${rep.cargo || '-'}
                                </h5>
                            </div>
                        ` : `
                            <div class="text-muted mb-3">Sin cargo registrado</div>
                        `
                    }

                    <!-- DATA EMPRESA -->
                    <h6 class="text-secondary fw-semibold mt-3 bg-light p-2">
                        INFORMACIÓN DE LA EMPRESA DONDE FIGURA VINCULADA LA PERSONA
                    </h6>

                    <div class="mb-2">
                        <strong>Condición:</strong> ${cons.condicion_contribuyente || '-'}
                    </div>

                    <div class="mb-2">
                        <strong>Actividad económica:</strong>
                        <div>${cons.actividades_economicas || '-'}</div>
                    </div>

                    <div class="mb-2">
                        <strong>Trabajadores:</strong> ${cons.cant_trabajadores || '-'} |
                        <strong>Anexos:</strong> ${cons.cant_anexos || '-'}
                    </div>

                    <div class="mb-2">
                        <strong>Última actualización SUNAT:</strong> ${cons.fecha_consulta || '-'}
                    </div>

                </div>

            </div>
        </div>
        `;
    }).join('')}
</div>

` : ''
}
            `;
        }

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('detailModal'));
        modal.show();
    }

    // Abrir modal de exportación cuando se presione el botón grande
    document.getElementById('btnOpenExportModal').addEventListener('click', function() {
        if (!currentSearchType) {
            alert('Por favor seleccione un tipo de búsqueda (RUC 20 o RUC 10) antes de descargar.');
            return;
        }

        // Mostrar conteo total en modal
        const totalText = document.getElementById('modalTotalText');
        totalText.textContent = `de ${totalRecords.toLocaleString()} total`;
        document.getElementById('modalExportLimit').value = '';

        const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
        exportModal.show();
    });

    // Handler del botón EXPORTAR dentro del modal
    document.getElementById('modalExportBtn').addEventListener('click', function() {
        if (!currentSearchType) {
            alert('Tipo de búsqueda no seleccionado');
            return;
        }

        const btn = this;
        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generando...';

        // Obtener filtros sin paginación
        const filters = getFilters();
        delete filters.page;
        delete filters.per_page;

        const limitValRaw = document.getElementById('modalExportLimit').value.trim();
        const limitVal = limitValRaw === '' ? null : parseInt(limitValRaw);
        const batchSizeVal = parseInt(document.getElementById('modalExportBatchSize').value) || 10000;

        const payload = { filters: filters, limit: limitVal, batch_size: batchSizeVal };
        const endpoint = currentSearchType === 'ruc20' ? '/busqueda/ruc20/export' : '/busqueda/ruc10/export';

        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : document.querySelector('input[name="_token"]') ? document.querySelector('input[name="_token"]').value : '';

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'text/csv'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) throw new Error('Error en la generación del CSV');
            const disposition = response.headers.get('Content-Disposition') || '';
            let filename = '';
            const match = /filename\*=UTF-8''([^;]+)|filename="?([^";]+)"?/.exec(disposition);
            if (match) filename = decodeURIComponent(match[1] || match[2]);
            if (!filename) {
                const ts = new Date().toISOString().replace(/[:\.]/g, '_');
                filename = `${currentSearchType}_export_${ts}.csv`;
            }
            return response.blob().then(blob => ({ blob, filename }));
        })
        .then(({ blob, filename }) => {
            // Cerrar modal
            const exportModalEl = document.getElementById('exportModal');
            const exportModal = bootstrap.Modal.getInstance(exportModalEl);
            if (exportModal) exportModal.hide();

            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        })
        .catch(err => {
            console.error('Error descargando CSV:', err);
            alert('Ocurrió un error al generar la descarga. Revise los logs del servidor.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    });
});
</script>
@endsection
