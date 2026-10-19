# CINEsesitasVerlas

Aplicación web para gestionar la cartelera de funciones de un cine: registrar,
editar y eliminar funciones, buscar por título, género o sala y navegar por los
resultados con un paginador. Incluye un caso aplicativo de Ajax para importar
datos de películas desde una API externa, sin recargar la página.

## Requisitos

- XAMPP con **Apache** y **MySQL** (PHP 7.4 o superior).
- Conexión a internet (para la búsqueda Ajax en TVMaze).

## Instalación

1. Copiar la carpeta `CINEsesitasVerlas` dentro de `htdocs` de XAMPP.
2. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
3. Abrir <http://localhost/CINEsesitasVerlas>

La base de datos `cinesitas` y la tabla `funciones` se crean automáticamente la
primera vez que se abre el sistema, junto con datos de ejemplo (16 funciones).

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
| **CRUD** (crear, listar, editar, eliminar) | `index.php`, `formulario.php`, `acciones.php` |
| **Buscador** por título, género o sala | `index.php` (formulario con `?q=`) |
| **Paginador** de 6 funciones por página | `index.php` (`?pagina=N`, conserva la búsqueda) |
| **Caso aplicativo de Ajax** | Sección «Importar datos desde TVMaze» en `formulario.php` + `assets/js/cartelera.js` |

## Estructura

```
CINEsesitasVerlas/
├── index.php            # Cartelera: buscador, paginador y acciones del CRUD
├── formulario.php       # Alta y edición de funciones (incluye el importador Ajax)
├── acciones.php         # Procesa guardar y eliminar
├── config/
│   ├── base.php         # Conexión, instalación automática y datos de ejemplo
│   ├── db.php           # Datos de conexión por defecto (XAMPP)
│   └── db.local.php     # (opcional, no se sube) contraseña real de MySQL
├── plantilla/
│   ├── cabecera.php     # Encabezado y menú
│   └── pie.php          # Pie de página
├── assets/
│   ├── css/estilo.css   # Diseño marquesina, responsive
│   ├── js/cartelera.js  # Consulta Ajax a TVMaze
│   └── img/logo.svg     # Logo del sistema
├── datos.sql            # Esquema y datos para phpMyAdmin
└── README.md            # Esta documentación
```

## API utilizada

- **TVMaze**: <https://api.tvmaze.com/search/shows?q=…> — no requiere clave.
  El navegador la consulta con `fetch()` y los resultados se copian al
  formulario con un clic.

## Paleta de colores

| Uso | Color |
|---|---|
| Fondo | `#0D0B14` |
| Superficies | `#16131F` / `#1E1A2B` |
| Acento (ámbar de marquesina) | `#FFB703` |
| Alertas y peligro | `#E63946` |

## Licencia

MIT — ver [LICENSE](LICENSE).
