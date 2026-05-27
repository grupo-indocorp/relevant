@extends('layouts.app')

@section('title', 'Sistema de Búsqueda - RUC y DNI')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Encabezado -->
            <div class="text-center mb-5">
                <h1 class="display-4 font-weight-bold text-dark">
                    <i class="fas fa-magnifying-glass text-primary"></i>
                    Sistema de Búsqueda
                </h1>
                <p class="lead text-muted">
                    Consulta información de RUCs, DNIs y datos comerciales en nuestra base de datos
                </p>
            </div>

            <!-- Contenedor de Opciones -->
            <div class="row">
                <!-- Opción 1: Búsqueda RUC 20 Individual -->
                @can('busqueda.view.ruc20.individual')
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-lg hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-search-plus fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Buscar RUC 20</h5>
                            <p class="card-text text-muted">
                                Búsqueda individual de empresas por número de RUC tipo 20 (11 dígitos)
                            </p>
                            <a href="{{ route('busqueda.ruc20') }}" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-search"></i> Buscar RUC 20
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

                <!-- Opción 2: Búsqueda DNI Individual -->
                @can('busqueda.view.ruc10.individual')
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-lg hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-id-card fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">Buscar DNI</h5>
                            <p class="card-text text-muted">
                                Búsqueda individual de contribuyentes por número de DNI (8 dígitos)
                            </p>
                            <a href="{{ route('busqueda.dni') }}" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-search"></i> Buscar DNI
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

                <!-- Opción 3: Búsqueda Masiva -->
                @canany(['busqueda.view.ruc20.masivo', 'busqueda.view.ruc10.masivo'])
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-lg hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-table fa-3x text-warning"></i>
                            </div>
                            <h5 class="card-title">Búsqueda Masiva</h5>
                            <p class="card-text text-muted">
                                Consulta masiva de múltiples registros con filtros y paginación
                            </p>
                            <a href="{{ route('busqueda.masivo') }}" class="btn btn-warning btn-lg w-100">
                                <i class="fas fa-database"></i> Búsqueda Masiva
                            </a>
                        </div>
                    </div>
                </div>
                @endcanany
            </div>

            <!-- Estadísticas en tiempo real -->
            <div class="row mt-5">
                @can('busqueda.view.ruc20.stats')
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-building"></i> Estadísticas RUC 20
                        </div>
                        <div class="card-body" id="ruc20-stats">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endcan

                @can('busqueda.view.ruc10.stats')
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-user"></i> Estadísticas DNI/RUC 10
                        </div>
                        <div class="card-body" id="ruc10-stats">
                            <div class="text-center">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endcan
            </div>

            <!-- Información Adicional -->
<div class="row mt-5">
    <div class="col-md-12">
        <div class="alert alert-dismissible fade show shadow"
             role="alert"
             style="background:#f8d7da; color:#842029; border:1px solid #f5c2c7;">

            <i class="fas fa-exclamation-triangle"></i>

            <strong>Advertencia de Riesgo:</strong>
            La información mostrada en esta plataforma proviene de consultas realizadas a la base de datos SUNAT y puede estar sujeta a actualizaciones, modificaciones o restricciones externas.
            El uso indebido, manipulación o interpretación incorrecta de los datos es responsabilidad exclusiva del usuario.
            Verifique siempre la información antes de utilizarla para fines legales, comerciales o tributarios.

            <br><br>
            © LBL - ILenTech - GRUPO ETHERNALBLUE

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
</div>
        </div>
    </div>
</div>

<style>
.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.2) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cargar estadísticas RUC 20
    fetch('{{ route("api.busqueda.ruc20.stats") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById('ruc20-stats').innerHTML = `
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="text-primary">${stats.total_registros.toLocaleString()}</h4>
                            <small class="text-muted">Total Registros</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-success">${stats.activos.toLocaleString()}</h4>
                            <small class="text-muted">Activos</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-danger">${stats.inactivos.toLocaleString()}</h4>
                            <small class="text-muted">Inactivos</small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: ${stats.porcentaje_activos}%">
                                ${stats.porcentaje_activos}% Activos
                            </div>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error cargando estadísticas RUC 20:', error);
            document.getElementById('ruc20-stats').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error al cargar estadísticas
                </div>
            `;
        });

    // Cargar estadísticas RUC 10
    fetch('{{ route("api.busqueda.ruc10.stats") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById('ruc10-stats').innerHTML = `
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="text-primary">${stats.total_registros.toLocaleString()}</h4>
                            <small class="text-muted">Total Registros</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-info">${stats.datos_reniec.toLocaleString()}</h4>
                            <small class="text-muted">Datos RENIEC</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-warning">${stats.datos_sunat.toLocaleString()}</h4>
                            <small class="text-muted">Datos SUNAT</small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" style="width: ${stats.porcentaje_reniec}%">
                                ${stats.porcentaje_reniec}% RENIEC
                            </div>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: ${stats.porcentaje_sunat}%">
                                ${stats.porcentaje_sunat}% SUNAT
                            </div>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error cargando estadísticas RUC 10:', error);
            document.getElementById('ruc10-stats').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error al cargar estadísticas
                </div>
            `;
        });
});
</script>
@endsection
