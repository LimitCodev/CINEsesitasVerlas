# 📀 MANUAL DE INSTALACIÓN — CINEsesitasVerlas

Guía para poner el buscador a funcionar en tu computadora en **5 minutos**.
No necesitas saber programar: solo seguir los pasos.

---

## 1. Lo que necesitas

| Qué | Para qué | Dónde se consigue |
|---|---|---|
| **XAMPP** | Trae PHP y MySQL ya instalados y listos | <https://www.apachefriends.org> (gratis) |
| **Un navegador** | Chrome, Firefox o Edge | Ya lo tienes |
| **Internet** | Las películas se buscan en línea | Tu conexión normal |

No hay que instalar librerías, frameworks ni dependencias extra.

---

## 2. Instalación paso a paso

### Paso 1 — Instala XAMPP

1. Entra a <https://www.apachefriends.org>.
2. Descarga la versión de tu sistema (Windows, Mac o Linux).
3. Instálalo con las opciones por defecto: *siguiente, siguiente, finalizar*.

### Paso 2 — Copia la carpeta del proyecto

Copia la carpeta `CINEsesitasVerlas` dentro de la carpeta `htdocs` de XAMPP:

| Sistema | Ruta |
|---|---|
| Windows | `C:\xampp\htdocs\CINEsesitasVerlas` |
| Mac | `/Applications/XAMPP/htdocs/CINEsesitasVerlas` |
| Linux | `/opt/lampp/htdocs/CINEsesitasVerlas` |

> En Mac y Linux las mayúsculas importan: respeta el nombre
> `CINEsesitasVerlas` tal cual.

### Paso 3 — Enciende Apache y MySQL

1. Abre el **XAMPP Control Panel**.
2. Pulsa **Start** junto a **Apache**.
3. Pulsa **Start** junto a **MySQL**.

Los dos deben quedarse en verde. Si Apache dice «port 80 in use», mira la
tabla de problemas de abajo.

### Paso 4 — Abre el buscador

En la barra de direcciones de tu navegador escribe:

```
http://localhost/CINEsesitasVerlas/
```

**Eso es todo.** La base de datos `cinesitas` se crea **sola** en la primera
carga: no hay que escribir SQL ni importar archivos.

### Paso 5 — Comprueba que funciona

1. Escribe `dune` u `oppenheimer` en el buscador y pulsa **Buscar**.
2. Deben aparecer películas con su cartel, año y resumen.
3. Pulsa **▶ Ver tráiler**: debe abrirse el vídeo en pantalla completa.
4. Pulsa **Guardar en mi cartelera**: la película aparece abajo, en
   «Mi cartelera», con botones de editar y eliminar.

---

## 3. Opcional: carteles y tráileres oficiales (TMDB)

**Sin clave el sistema ya funciona**: usa Wikipedia y Wikidata (datos reales,
miniaturas y tráileres cuando existen). Con una clave gratuita de TMDB verás
además los **pósteres y tráileres oficiales** de cada película.

1. Crea una cuenta gratis en <https://www.themoviedb.org>.
2. Pide tu clave en <https://www.themoviedb.org/settings/api> (es gratuita).
3. Crea el archivo `config/api.local.php` dentro del proyecto y pega:

```php
<?php
define('TMDB_API_KEY', 'tu_clave_aqui');
```

4. Recarga la página: arriba debe aparecer **MODO TMDB CON CLAVE**.

> Si ese archivo no existe, no pasa nada: el buscador sigue funcionando en
> modo Wikipedia. La clave **nunca** se sube al repositorio (el archivo está
> en el `.gitignore`).

---

## 4. Si algo falla

| Lo que ves en pantalla | Causa probable | Solución |
|---|---|---|
| «No se pudo conectar con MySQL…» | MySQL no está encendido | Abre XAMPP y pulsa **Start** en MySQL |
| Página no encontrada (404) | La carpeta no está en `htdocs` o está mal escrita la URL | Revisa el Paso 2; la dirección es `localhost/CINEsesitasVerlas` |
| Apache no arranca (puerto 80 ocupado) | Otro programa (IIS, Skype, MySQL de otro servidor) usa el puerto 80 | Cierra ese programa o cambia el puerto en Apache → *Config* → `httpd.conf` |
| «Wikipedia limita las consultas: espera X s» | Normal: Wikipedia deja de responder unos segundos | Espera los segundos indicados y repite; una búsqueda repetida sale al instante (caché) |
| Los carteles no cargan | Sin conexión a internet | Conéctate a la red |
| Tu MySQL pide otra contraseña o puerto | No es el MySQL de XAMPP por defecto | Crea `config/db.local.php` con tus datos (ver «Si MySQL tiene contraseña» en el `README.md`) |
| Todo en blanco | Error de PHP | Mira la consola del navegador (F12) o el log de Apache en `xampp/apache/logs` |

---

## 5. No hay datos de ejemplo (y es a propósito)

La cartelera arranca **vacía**. Las películas entran únicamente desde el
buscador, con datos reales de Wikipedia, Wikidata o TMDB. La tabla
`cache_externa` se llena sola para responder al instante las búsquedas
repetidas.

---

## 6. Qué se usa por dentro (para explicar la entrega)

- **PHP + MySQL** sobre XAMPP, con **HTML/CSS/JS puro** (sin frameworks), diseño
  responsive y paleta de marquesina propia.
- **CRUD** completo: guardar, ver, editar y eliminar
  (`formulario.php`, `acciones.php`, sección «Mi cartelera» en `index.php`).
- **Buscador** de la cartelera por título, género o estado y **paginador** de
  6 películas por página.
- **Caso aplicativo de Ajax**: la búsqueda de películas, la reproducción del
  tráiler en modal y el guardado en la cartelera ocurren **sin recargar la
  página** (`assets/js/buscador.js` → `api/pelicula.php`).
- **Servicios gratuitos**: Wikipedia (español), Wikidata (tráiler P1651) y,
  opcionalmente, TMDB (pósteres y tráileres oficiales).

Más detalle técnico (estructura de archivos, APIs y paleta) en `README.md`.

---

**Licencia MIT** — libre para usar, copiar y modificar.
