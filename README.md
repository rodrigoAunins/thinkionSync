# thinkionSync
# Thinkion Sync — Integración API de Reportes

Sistema ETL (Extract, Transform, Load) para sincronizar datos desde la API de reportes de Thinkion hacia una base de datos MySQL preexistente.

## 📋 Requisitos

- PHP 8.2+
- Composer 2.x
- MySQL 8.x (producción) / SQLite (desarrollo)
- Laravel 13.x

## 🚀 Instalación

```bash
# 1. Instalar dependencias
composer install

# 2. Copiar y configurar el archivo de entorno
cp .env.example .env
php artisan key:generate

# 3. Configurar las variables de Thinkion en .env (ver sección siguiente)

# 4. Ejecutar migraciones (tablas de sync — NO altera la tabla ventas en producción)
php artisan migrate
```

## ⚙️ Configuración (.env)

### Variables de la API de Thinkion

| Variable | Descripción | Ejemplo |
|---|---|---|
| `THINKION_CLIENT_CODE` | Código de cliente Thinkion | `tem9` |
| `THINKION_API_TOKEN` | Token de autenticación (header X-Server-Token) | `REDACTED_API_TOKEN` |
| `THINKION_API_TIMEOUT` | Timeout HTTP en segundos | `60` |
| `THINKION_API_RETRIES` | Intentos de retry ante errores de servidor | `3` |
| `THINKION_API_RETRY_SLEEP_MS` | Pausa entre retries en milisegundos | `1000` |
| `THINKION_MAX_DAYS_PER_REQUEST` | Máximo días por request (límite del API: 30) | `30` |

### Variables de Sincronización

| Variable | Descripción | Ejemplo |
|---|---|---|
| `THINKION_DEFAULT_REPORT_IDS` | IDs de reportes a sincronizar (separados por coma) | `233` |
| `THINKION_DEFAULT_ESTABLISHMENTS` | IDs de establecimientos (separados por coma) | `1,2` |
| `THINKION_SYNC_DAYS_BACK` | Días hacia atrás para sync diario | `7` |

### Variables de Logging

| Variable | Descripción | Default |
|---|---|---|
| `THINKION_LOG_REQUESTS` | Loggear requests HTTP | `true` |
| `THINKION_LOG_RESPONSES` | Loggear responses HTTP (puede ser verbose) | `false` |

### Base de Datos

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crm_database
DB_USERNAME=root
DB_PASSWORD=secret
```

### 🔧 Comandos Artisan

El sistema provee una interfaz CLI robusta para la gestión de los datos. Se recomienda el uso de **Recursos Humanos** (`--resource`) para la operación diaria.

#### 1. Sincronización Manual (`thinkion:sync`)
Ejecuta la extracción y carga de datos desde la API.

| Opción | Descripción | Ejemplo |
|---|---|---|
| `--resource` | **Recomendado**. Filtra por tipo de entidad. | `transaction`, `sales`, `products` |
| `--report` | Nivel bajo. Usa el ID numérico de Thinkion. | `1`, `233`, `234` |
| `--start` / `--end` | Rango de fechas (Y-m-d). | `--start=2024-01-01` |

**Ejemplos:**
```bash
# Sincronizar transacciones de la última semana
php artisan thinkion:sync --resource=transactions

# Sincronizar un reporte específico por ID
php artisan thinkion:sync --report=1 --start=2024-01-01
```

#### 2. Auditoría de Procesos (`thinkion:audit`)
Visualiza el historial de ejecuciones persistido en la capa de PostgreSQL.

```bash
# Ver las últimas 10 ejecuciones
php artisan thinkion:audit

# Filtrar auditoría por tipo de recurso
php artisan thinkion:audit --resource=sales
```

#### 3. Diagnóstico de Conectividad (`thinkion:test-db`)
Verifica la salud de las conexiones Dual-DB (MySQL y Postgres).
mente
php artisan thinkion:test-connection
php artisan thinkion:test-connection --report=233 --establishments=1,2

## ⏰ Cron (Scheduling)

El sistema está configurado para ejecutar la sincronización diaria a las 02:00 AM.

Para activar el scheduler, agregar al crontab del servidor:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🏗️ Arquitectura

```
app/
├── Console/Commands/
│   ├── ThinkionSyncCommand.php          # Sync manual con parámetros
│   ├── ThinkionSyncDailyCommand.php     # Sync diario automático
│   └── ThinkionTestConnectionCommand.php # Test de conectividad
├── Enums/
│   ├── ReportType.php                   # transaction, sales, products, generic
│   └── SyncStatus.php                   # pending, running, completed, failed
├── Exceptions/
│   └── ThinkionApiException.php         # Exception específica del API
├── Models/
│   ├── Venta.php                        # Modelo de tabla ventas (legacy)
│   ├── ThinkionRawReport.php            # Datos crudos de staging
│   └── SyncRun.php                      # Tracking de ejecuciones
├── Providers/
│   └── ThinkionServiceProvider.php      # Inyección de dependencias
├── Repositories/
│   ├── Contracts/
│   │   └── DomainRepositoryInterface.php
│   ├── Domain/
│   │   └── VentaRepository.php          # Upsert en tabla ventas
│   └── Raw/
│       ├── RawReportRepository.php      # Staging de datos crudos
│       └── SyncRunRepository.php        # Tracking de sync runs
└── Services/Thinkion/
    ├── ApiClient.php                    # Cliente HTTP (token + paginación + retry)
    ├── Contracts/
    │   └── ReportMapperInterface.php    # Contrato para mappers
    ├── Mappers/
    │   ├── GenericReportMapper.php      # Mapper genérico (raw)
    │   └── VentasReportMapper.php       # Mapper para tabla ventas
    ├── Reports/
    │   ├── ReportDefinition.php         # DTO de definición de reporte
    │   └── ReportRegistry.php           # Registro de reportes configurados
    ├── Support/
    │   ├── DateRangeChunker.php         # Divide rangos en chunks ≤30 días
    │   └── SyncLogger.php              # Logger dedicado
    └── Sync/
        ├── SyncService.php              # Motor ETL principal
        └── SyncOrchestrator.php         # Coordinador de múltiples reportes
```

## 📊 Flujo de Datos

1. **Comando Artisan** → inicia el proceso
2. **SyncOrchestrator** → selecciona reportes a sincronizar
3. **SyncService** → divide fechas en chunks ≤30 días
4. **ApiClient** → consume la API de Thinkion (POST con X-Server-Token)

## 🛠️ Arquitectura y Decisiones Técnicas

### 1. Autenticación (X-Server-Token)
> [!NOTE]
> La API temporal de Thinkion documentada para este desarrollo utiliza el esquema de **X-Server-Token** enviado en el Header HTTP.
> Aunque el requerimiento menciona un login dinámico y refresco de tokens, la versión actual de la API no expone dichos endpoints. Se implementó una arquitectura lista para inyectar un `TokenProvider` dinámico en el futuro, pero actualmente se consume el token estático desde el `.env` para alinearse con la documentación técnica oficial de la API de reportes.

### 2. Capa de Auditoría y Control (Auditoría SQL)
El sistema no solo genera logs de texto, sino que implementa una **capa de persistencia de auditoría** en PostgreSQL:
- **`thinkion_sync_runs`**: Registro histórico de cada ejecución, incluyendo fecha de inicio, fin, resultados (insertados/actualizados) y mensajes de error detallados.
- **`thinkion_raw_reports` (Staging)**: Almacén de los datos crudos (JSON completo) recibidos de la API antes de ser mapeados. Esto permite auditorías forenses y reprocesamiento de datos sin volver a llamar a la API.

### 3. Modularidad y Extensibilidad
El sistema está diseñado bajo el principio de Responsabilidad Única:
- **Mappers**: Transforman el JSON de la API al esquema de la base de datos (ej: `VentasReportMapper`).
- **Repositories**: Encapsulan la lógica de persistencia e idempotencia (`updateOrCreate`).
- **Orquestador**: Gestiona el flujo ETL, chunks de fecha y paginación.

5. **Paginación automática** → itera page tokens
6. **Raw Storage** → guarda datos crudos en `thinkion_raw_reports`
7. **Mapper** → transforma datos API → estructura de tabla destino
8. **Repository** → upsert (updateOrCreate) en tabla de dominio (ej: `ventas`)
9. **SyncRun** → registra métricas en `thinkion_sync_runs`

## 📝 Logs

Los logs se guardan en `storage/logs/thinkion.log` con rotación diaria (60 días de retención).

```bash
# Ver logs en tiempo real
tail -f storage/logs/thinkion-*.log
```

## 🧪 Tests

```bash
# Ejecutar toda la suite de tests
php artisan test

# Solo tests de Thinkion
php artisan test --filter=Thinkion

# Tests específicos
php artisan test --filter=ApiClientTest
php artisan test --filter=IdempotencyTest
php artisan test --filter=SyncServiceTest
```

## ➕ Agregar un Nuevo Reporte

Para integrar un nuevo reporte de Thinkion:

### 1. Crear el Mapper

```php
// app/Services/Thinkion/Mappers/ProductosReportMapper.php
class ProductosReportMapper implements ReportMapperInterface
{
    public function map(array $row, array $context = []): ?array
    {
        return [
            'product_id' => $row['id'],
            'name' => $row['nombre'],
            'price' => $row['precio'],
            // ... mapear campos
        ];
    }
}
```

### 2. Crear el Repository (si es una tabla nueva)

```php
class ProductoRepository implements DomainRepositoryInterface
{
    public function upsert(array $data): Model
    {
        return Producto::updateOrCreate(
            ['product_id' => $data['product_id']],
            $data
        );
    }
}
```

### 3. Registrar en ReportRegistry

```php
// En ReportRegistry::registerReports()
$this->add(new ReportDefinition(
    reportId: 234,
    name: 'productos',
    description: 'Catálogo de productos',
    type: ReportType::PRODUCTS,
    mapperClass: ProductosReportMapper::class,
    repositoryClass: ProductoRepository::class,
));
```

### 4. Agregar el ID al .env

```env
THINKION_DEFAULT_REPORT_IDS=233,234
```

## 📄 Tablas de Base de Datos

### Tablas propias del ETL (creadas por migración)

- `thinkion_sync_runs` — Tracking de cada ejecución de sincronización
- `thinkion_raw_reports` — Datos crudos del API (staging)

### Tablas existentes (NO se modifican)

- `ventas` — Tabla legacy del CRM

