# Certificación Digital FT-UMSA

Sistema de actividades académicas, nóminas, plantillas, emisión y verificación de certificados de la Facultad de Tecnología.

## Guía para el equipo que instalará el sistema

Esta instalación parte de una **base de datos nueva y vacía**. Ejecuta los comandos desde la carpeta raíz del proyecto, donde está el archivo `artisan`.

Solicita a Cómputo la IP o el dominio de acceso, una base MySQL y sus credenciales, y la configuración del servicio web. El servidor necesita PHP 8.3 o superior con las extensiones requeridas por Composer, además de `pdo_mysql`, `gd` y `zip`, Composer 2 y MySQL 8. Para compilar el frontend se requiere Node.js 22.12 o superior y npm.

### 1. Subir el proyecto al espacio asignado

Clona el repositorio en la carpeta asignada del servidor o copia allí los archivos de la versión entregada.

```bash
git clone https://github.com/Isrrael-Sirpa/sistema-certificados.git
cd sistema-certificados
```

### 2. Preparar la base de datos y el archivo de configuración

Crea una base MySQL vacía desde el panel o administrador de bases de datos del servidor. Si cuentas con una sesión MySQL con permisos de creación, puedes ejecutar lo siguiente.

```sql
CREATE DATABASE sistema_certificados CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Copia `.env.example` a `.env` y edítalo con los datos entregados por Cómputo. En Linux usa `cp .env.example .env`. En PowerShell usa `Copy-Item .env.example .env`.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://certificados.tu-institucion.bo
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_certificados
DB_USERNAME=usuario_de_la_aplicacion
DB_PASSWORD=tu_clave_de_base_de_datos
SESSION_SECURE_COOKIE=true
```

Reemplaza los valores de ejemplo. `APP_URL` es la dirección que abrirán las personas en el navegador. `DB_HOST` es la dirección del servidor MySQL, que puede ser diferente de la dirección del sitio.

Para pruebas iniciales mediante una IP con HTTP, usa `APP_URL=http://IP_ASIGNADA` y `SESSION_SECURE_COOKIE=false`. Al habilitar HTTPS, configura la dirección definitiva y cambia la cookie a `true`. Define esa dirección definitiva **antes de emitir certificados reales**, porque sus QR contienen enlaces al sistema.

### 3. Instalar y preparar el backend

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
php artisan key:generate --force --no-interaction
php artisan config:clear --no-interaction
```

La generación de la clave corresponde a esta instalación nueva. Conserva esa clave en las actualizaciones posteriores.

### 4. Ejecutar las migraciones y la carga inicial

```bash
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
php artisan storage:link --no-interaction
php artisan migrate:status --no-interaction
```

Las migraciones crean las tablas y sus relaciones. El último comando debe mostrar todas las migraciones como ejecutadas.

Con `APP_ENV=production`, la carga inicial incorpora las **nueve carreras, Cómputo y Sistemas, Decanato, Vicedecanato, roles, plantillas y configuración institucional**. La base comienza con **cero usuarios, beneficiarios y actividades**.

### 5. Crear el primer superadministrador

Ejecuta este comando en una terminal interactiva, usando el correo real de la persona responsable de Cómputo.

```bash
php artisan certificates:create-admin administrador@tu-institucion.bo
```

El comando solicita el nombre y una contraseña oculta. Después, Cómputo ingresa al sistema y crea las cuentas de jefatura y secretaría, vinculándolas con su carrera o departamento.

### 6. Preparar el frontend

```bash
npm ci --include=dev
npm run build
```

La compilación genera `public/build/manifest.json` y los archivos CSS y JavaScript. Puede ejecutarse en el servidor o en una máquina de compilación y copiar después `public/build` completo junto con la misma versión del código.

### 7. Publicar y comprobar el sistema

Entrega al administrador del servidor estos datos para configurar el sitio.

- La IP o el dominio debe dirigir al espacio donde está instalado el proyecto.
- La raíz web debe apuntar a la carpeta **`public/`** del proyecto.
- Laravel debe ejecutarse mediante PHP-FPM con Nginx o el servicio PHP equivalente del servidor.
- El proceso web necesita permisos de escritura en `storage/` y `bootstrap/cache/`.
- El sitio debe tener HTTPS para su uso con datos reales.

Una vez configurado el sitio, ejecuta lo siguiente y pide a Cómputo activar o recargar el servicio web.

```bash
php artisan optimize --no-interaction
```

El servicio web procesa el backend de Laravel y entrega los archivos compilados del frontend desde la misma IP o dominio. Los comandos específicos para activar ese servicio dependen del servidor que les asignen.

Comprueba el resultado en el navegador.

| Comprobación | Resultado esperado |
|---|---|
| Abrir `/up` | El servicio responde correctamente. |
| Abrir `/login` | Aparece el formulario con estilos. |
| Entrar como superadministrador | Permite crear usuarios y asignar unidades. |
| Entrar con jefatura y secretaría | Aparecen las opciones de sus roles y los datos de su unidad. |
| Crear un evento y cargar una nómina de prueba | Se aplican carrera, nota mínima y confirmación manual cuando corresponde. |
| Emitir un certificado de prueba | Se genera el PDF y el QR abre la verificación en la dirección del servidor. |

## Trabajo local con backend y frontend separados

Esta sección permite desarrollar o probar cambios en tu computadora.

1. Instala las dependencias con `composer install` y `npm ci`.
2. Configura una base local y `.env` con `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://127.0.0.1:8000` y `SESSION_SECURE_COOKIE=false`.
3. En una instalación local nueva ejecuta `php artisan key:generate --no-interaction`, `php artisan config:clear --no-interaction`, `php artisan migrate --no-interaction`, `php artisan db:seed --no-interaction` y `php artisan storage:link --no-interaction`. En desarrollo, el seeder también crea registros y cuentas de demostración.
4. Abre dos terminales en la carpeta del proyecto.

**Terminal del backend**

```bash
php artisan serve --host=127.0.0.1 --port=8000 --tries=1
```

**Terminal del frontend**

```bash
npm run dev -- --host 127.0.0.1 --port 5173 --strictPort
```

Abre la aplicación en `http://127.0.0.1:8000`. Mantén ambas terminales abiertas y detén cada proceso con `Ctrl+C`. El puerto de Vite entrega los estilos y JavaScript durante el desarrollo. En PowerShell puedes usar `npm.cmd` si se bloquea `npm`.

El lanzador `iniciar_proyecto.bat` ofrece las mismas tareas. Elige **1 — Backend** en una terminal y **2 — Frontend** en otra. La opción **3** compila el frontend. `composer run dev` inicia únicamente el backend en este proyecto.

Para ejecutar las pruebas, usa los siguientes comandos. Las pruebas de Laravel usan SQLite en memoria y requieren Node.js.

```bash
php artisan test --compact
node --test tests/Unit/CertificateStudioTest.mjs
```

## Actualizar una instalación existente

Respalda la base de datos y `storage/app`. Sube la versión nueva, conserva `.env` y su `APP_KEY`, y ejecuta lo siguiente.

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci --include=dev
npm run build
php artisan migrate --force --no-interaction
php artisan optimize --no-interaction
```

La carga inicial de datos corresponde a la primera instalación. Comprueba los flujos principales tras activar la versión.

## Si algo falla al iniciar

- **Conexión MySQL rechazada.** Comprueba el servicio MySQL, la base y las credenciales de `.env`. Tras corregirlas, ejecuta `php artisan config:clear --no-interaction`.
- **El inicio de sesión no conserva la sesión.** Revisa que `SESSION_SECURE_COOKIE` corresponda a HTTP o HTTPS y que `APP_URL` tenga la dirección correcta.
- **Faltan estilos o el manifiesto de Vite.** Ejecuta la instalación y compilación del frontend. Comprueba que `public/build` esté desplegado.
- **El sitio intenta acceder al Vite de desarrollo.** Si Vite está detenido y quedó el archivo temporal `public/hot`, elimina ese archivo concreto y recompila. En PowerShell usa `Remove-Item -LiteralPath public/hot` y en Linux `rm public/hot`.
- **Puerto local ocupado.** Detén la ejecución anterior o usa un puerto libre y ajusta las direcciones correspondientes.
- **Permisos de escritura.** Pide al administrador del servidor que revise el acceso del proceso PHP a `storage/` y `bootstrap/cache/`.

Las credenciales se configuran en el servidor y `.env` está excluido de Git. El equipo administrador debe conservar los respaldos de la base, del almacenamiento y de la clave de la aplicación. [Despliegue de Laravel](https://laravel.com/docs/13.x/deployment), [compilación con Vite](https://laravel.com/docs/13.x/vite).
