Resumen de cambios realizados para migración y corrección del módulo de búsqueda

Objetivo
- Migrar la lógica de búsquedas que funciona en Flask hacia Laravel/PHP conservando todas las funcionalidades: búsqueda individual (RUC20, DNI/RUC10), búsquedas masivas, export CSV, sugerencias y estadísticas.

Cambios aplicados
1) Seguridad de credenciales
- Removí valores por defecto sensibles en `config/database.php` para la conexión `mysql_flask`.
- Añadí variables `FLASK_DB_*` en `.env` (placeholders). Asegúrate de llenar `FLASK_DB_PASSWORD` en el `.env` real y mantenerlo fuera de VCS.

2) Robustez ante diferencias en el esquema
- `RUC10` y `RUC20` (Modelos) ahora detectan si la tabla tiene `fuente_datos` o `source`. Añadí un accessor `getFuenteDatosAttribute()` y scopes `byFuente` que usan la columna existente cuando esté disponible.
- `RUC10Service` y `RUC20Service` adaptados para detectar si la columna `fuente_datos`/`source` existe antes de realizar conteos por `reniec`/`sunat`.
- En sugerencias se evita seleccionar directamente `fuente_datos` para prevenir errores cuando la columna no exista.

3) Frontend JS
- Reemplacé llamadas `fetch('{{ route(...) }}')` por rutas relativas (`/busqueda/ruc20`, `/busqueda/dni`, `/busqueda/ruc20/masivo`, etc.) para evitar problemas de esquema/host que producían errores 404 (http vs https).

4) Limpieza de caché
- Ejecute: `php artisan config:clear`, `route:clear`, `cache:clear`, `view:clear` y `php artisan route:cache`.

Qué probar manualmente (pasos)
1) Preparar `.env` (en `relevant/.env`): completar variables `FLASK_DB_HOST`, `FLASK_DB_PORT`, `FLASK_DB_DATABASE`, `FLASK_DB_USERNAME`, `FLASK_DB_PASSWORD`.
2) Limpiar caches y cachear rutas (en `relevant`):
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
php artisan route:cache
```
3) Acceder en navegador como usuario autenticado (el módulo `busqueda` está protegido por middleware):
- Visitar `/busqueda` → comprobar que la página carga y muestra estadísticas.
- Probar búsqueda individual RUC20: `/busqueda/ruc20` (rellenar RUC y buscar).
- Probar búsqueda individual DNI: `/busqueda/dni`.
- Probar búsqueda masiva: `/busqueda/ruc20/masivo` y `/busqueda/ruc10/masivo`.
- Exportar CSV desde la interfaz masiva.

Notas sobre errores previos
- El error SQL "Unknown column 'fuente_datos'" se debía a que el código asumía la existencia de la columna. Ahora el código detecta `fuente_datos` o `source` antes de usarla.
- El POST a `/busqueda/ruc20` que devolvía 404 parecía causado por llamadas fetch que usaban rutas absolutas con un esquema/host distinto; cambié a rutas relativas para evitar eso.

Siguientes pasos recomendados
- Verificar conexión a la BD `mysql_flask` con las credenciales reales y probar las consultas.
- Ejecutar pruebas manuales de carga en masivos (export) en un entorno de staging para validar rendimiento.
- Documentar y automatizar la rotación/almacenamiento seguro de credenciales (Vault, secrets manager).

Archivos modificados (resumen)
- `relevant/config/database.php` — quitar valores por defecto sensibles
- `relevant/.env` — placeholders `FLASK_DB_*` añadidos
- `relevant/app/Models/RUC10.php` — detección `fuente_datos`/`source`, accessor
- `relevant/app/Services/RUC10Service.php` — detección de columna y evitar selects problemáticos
- `relevant/app/Models/RUC20.php` — detección `fuente_datos`/`source`, accessor
- `relevant/app/Services/RUC20Service.php` — detección de columna y estadísticas
- `relevant/resources/views/busqueda/*.blade.php` — rutas JS cambiadas a relativas

Si quieres, puedo:
- Ejecutar pruebas HTTP simuladas (requiere credenciales y/o sesión autenticada);
- Añadir pruebas unitarias o integradas para los services (usar DB de pruebas);
- Generar un playbook de despliegue detallado para producción.
