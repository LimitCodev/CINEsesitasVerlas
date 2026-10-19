<?php
require_once 'config/base.php';
$conexion = instalar_base();

/* ================= Mi cartelera: búsqueda y paginación ================= */
$busqueda = trim($_GET['q'] ?? '');
$pagina = (int) ($_GET['pagina'] ?? 1);
$porPagina = 6;

if ($busqueda === '') {
    $consulta = $conexion->query('SELECT COUNT(*) AS total FROM peliculas');
    $fila = $consulta->fetch_assoc();
    $totalPeliculas = (int) $fila['total'];
} else {
    $como = '%' . $busqueda . '%';
    $sentencia = $conexion->prepare(
        'SELECT COUNT(*) AS total FROM peliculas WHERE titulo LIKE ? OR genero LIKE ? OR estado LIKE ?'
    );
    $sentencia->bind_param('sss', $como, $como, $como);
    $sentencia->execute();
    $fila = $sentencia->get_result()->fetch_assoc();
    $totalPeliculas = (int) $fila['total'];
}

$totalPaginas = max(1, (int) ceil($totalPeliculas / $porPagina));
if ($pagina < 1) {
    $pagina = 1;
}
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}
$desde = ($pagina - 1) * $porPagina;

if ($busqueda === '') {
    $sentencia = $conexion->prepare(
        "SELECT * FROM peliculas ORDER BY id DESC LIMIT $desde, $porPagina"
    );
    $sentencia->execute();
} else {
    $sentencia = $conexion->prepare(
        "SELECT * FROM peliculas WHERE titulo LIKE ? OR genero LIKE ? OR estado LIKE ?
         ORDER BY id DESC LIMIT $desde, $porPagina"
    );
    $sentencia->bind_param('sss', $como, $como, $como);
    $sentencia->execute();
}
$peliculas = [];
$resultado = $sentencia->get_result();
while ($fila = $resultado->fetch_assoc()) {
    $peliculas[] = $fila;
}

/* Genera el enlace de una página conservando la búsqueda */
function enlace_pagina($n)
{
    global $busqueda;
    $parametros = ['pagina' => $n];
    if ($busqueda !== '') {
        $parametros['q'] = $busqueda;
    }
    return 'index.php?' . http_build_query($parametros);
}

/** Devuelve el identificador de tráiler de YouTube de una película guardada. */
function trailer_de($pelicula)
{
    if ($pelicula['trailer'] !== '') {
        return $pelicula['trailer'];
    }
    if ($pelicula['fuente'] === 'tmdb' && $pelicula['enlace'] !== '') {
        if (preg_match('#themoviedb\.org/movie/(\d+)#', $pelicula['enlace'], $m)) {
            return 'tmdb:' . $m[1];
        }
    }
    return '';
}

$mensaje = $_GET['ok'] ?? '';
$titulo_pagina = 'Buscador';
include 'plantilla/cabecera.php';
?>
  <!-- Buscador de películas: los resultados llegan por Ajax, sin recargar -->
  <section class="hero">
    <p class="modo-activo" id="etiqueta-modo">
      <?php if (TMDB_API_KEY === ''): ?>
        <span class="modo-punto"></span> Modo demo sin clave · datos reales de Wikipedia + Wikidata
      <?php else: ?>
        <span class="modo-punto"></span> Modo TMDB · búsqueda, pósteres y tráileres oficiales
      <?php endif; ?>
    </p>
    <h1>Busca una película y mira su tráiler</h1>
    <p class="hero-texto">
      Escribe un título (ej. «doom», «dune», «oppenheimer») y el sistema lo busca
      con datos reales de Wikipedia y Wikidata. Si configuras una clave gratuita
      de TMDB, además verás los pósteres oficiales.
    </p>
    <form class="buscador-grande" id="form-buscar">
      <input id="entrada-buscar" type="search" maxlength="80" autocomplete="off"
             placeholder="Ej. Doom, Dune, Intensamente…">
      <button class="boton boton-primario" type="submit">Buscar</button>
    </form>
    <p class="buscador-pista">Los resultados se cargan con Ajax, sin recargar la página.</p>
  </section>

  <section class="bloque">
    <div class="bloque-titulo">
      <h2>Resultados de búsqueda</h2>
      <span class="cuenta-verificadas" id="cuenta-resultados"></span>
    </div>
    <div class="rejilla" id="caja-resultados">
      <p class="vacio">Escribe un título arriba y toca «Buscar» para ver las películas encontradas.</p>
    </div>
  </section>

  <!-- Reproductor de tráiler (se rellena con Ajax) -->
  <div class="modal-trailer" id="modal-trailer" hidden>
    <div class="modal-trailer-caja">
      <div class="modal-trailer-barra">
        <strong id="modal-trailer-titulo">Tráiler</strong>
        <button class="boton boton-chico boton-secundario" id="btn-cerrar-trailer" type="button">Cerrar ✕</button>
      </div>
      <div class="modal-trailer-video">
        <iframe id="iframe-trailer" src="" title="Tráiler de la película"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
      </div>
    </div>
  </div>

  <!-- ================= Mi cartelera (CRUD) ================= -->
  <section class="bloque">
    <div class="bloque-titulo">
      <h2>Mi cartelera</h2>
      <div class="bloque-acciones">
        <span class="cuenta-verificadas">
          <?php if ($busqueda !== ''): ?>
            <?= $totalPeliculas ?> resultado(s) para «<?= e($busqueda) ?>»
          <?php else: ?>
            <?= $totalPeliculas ?> película(s) guardada(s)
          <?php endif; ?>
        </span>
        <a class="boton boton-secundario boton-chico" href="formulario.php">+ Agregar a mano</a>
      </div>
    </div>

    <?php if ($mensaje === 'guardado'): ?>
      <div class="aviso aviso-correcto">Película agregada a tu cartelera.</div>
    <?php elseif ($mensaje === 'actualizado'): ?>
      <div class="aviso aviso-correcto">Película actualizada correctamente.</div>
    <?php elseif ($mensaje === 'eliminado'): ?>
      <div class="aviso aviso-correcto">Película eliminada de tu cartelera.</div>
    <?php elseif ($mensaje === 'error'): ?>
      <div class="aviso aviso-error">Falta el título de la película.</div>
    <?php endif; ?>

    <!-- Buscador de la cartelera: filtra por título, género o estado -->
    <form class="buscador" method="get" action="index.php">
      <input class="buscador-entrada" type="search" name="q" maxlength="80"
             placeholder="Buscar en mi cartelera…" value="<?= e($busqueda) ?>">
      <button class="boton boton-primario" type="submit">Buscar</button>
      <?php if ($busqueda !== ''): ?>
        <a class="boton boton-secundario" href="index.php">Limpiar</a>
      <?php endif; ?>
    </form>

    <div class="rejilla">
      <?php if (empty($peliculas)): ?>
        <p class="vacio">
          <?php if ($busqueda !== ''): ?>
            No se encontraron películas para «<?= e($busqueda) ?>».
          <?php else: ?>
            Tu cartelera está vacía. Busca una película arriba y toca «Guardar»
            para agregarla con sus datos reales.
          <?php endif; ?>
        </p>
      <?php endif; ?>

      <?php foreach ($peliculas as $p): ?>
        <article class="tarjeta">
          <div class="tarjeta-poster">
            <?php if ($p['poster'] !== ''): ?>
              <img src="<?= e($p['poster']) ?>" alt="Póster de <?= e($p['titulo']) ?>" loading="lazy"
                   onerror="this.outerHTML='<span class=&quot;poster-inicial&quot;><?= e(mb_strtoupper(mb_substr($p['titulo'], 0, 1))) ?></span>'">
            <?php else: ?>
              <span class="poster-inicial"><?= e(mb_strtoupper(mb_substr($p['titulo'], 0, 1))) ?></span>
            <?php endif; ?>
            <span class="tarjeta-genero estado-<?= e($p['estado']) ?>"><?= e($p['estado']) ?></span>
          </div>
          <div class="tarjeta-cuerpo">
            <h2><?= e($p['titulo']) ?></h2>
            <ul class="tarjeta-datos">
              <li><span>Año</span> <?= $p['anio'] !== '' ? e($p['anio']) : '—' ?></li>
              <li><span>Fuente</span> <?= $p['fuente'] === 'tmdb' ? 'TMDB' : 'Wikipedia' ?></li>
            </ul>
            <?php if ($p['sinopsis'] !== '' && $p['sinopsis'] !== null): ?>
              <p class="tarjeta-sinopsis"><?= e($p['sinopsis']) ?></p>
            <?php endif; ?>
            <?php if ($p['notas'] !== ''): ?>
              <p class="tarjeta-notas">📝 <?= e($p['notas']) ?></p>
            <?php endif; ?>
            <div class="tarjeta-acciones">
              <?php $trailer = trailer_de($p); ?>
              <?php if ($trailer !== ''): ?>
                <button class="boton boton-chico boton-primario btn-trailer"
                        type="button"
                        data-trailer="<?= e($trailer) ?>"
                        data-titulo="<?= e($p['titulo']) ?>">▶ Tráiler</button>
              <?php endif; ?>
              <a class="boton boton-chico" href="formulario.php?id=<?= (int) $p['id'] ?>">Editar</a>
              <form method="post" action="acciones.php"
                    onsubmit="return confirm('¿Eliminar «<?= e($p['titulo']) ?>» de tu cartelera?');">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button class="boton boton-chico boton-peligro" type="submit">Eliminar</button>
              </form>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <!-- Paginador -->
    <?php if ($totalPeliculas > 0 && $totalPaginas > 1): ?>
      <nav class="paginador" aria-label="Paginación de la cartelera">
        <?php if ($pagina > 1): ?>
          <a class="paginador-enlace" href="<?= enlace_pagina($pagina - 1) ?>">← Anterior</a>
        <?php endif; ?>

        <?php for ($n = 1; $n <= $totalPaginas; $n++): ?>
          <?php if ($n === $pagina): ?>
            <span class="paginador-enlace paginador-activo"><?= $n ?></span>
          <?php else: ?>
            <a class="paginador-enlace" href="<?= enlace_pagina($n) ?>"><?= $n ?></a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($pagina < $totalPaginas): ?>
          <a class="paginador-enlace" href="<?= enlace_pagina($pagina + 1) ?>">Siguiente →</a>
        <?php endif; ?>
      </nav>
      <p class="paginador-info">
        Mostrando <?= $desde + 1 ?>–<?= $desde + count($peliculas) ?> de <?= $totalPeliculas ?> películas
      </p>
    <?php endif; ?>
  </section>
<?php include 'plantilla/pie.php';
