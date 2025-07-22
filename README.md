# Instalación y Levantamiento del Proyecto Laravel

Este documento proporciona instrucciones detalladas sobre cómo instalar y levantar un proyecto Laravel en tu entorno local.

## Requisitos Previos

> Recomendamos usar [Herd](https://herd.laravel.com/windows), proporcionado por Laravel. Si no cuenta con la versión pro, puede descargar un gestor de Base de Datos de su preferencia. Por sugerencia puede usar `HeidiSQL`.

Pero si no quiere seguir ninguna de nuestras opciones, asegúrate de tener instalados los siguientes requisitos en tu máquina:

- PHP >= 8.2.4
- Composer
- Laravel Installer
- MySQL o cualquier otro sistema de gestión de bases de datos compatible
- Node.js y npm

## Pasos de Instalación


**Clonar el Repositorio:**

```bash
git clone git@github.com:grupo-indocorp/relevant.git
cd relevant
```

*Si el proyecto se levanta usando XAMP:*
Habilitar en `php.ini`
```bash
extension=gd
extension=zip
```

**Copiar el Archivo de deploy/index.cpanel:**
Es necesario actualizar `.cpanel.yml`

```bash
DEPLOYPATH=/home/servidor/directorio/
```

**Copiar el Archivo de deploy/index.cpanel:**
Cambiar el nombre de `project`

```bash
cp ./deploy/index.cpanel.example.php ./deploy/index.cpanel.php
```

**Instalar Dependencias de PHP:**

```bash
composer install
```

**Copiar el Archivo de Configuración:**

```bash
cp .env.example .env
```

**Generar la Clave de la Aplicación:**

```bash
php artisan key:generate
```

**Configurar la Base de Datos:**

Abre el archivo `.env` en un editor de texto y configura las variables de entorno relacionadas con la base de datos (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD).

**Migrar y Sembrar la Base de Datos:**

```bash
php artisan migrate --seed
```

**Instalar Dependencias de Node.js:**

```bash
npm install
```

**Compilar Assets:**

```bash
# desarrollo
npm run dev

# producción
npm run build 
```

**Levantamiento del Servidor**

Una vez que has completado la instalación, puedes levantar el servidor de desarrollo de Laravel utilizando el siguiente comando:

```bash
php artisan serve
```

La aplicación estará disponible en http://localhost:8000 por defecto.

**Levantamiento del Servidor con HERD**

Puedes usar la ruta de [indotech.test](indotech.test) y para que carguen los assets de node, debes configurar el artico `vite.config.js`
```js
export default defineConfig({
  server: {
    host: 'relevant.test',
  },
  ...
});
```

**Agregar datos en la Base de Datos usando Tinker**
Puedes revisar [aqui](./README-TINKER.md), para agregar los datos que necesites en la Base de Datos

¡Listo! Has instalado y levantado exitosamente tu proyecto Laravel. Asegúrate de consultar la documentación oficial de Laravel para obtener información adicional sobre el desarrollo de la aplicación: [Documentación Laravel](https://laravel.com/docs/10.x).