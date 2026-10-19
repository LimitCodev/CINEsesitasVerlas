<?php
require_once 'config/base.php';
$conexion = instalar_base();

/* ================= Búsqueda y paginación ================= */
$busqueda = trim($_GET['q'] ?? '');
$pagina = (int) ($_GET['pagina'] ?? 1);
$porPagina = 6;

/* 1) Contar registros (con o sin búsqueda) */
if ($busqueda === '') {
    $consulta = $conexion->query('SELECT COUNT(*) AS total FROM funciones');
    $fila = $consulta->fetch_assoc();
    $totalFunciones = (int) $fila['total'];
} else {
    $como = '%' . $busqueda . '%';
    $sentencia = $conexion->prepare(
        'SELECT COUNT(*) AS total FROM funciones WHERE titulo LIKE ? OR genero LIKE ? OR sala LIKE ?'
    );
    $sentencia->bind_param('sss', $como, $como, $como);
    $sentencia->execute();
    $fila = $sentencia->get_result()->fetch_assoc();
    $totalFunciones = (int) $fila['total'];
}

/* 2) Calcular la página actual y el desplazamiento */
$totalPaginas = max(1, (int) ceil($totalFunciones / $porPagina));
if ($pagina < 1) {
    $pagina = 1;
}
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}
$desde = ($pagina - 1) * $porPagina;

/* 3) Traer solo la página solicitada */
if ($busqueda === '') {
    $sentencia = $conexion->prepare(
        "SELECT * FROM funciones ORDER BY horario ASC LIMIT $desde, $porPagina"
    );
    $sentencia->execute();
} else {
    $sentencia = $conexion->prepare(
        "SELECT * FROM funciones WHERE titulo LIKE ? OR genero LIKE ? OR sala LIKE ?
         ORDER BY horario ASC LIMIT $desde, $porPagina"
    );
    $sentencia->bind_param('sss', $como, $como, $como);
    $sentencia->execute();
}
$funciones = [];
$resultado = $sentencia->get_result();
while ($fila = $resultado->fetch_assoc()) {
    $funciones[] = $fila;
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

$mensaje = $_GET['ok'] ?? '';
$titulo_pagina = 'Cartelera';
include 'plantilla/cabecera.php';
?>
  <section class="titulo-seccion">
    <div>
      <h1>Cartelera de funciones</h1>
      <p class="subtitulo">
        <?php if ($busqueda !== ''): ?>
          <?= $totalFunciones ?> resultado(s) para «<?= e($busqueda) ?>»
        <?php else: ?>
          <?= $totalFunciones ?> función(es) programada(s)
        <?php endif; ?>
      </p>
    </div>
    <a class="boton boton-primario" href="formulario.php">+ Nueva función</a>
  </section>

  <?php if ($mensaje === 'guardado'): ?>
    <div class="aviso aviso-correcto">Función registrada correctamente.</div>
  <?php elseif ($mensaje === 'actualizado'): ?>
    <div class="aviso aviso-correcto">Función actualizada correctamente.</div>
  <?php elseif ($mensaje === 'eliminado'): ?>
    <div class="aviso aviso-correcto">Función eliminada.</div>
  <?php elseif ($mensaje === 'error'): ?>
    <div class="aviso aviso-error">Ocurrió un problema al guardar. Revisa los campos obligatorios.</div>
  <?php endif; ?>

  <!-- Buscador: filtra por título, género o sala -->
  <form class="buscador" method="get" action="index.php">
    <input class="buscador-entrada" type="search" name="q" maxlength="80"
           placeholder="Buscar por título, género o sala…" value="<?= e($busqueda) ?>">
    <button class="boton boton-primario" type="submit">Buscar</button>
    <?php if ($busqueda !== ''): ?>
      <a class="boton boton-secundario" href="index.php">Limpiar</a>
    <?php endif; ?>
  </form>

  <div class="rejilla">
    <?php if (empty($funciones)): ?>
      <p class="vacio">
        <?php if ($busqueda !== ''): ?>
          No se encontraron funciones para «<?= e($busqueda) ?>».
        <?php else: ?>
          Aún no hay funciones registradas. Usa el botón «Nueva función» para agregar la primera.
        <?php endif; ?>
      </p>
    <?php endif; ?>

    <?php foreach ($funciones as $f): ?>
      <article class="tarjeta">
        <div class="tarjeta-poster">
          <?php if (!empty($f['poster'])): ?>
            <img src="<?= e($f['poster']) ?>" alt="Póster de <?= e($f['titulo']) ?>">
          <?php else: ?>
            <span class="poster-inicial"><?= e(mb_strtoupper(mb_substr($f['titulo'], 0, 1))) ?></span>
          <?php endif; ?>
          <span class="tarjeta-genero"><?= e($f['genero']) ?></span>
        </div>
        <div class="tarjeta-cuerpo">
          <h2><?= e($f['titulo']) ?></h2>
          <ul class="tarjeta-datos">
            <li><span>Sala</span> <?= e($f['sala']) ?></li>
            <li><span>Función</span> <?= date('d/m/Y H:i', strtotime($f['horario'])) ?></li>
            <li><span>Precio</span> S/ <?= number_format((float) $f['precio'], 2) ?></li>
          </ul>
          <p class="tarjeta-sinopsis"><?= e($f['sinopsis']) ?></p>
          <div class="tarjeta-acciones">
            <a class="boton boton-chico" href="formulario.php?id=<?= (int) $f['id'] ?>">Editar</a>
            <form method="post" action="acciones.php"
                  onsubmit="return confirm('¿Eliminar la función «<?= e($f['titulo']) ?>»?');">
              <input type="hidden" name="accion" value="eliminar">
              <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
              <button class="boton boton-chico boton-peligro" type="submit">Eliminar</button>
            </form>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <!-- Paginador -->
  <?php if ($totalFunciones > 0 && $totalPaginas > 1): ?>
    <nav class="paginador" aria-label="Paginación de resultados">
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
      Mostrando <?= $desde + 1 ?>–<?= $desde + count($funciones) ?> de <?= $totalFunciones ?> funciones
    </p>
  <?php endif; ?>
<?php include 'plantilla/pie.php';
