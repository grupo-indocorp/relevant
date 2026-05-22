# Migración del Sistema de Búsqueda RUC/DNI de Flask a Laravel

## Resumen

Se ha completado la migración del sistema de búsqueda Flask (Python) al sistema Laravel existente, permitiendo que ambos sistemas funcionen de forma integrada pero con bases de datos separadas.

## Componentes Migrados

### 1. Base de Datos
- **Configuración**: Se agregó conexión `mysql_flask` en `config/database.php`
- **Base de datos externa**: `BASE_GENERAL_NUEVA` en servidor `68.178.135.232`
- **Tablas**: 
  - `ruc20_febrero28` (empresas - ~2M registros)
  - `ruc10_febrero28` (personas - ~15M registros)

### 2. Modelos Laravel
- **RUC20.php**: Modelo para empresas con conexión a base de datos Flask
- **RUC10.php**: Modelo para personas con conexión a base de datos Flask
- **Características**: Scopes para búsqueda, relaciones, y atributos calculados

### 3. Servicios de Negocio
- **RUC20Service.php**: Lógica de negocio para búsqueda de empresas
- **RUC10Service.php**: Lógica de negocio para búsqueda de personas
- **Funcionalidades**: Búsqueda individual, masiva, exportación CSV, estadísticas

### 4. Controlador
- **BusquedaController.php**: Controlador principal con todos los endpoints
- **Métodos**: Búsqueda RUC20, DNI, búsqueda masiva, exportación, estadísticas

### 5. Rutas y Middleware
- **Rutas web**: `/busqueda/*` con protección de permisos
- **Rutas API**: `/api/busqueda/*` para AJAX
- **Middleware**: `CheckBusquedaPermission` para validación de accesos

### 6. Vistas Blade
- **busqueda/index.blade.php**: Página principal con 3 opciones
- **busqueda/search_ruc20.blade.php**: Búsqueda individual RUC20
- **busqueda/search_dni.blade.php**: Búsqueda individual DNI
- **busqueda/search_massive.blade.php**: Búsqueda masiva con filtros

### 7. Sistema de Permisos
- **BusquedaPermissionSeeder**: Seeder para crear permisos
- **Permisos definidos**:
  - `busqueda.access`: Acceso general al módulo
  - `busqueda.ruc20.individual`: Búsqueda individual RUC20
  - `busqueda.ruc20.masivo`: Búsqueda masiva RUC20
  - `busqueda.ruc20.export`: Exportación RUC20
  - `busqueda.ruc10.individual`: Búsqueda individual DNI/RUC10
  - `busqueda.ruc10.masivo`: Búsqueda masiva DNI/RUC10
  - `busqueda.ruc10.export`: Exportación DNI/RUC10
  - `busqueda.admin`: Permisos administrativos

### 8. Integración con Sistema Existente
- **BusquedaMenuProvider**: Provider para integrar menú dinámicamente
- **Sidebar actualizado**: Enlace al sistema de búsqueda con icono diferenciado
- **Configuración**: Provider registrado en `config/app.php`

## Instalación y Configuración

### 1. Ejecutar Seeder de Permisos
```bash
php artisan db:seed --class=BusquedaPermissionSeeder
```

### 2. Limpiar Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 3. Verificar Conexión
```bash
php artisan tinker
>>> App\Models\RUC20::count();
>>> App\Models\RUC10::count();
```

## Estructura de Rutas

### Rutas Principales
- `GET /busqueda` - Página principal del sistema de búsqueda
- `GET /busqueda/ruc20` - Formulario búsqueda RUC20
- `POST /busqueda/ruc20` - Procesar búsqueda RUC20
- `GET /busqueda/ruc20/masivo` - Búsqueda masiva RUC20
- `POST /busqueda/ruc20/export` - Exportar CSV RUC20
- `GET /busqueda/dni` - Formulario búsqueda DNI
- `POST /busqueda/dni` - Procesar búsqueda DNI
- `GET /busqueda/ruc10/masivo` - Búsqueda masiva RUC10
- `POST /busqueda/ruc10/export` - Exportar CSV RUC10

### Rutas API (AJAX)
- `GET /api/busqueda/ruc20/suggestions` - Autocompletado RUC20
- `GET /api/busqueda/ruc10/suggestions` - Autocompletado RUC10
- `GET /api/busqueda/ruc20/stats` - Estadísticas RUC20
- `GET /api/busqueda/ruc10/stats` - Estadísticas RUC10

## Configuración de Base de Datos

### Variables de Entorno Necesarias
Agregar al archivo `.env`:
```env
# Conexión a base de datos Flask
FLASK_DB_HOST=68.178.135.232
FLASK_DB_PORT=3306
FLASK_DB_DATABASE=BASE_GENERAL_NUEVA
FLASK_DB_USERNAME=userBDN
FLASK_DB_PASSWORD=]3hk+jl]IOvt
```

## Características Implementadas

### ✅ Funcionalidades Completas
- Búsqueda individual de RUC20 (empresas)
- Búsqueda individual de DNI/RUC10 (personas)
- Búsqueda masiva con filtros avanzados
- Exportación a CSV con streaming
- Estadísticas en tiempo real
- Autocompletado/sugerencias
- Paginación de resultados
- Validación de formularios
- Manejo de errores
- Interfaz responsive

### ✅ Seguridad y Permisos
- Middleware de autenticación
- Sistema de permisos granular
- Protección CSRF
- Validación de entrada
- Control de acceso por rol

### ✅ Integración
- Menú dinámico integrado
- Iconos diferenciados (azul para búsqueda)
- Compatible con diseño existente
- Sin interferencia con módulos actuales

## Pruebas Recomendadas

### 1. Pruebas Funcionales
- Acceder al menú principal y verificar enlace de búsqueda
- Probar búsqueda individual RUC20 con datos válidos
- Probar búsqueda individual DNI con datos válidos
- Probar búsqueda masiva con filtros
- Probar exportación de CSV
- Verificar permisos por rol

### 2. Pruebas de Rendimiento
- Consultas con grandes volúmenes de datos
- Tiempos de respuesta en búsquedas complejas
- Consumo de memoria en exportaciones grandes

### 3. Pruebas de Integración
- Verificar que el menú principal funciona
- Comprobar que no afecta otros módulos
- Validar que los permisos funcionan correctamente

## Mantenimiento

### Monitoreo
- Revisar logs de errores de búsqueda
- Monitorear rendimiento de consultas
- Verificar conexión a base de datos externa

### Actualizaciones
- Mantener sincronizados los modelos con estructura de tablas
- Actualizar permisos según nuevos requerimientos
- Optimizar consultas para mejor rendimiento

## Notas Importantes

1. **Base de Datos Externa**: El sistema se conecta a una base de datos externa, no se modifica la estructura original
2. **Independencia**: El sistema Flask puede continuar funcionando paralelamente
3. **Seguridad**: Todas las rutas están protegidas por autenticación y permisos
4. **Rendimiento**: Se implementó streaming para exportaciones grandes
5. **Compatibilidad**: Totalmente compatible con el sistema Laravel existente

## Soporte

Para problemas o consultas sobre la migración:
- Verificar conexión a base de datos Flask
- Revisar configuración de permisos
- Validar que los seeders se ejecutaron correctamente
- Revisar logs de Laravel para errores
