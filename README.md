# Certifica - Backend

Backend del sistema **Certifica**, desarrollado con Laravel 10. Expone una API RESTful protegida con Laravel Sanctum para ser consumida por el frontend Vue 3.


## Tecnologías

| Categoría | Tecnología |
|---|---|
| Framework | Laravel 10 |
| Lenguaje | PHP 8.1+ |
| Autenticación | Laravel Sanctum (tokens Bearer) |
| ORM | Eloquent (PostgreSQL) |
| Pruebas | PHPUnit 10 |
| Análisis estático | PHPStan + Larastan (nivel 8) |
| Formato de código | Laravel Pint (preset Laravel / PSR-12) |
| Detección de código sucio | PHP Mess Detector (PHPMD) |

## Estructura del proyecto

```
app/
├── Http/
│   ├── Controllers/   # Controller
│   ├── Requests/      # Request
│   └── Resources/     # Resource
├── Models/                 # Model
└── Services/          # Service
database/
└── migrations/             # users, password_reset_tokens, failed_jobs...
routes/
└── api.php                 # POST /login (pública) · GET /user (protegida)...
tests/
├── Feature/           # Test integración desde la ruta
└── Unit/Services/     # Test unitarios del servicio
```

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Edita `.env` con los datos de conexión y las URLs del frontend.

## Variables de entorno clave

| Variable | Descripción |
|---|---|
| `APP_URL` | URL del backend (`http://localhost:8000`) |
| `FRONTEND_URL` | URL del frontend para CORS (`http://localhost:5173`) |
| `SANCTUM_STATEFUL_DOMAINS` | Dominios stateful de Sanctum (`localhost:5173`) |
| `DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Conexión PostgreSQL |

## Scripts

```bash
php artisan serve          # Servidor de desarrollo (http://localhost:8000)

composer test              # Ejecutar pruebas
composer test:coverage     # Pruebas con cobertura, necesario xdebug

composer pint              # Aplicar formato
composer pint:check        # Verificar formato sin modificar
composer phpstan           # Análisis estático
composer phpmd             # Detección de código sucio
composer quality           # pint:check + phpstan + phpmd
```

## Buenas prácticas

- Lógica de negocio en **Services**; controladores solo orquestan.
- Validación en **Form Requests** (`app/Http/Requests/`).
- Respuestas formateadas con **API Resources** (`app/Http/Resources/`).
- Declarar siempre `$fillable` o `$hidden` en los modelos.
- Ejecutar `composer quality` antes de cada commit.

## Licencia

Software propiedad del organizacion.

