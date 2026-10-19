<?php
require_once 'config/base.php';
$conexion = instalar_base();

$resultado = $conexion->query('SELECT * FROM funciones ORDER BY horario ASC');
$funciones = [];
while ($fila = $resultado->fetch_assoc()) {
    $funciones[] = $fila;
}

$mensaje = $_GET['ok'] ?? '';
$titulo_pagina = 'Cartelera';
include 'plantilla/cabecera.php';
?>
  <section class="titulo-seccion">
    <div>
      <h1>Cartelera de funciones</h1>
      <p class="subtitulo">Funciones programadas: <?= count($funciones) ?></p>
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

  <div class="rejilla">
    <?php if (empty($funciones)): ?>
      <p class="vacio">Aún no hay funciones registradas. Usa el botón «Nueva función» para agregar la primera.</p>
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
<?php include 'plantilla/pie.php';
