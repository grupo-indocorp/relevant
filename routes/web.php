<?php

use App\Http\Controllers\ClienteConsultorController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ClienteGestionController;
use App\Http\Controllers\ConfiguracionCategoriaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ConfiguracionEstadoFacturaController;
use App\Http\Controllers\ConfiguracionEtapaController;
use App\Http\Controllers\ConfiguracionExcelController;
use App\Http\Controllers\ConfiguracionFichaClienteController;
use App\Http\Controllers\ConfiguracionProductoController;
use App\Http\Controllers\ConfiguracionSistemaController;
use App\Http\Controllers\CuentafinancieraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FileViewController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\FunnelController;
use App\Http\Controllers\GestionClienteController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ListaUsuarioController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ReporteClienteController;
use App\Http\Controllers\ReporteClienteNuevoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UbigeoController;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\BusquedaController;

// Ruta para la página de componentes
Route::get('/components', function () {
    return view('components');
});

// Ruta de inicio
Route::get('/', function () {
    return redirect('/login');
});
Route::get('/logout', function () {
    return redirect('/login');
});
Route::get('/register', function () {
    return redirect('/login');
});

// Rutas protegidas por autenticación
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Rutas de recursos
    Route::resources([
        'lista_usuario' => ListaUsuarioController::class,
        'equipo' => EquipoController::class,
        'funnel' => FunnelController::class,
        'role' => RoleController::class,
        'notificacion' => NotificacionController::class,
        'reporte' => ReporteController::class,
        'reporte_cliente' => ReporteClienteController::class,
        'reporte_cliente_nuevo' => ReporteClienteNuevoController::class,
        'producto' => ProductoController::class,
        'cliente' => ClienteController::class,
        'cliente-consultor' => ClienteConsultorController::class,
        'cliente-gestion' => ClienteGestionController::class,
        'cuentas-financieras' => CuentafinancieraController::class,
        'facturas' => FacturaController::class,
        'configuracion' => ConfiguracionController::class,
        'configuracion-sistema' => ConfiguracionSistemaController::class,
        'configuracion-etapa' => ConfiguracionEtapaController::class,
        'configuracion-categoria' => ConfiguracionCategoriaController::class,
        'configuracion-producto' => ConfiguracionProductoController::class,
        'configuracion-excel' => ConfiguracionExcelController::class,
        'configuracion-ficha-cliente' => ConfiguracionFichaClienteController::class,
        'configuracion-estado-factura' => ConfiguracionEstadoFacturaController::class,
        'files' => FileController::class,
        'files-view' => FileViewController::class,
    ]);

    Route::get('/api/provincias/{departamento}', [UbigeoController::class, 'provincias']);
    Route::get('/api/distritos/{departamento}/{provincia}', [UbigeoController::class, 'distritos']);

    // Ruta adicional para la descarga de archivos
    Route::get('/files/{id}/download', [FileController::class, 'download'])->name('files.download');

    // Ruta para la visualización de archivos
    Route::get('/documentos', [FileViewController::class, 'index'])->name('files.view');

    // Exportación e importación de clientes
    Route::get('clientes/export/', [GestionClienteController::class, 'export']);
    Route::post('clientes/import/', [GestionClienteController::class, 'import'])->name('import.cliente');

    // Actualización de datos de clientes
    Route::get('upcf', [ConfiguracionController::class, 'updateCuentaFinanciera'])->name('update.cuentafinanciera');
    Route::get('upf', [ConfiguracionController::class, 'updateFactura'])->name('update.factura');

    // Exportación de datos
    Route::get('export/secodi/funnel', [ExportController::class, 'secodiFunnel']);
    Route::get('export/indotech/funnel', [ExportController::class, 'indotechFunnel']);

    // Importación de datos
    Route::post('import/evaporacion', [ImportController::class, 'evaporacion'])->name('import.evaporacion');

    // Archivos
    Route::resource('files', FileController::class);

    // Carpetas
    Route::prefix('folders')->group(function () {
        Route::get('create', [FolderController::class, 'create'])->name('folders.create');
        Route::post('', [FolderController::class, 'store'])->name('folders.store');
        Route::delete('{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');
    });

    // routes/web.php

  

    Route::post('/contactos', [ContactoController::class, 'store'])->name('contactos.store');

    // Sistema de Búsqueda RUC/DNI
    Route::prefix('busqueda')->name('busqueda.')->middleware(['busqueda.permission:busqueda.access'])->group(function () {
        // Página principal del sistema de búsqueda
        Route::get('/', [BusquedaController::class, 'index'])->name('index');

        // Página de búsqueda masiva con opciones para RUC 10 y RUC 20
        Route::get('/masivo', [BusquedaController::class, 'buscarMasivo'])->name('masivo');

        // Búsqueda RUC 20
        Route::get('/ruc20', [BusquedaController::class, 'buscarRUC20'])->name('ruc20')->middleware('busqueda.permission:busqueda.ruc20.individual');
        Route::post('/ruc20', [BusquedaController::class, 'buscarRUC20'])->name('ruc20.search')->middleware('busqueda.permission:busqueda.ruc20.individual');
        Route::get('/ruc20/masivo', [BusquedaController::class, 'buscarRUC20Masivo'])->name('ruc20.masivo')->middleware('busqueda.permission:busqueda.ruc20.masivo');
        Route::post('/ruc20/export', [BusquedaController::class, 'exportRUC20CSV'])->name('ruc20.export')->middleware('busqueda.permission:busqueda.ruc20.export');

        // Búsqueda DNI/RUC 10
        Route::get('/dni', [BusquedaController::class, 'buscarDNI'])->name('dni')->middleware('busqueda.permission:busqueda.ruc10.individual');
        Route::post('/dni', [BusquedaController::class, 'buscarDNI'])->name('dni.search')->middleware('busqueda.permission:busqueda.ruc10.individual');
        Route::get('/ruc10/masivo', [BusquedaController::class, 'buscarRUC10Masivo'])->name('ruc10.masivo')->middleware('busqueda.permission:busqueda.ruc10.masivo');
        Route::post('/ruc10/export', [BusquedaController::class, 'exportRUC10CSV'])->name('ruc10.export')->middleware('busqueda.permission:busqueda.ruc10.export');
    });

    // API endpoints para búsquedas (AJAX)
    Route::prefix('api/busqueda')->name('api.busqueda.')->middleware(['busqueda.permission:busqueda.access'])->group(function () {
        Route::get('/ruc20/suggestions', [BusquedaController::class, 'getRUC20Suggestions'])->name('ruc20.suggestions');
        Route::get('/ruc10/suggestions', [BusquedaController::class, 'getRUC10Suggestions'])->name('ruc10.suggestions');
        Route::get('/ruc20/stats', [BusquedaController::class, 'getRUC20Stats'])->name('ruc20.stats')->middleware('busqueda.permission:busqueda.ruc20.stats');
        Route::get('/ruc10/stats', [BusquedaController::class, 'getRUC10Stats'])->name('ruc10.stats')->middleware('busqueda.permission:busqueda.ruc10.stats');
        
        // API de filtros
        Route::get('/filter-options/ruc20/{column}', [BusquedaController::class, 'getRUC20FilterOptions'])->name('ruc20.filter_options');
        Route::get('/filter-options/ruc10/{column}', [BusquedaController::class, 'getRUC10FilterOptions'])->name('ruc10.filter_options');
        Route::get('/filter-options/{search_type}/{column}', [BusquedaController::class, 'getFilterOptions'])->name('filter_options');
        Route::get('/{search_type}/actividades-economicas', [BusquedaController::class, 'getActividadEconomicaOptions'])->name('actividades_economicas');

        // API de ubicaciones
        Route::get('/locations/{search_type}/provincias/{departamento}', [BusquedaController::class, 'getProvinciasByDepartamento'])->name('provincias');
        Route::get('/locations/{search_type}/distritos/{departamento}/{provincia}', [BusquedaController::class, 'getDistritosByProvincia'])->name('distritos');
    });
});

Livewire::setScriptRoute(function ($handle) {
    return Route::get('/indotech/vendor/livewire/livewire/dist/livewire.js', $handle);
});
