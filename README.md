# Oh!Sansi Backend (Laravel 11)

Este es el backend del sistema **Oh!Sansi**, desarrollado en **Laravel 11** y usando **PostgreSQL** como base de datos.

---

## 📦 Instalación de dependencias

Cada vez que se clona el repositorio, instalar las dependencias con:

```bash
composer install
```

---

## ⚙️ Configuración del entorno

1. Copia el archivo `.env.example` a `.env`:
    ```bash
    cp .env.example .env
    ```
2. Configura las variables de entorno en `.env` según tu entorno local. Ejemplo para PostgreSQL:

    ```env
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=ohsansi
    DB_USERNAME=tu_usuario
    DB_PASSWORD=tu_contraseña
    ```

⚠️ **Importante:** La base de datos debe existir antes de correr las migraciones.

3. Genera la clave de la aplicación:
    ```bash
    php artisan key:generate
    ```

---

## 🗄️ Migraciones y modelos

### Ejecutar migraciones

Cada vez que se clona el repositorio, crear las nuevas tablas en la base de datos (solo después de configurar `.env`) ejecutando las migraciones con:

```bash
php artisan migrate
```

### Crear una nueva migración

Ejemplo para crear una tabla `usuarios`:

```bash
php artisan make:migration create_usuarios_table
```

### Crear un modelo

Ejemplo para el modelo `Usuario`:

```bash
php artisan make:model Usuario
```

### Crear un controlador

Ejemplo para un controlador `UsuarioController`:

```bash
php artisan make:controller UsuarioController
```

---

## 🔗 Relaciones entre entidades

### Ejemplo: Usuarios y Roles (1:N)

#### Migración de `usuarios` (fragmento)

```php
Schema::create('usuarios', function (Blueprint $table) {
    $table->id();
    $table->string('nombre');
    $table->foreignId('rol_id')->constrained('roles');
    $table->timestamps();
});
```

#### Modelo `Usuario.php`

```php
class Usuario extends Model
{
    public function rol()
    {
        return $this->belongsTo(Rol::class);
    }
}
```

#### Modelo `Rol.php`

```php
class Rol extends Model
{
    public function usuarios()
    {
        return $this->hasMany(Usuario::class);
    }
}
```

---

## 🌐 Rutas

Las rutas del backend están en `routes/web.php` o `routes/api.php`.

Ejemplo en `routes/api.php`:

```php
use App\Http\Controllers\UsuarioController;

Route::post('/usuarios', [UsuarioController::class, 'store']);
```

---

## ▶️ Correr el servidor

Ejecuta:

```bash
php artisan serve
```

El backend estará disponible en:

```
http://127.0.0.1:8000
```

---

## 📌 Notas

-   Siempre crear una rama `feature/...` para nuevas funcionalidades.
-   Instalar dependencias adicionales con:
    ```bash
    composer require vendor/package
    ```
-   Para refrescar las migraciones (⚠️ borra los datos):
    ```bash
    php artisan migrate:fresh
    ```
-   Mantener el código en inglés.
---
