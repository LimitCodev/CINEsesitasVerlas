# CINEsesitasVerlas

Aplicación web para buscar películas reales, ver su tráiler oficial y armar una
cartelera personal. El buscador consulta APIs públicas con Ajax (sin recargar la
página) y la cartelera se administra con CRUD, buscador y paginador.

## Requisitos

- XAMPP con **Apache** y **MySQL** (PHP 7.4 o superior con extensión `cURL`).
- Conexión a internet (las APIs externas se consultan desde el servidor).

## Instalación

1. Copiar la carpeta `CINEsesitasVerlas` dentro de `htdocs` de XAMPP.
2. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
3. Abrir <http://localhost/CINEsesitasVerlas>

La base de datos `cinesitas` y la tabla `peliculas` se crean automáticamente la
primera vez. La cartelera inicia **vacía a propósito**: las películas se agregan
desde el buscador con datos reales, no con datos de ejemplo.

### Importación manual con phpMyAdmin (opcional)

1. Abrir <http://localhost/phpmyadmin>
2. Pestaña **Importar** → elegir el archivo `datos.sql` → Continuar.

### Si MySQL tiene contraseña

Crear el archivo `config/db.local.php` (está excluido del repositorio):

```php
<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', 'tu_contraseña');
```

## Funcionalidades

| Requisito | Dónde está |
|---|---|
| **CRUD** (crear, listar, editar, eliminar) | `index.php` (sección «Mi cartelera»), `formulario.php`, `acciones.php` |
| **Buscador** de la cartelera por título, género o estado | `index.php` (formulario con `?q=`) |
| **Paginador** de 6 películas por página | `index.php` (`?pagina=N`, conserva la búsqueda) |
| **Caso aplicativo de Ajax** | Buscador de películas (`api/pelicula.php`), tráiler en modal y guardado en la cartelera (`assets/js/buscador.js`) |

### Cómo usar el buscador

1. Escribir un título (ej. `doom`, `dune`, `oppenheimer`) y tocar **Buscar**.
2. Cada resultado muestra año, resumen y, si existe, su **tráiler oficial**.
3. Tocar **▶ Ver tráiler** para reproducirlo en un modal (YouTube sin cookies de seguimiento).
4. Tocar **Guardar en mi cartelera** para agregarla con sus datos reales.

## Estructura

```
CINEsesitasVerlas/
├── index.php            # Buscador (Ajax) + Mi cartelera (CRUD, buscador, paginador)
├── formulario.php       # Alta manual y edición de películas
├── acciones.php         # Guardar / eliminar / guardar por Ajax
├── api/
│   └── pelicula.php     # Búsqueda de películas y tráileres (Wikipedia/Wikidata o TMDB)
├── config/
│   ├── base.php         # Conexión, helpers e instalación automática
│   ├── db.php           # Datos de conexión por defecto (XAMPP)
│   ├── api.php          # Clave de TMDB (vacía por defecto)
│   ├── db.local.php     # (opcional, no se sube) contraseña real de MySQL
│   └── api.local.php    # (opcional, no se sube) clave real de TMDB
├── plantilla/
│   ├── cabecera.php     # Encabezado y menú
│   └── pie.php          # Pie de página
├── assets/
│   ├── css/estilo.css   # Diseño marquesina, responsive
│   ├── js/buscador.js   # Los casos de Ajax (búsqueda, tráiler, guardado)
│   └── img/logo.svg     # Logo del sistema
├── datos.sql            # Esquema para phpMyAdmin
└── README.md            # Esta documentación
```

## APIs y servicios utilizados

| Servicio | Uso | Clave |
|---|---|---|
| Wikipedia (español) | Búsqueda y resumen de cada película | No requiere |
| Wikidata | Tráiler oficial (propiedad P1651, YouTube) | No requiere |
| TMDB (opcional) | Búsqueda mejorada y pósteres oficiales | Gratuita (ver abajo) |

El sistema funciona **sin ninguna clave**: usa Wikipedia y Wikidata. Con una
clave gratuita de TMDB se mejoran los resultados con pósteres oficiales.

### Clave de TMDB (opcional)

1. Crear cuenta en <https://www.themoviedb.org> y pedir una clave en
   <https://www.themoviedb.org/settings/api> (es gratuita).
2. Crear el archivo `config/api.local.php`:

```php
<?php
define('TMDB_API_KEY', 'tu_clave_aqui');
```

> El archivo `config/api.local.php` está en el `.gitignore`: la clave real
> nunca se sube al repositorio.

## Paleta de colores

| Uso | Color |
|---|---|
| Fondo | `#0D0B14` |
| Superficies | `#16131F` / `#1E1A2B` |
| Acento (ámbar de marquesina) | `#FFB703` |
| Alertas y peligro | `#E63946` |

## Licencia

MIT — ver [LICENSE](LICENSE).
