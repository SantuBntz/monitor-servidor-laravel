# Monitor de Servidor con Laravel

Trabajo práctico de **Programación IV** · Cohorte 2026 · UTN Facultad Regional Resistencia

Aplicación en Laravel que lee datos del sistema operativo **Windows** (memoria, CPU, disco, uptime, temperatura) y los muestra en una vista web y en una API JSON.

## Qué hace

- **Vista web** en `/servidor`: 8 tarjetas (equipo, sistema, PHP, disco, memoria, CPU, uptime y temperatura) que se actualizan solas cada 5 segundos.
- **API JSON** en `/api/metrics`: devuelve los mismos datos en formato JSON.
- Ambas salidas usan el mismo service, `ServerMetrics`, que consulta a Windows mediante PowerShell (`Get-CimInstance`).

## Requisitos

- Windows (el service usa PowerShell y WMI)
- PHP 8.2 o superior, con `shell_exec` habilitado
- Composer

## Instalación y ejecución

```powershell
git clone https://github.com/SantuBntz/monitor-servidor-laravel.git
cd monitor-servidor-laravel
composer install
copy .env.example .env
php artisan key:generate
```

En el archivo `.env`, usar archivos en lugar de base de datos (el proyecto no usa base de datos):

```env
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Iniciar el servidor:

```powershell
php artisan config:clear
php artisan serve
```

Abrir en el navegador:

- Vista web: http://127.0.0.1:8000/servidor
- API: http://127.0.0.1:8000/api/metrics

## Estructura principal

| Archivo | Función |
|---|---|
| `app/Services/ServerMetrics.php` | Lee el sistema operativo y arma los datos |
| `app/Http/Controllers/ServerController.php` | Decide el formato de salida (vista o JSON) |
| `routes/web.php` | Ruta `/servidor` |
| `routes/api.php` | Ruta `/api/metrics` |
| `resources/views/servidor.blade.php` | Vista con las tarjetas y el polling |

## Los 5 pasos del trabajo

### 1. Crear el proyecto
Se creó un proyecto Laravel nuevo con Composer (`composer create-project laravel/laravel monitor-servidor`). Se configuró el `.env` con sesiones y caché en archivos, porque el proyecto no usa base de datos.

### 2. Service
`app/Services/ServerMetrics.php` lanza con `shell_exec()` un script fijo de PowerShell que ejecuta `Get-CimInstance`. Windows responde con los datos por WMI, el script los devuelve en JSON y PHP los convierte en un array con `json_decode()`. El método `snapshot()` devuelve los datos listos para usar.

### 3. Vista web
La ruta `/servidor` muestra la vista Blade `servidor.blade.php` con las 8 tarjetas. El controlador recibe el service por inyección de dependencias: Laravel lo crea y se lo pasa declarándolo como parámetro (`index(ServerMetrics $metrics)`).

### 4. API JSON
La ruta `/api/metrics` (definida en `routes/api.php`) devuelve los mismos datos en JSON. Usa el mismo service que la vista web: el controlador solo cambia el formato de la respuesta.

### 5. Polling
La vista llama con `fetch` a `/api/metrics` cada 5 segundos (`setInterval`) y actualiza el contenido de las tarjetas sin recargar la página. Si una petición falla, se conservan los últimos datos mostrados.

## Notas

- **Temperatura:** en Windows suele figurar como "No disponible". Requiere permisos de administrador y que la placa la informe; es un comportamiento esperado.
- **Seguridad:** `shell_exec()` ejecuta comandos en el servidor, por eso el script de PowerShell es fijo y nunca se arma con datos del usuario.
- **API:** expone datos de infraestructura. En producción debería protegerse con `auth:sanctum` y un límite de pedidos (`throttle`).
- **Polling:** cada visitante genera un pedido cada 5 segundos aunque los datos no cambien. El próximo trabajo práctico lo reemplaza por WebSockets con Laravel Reverb y Echo.

## Autor

Santiago Benitez
