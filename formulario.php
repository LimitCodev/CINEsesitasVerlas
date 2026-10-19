<?php
require_once 'config/base.php';
$conexion = instalar_base();

$id = (int) ($_GET['id'] ?? 0);
$pelicula = [
    'titulo' => '',
    'anio' => '',
    'genero' => '',
    'sinopsis' => '',
    'poster' => '',
    'trailer' => '',
    'enlace' => '',
    'fuente' => 'manual',
    'estado' => 'pendiente',
    'notas' => '',
];

if ($id > 0) {
    $sentencia = $conexion->prepare('SELECT * FROM peliculas WHERE id = ?');
    $sentencia->bind_param('i', $id);
    $sentencia->execute();
    $pelicula = $sentencia->get_result()->fetch_assoc();
    if (!$pelicula) {
        redirigir('index.php?ok=error');
    }
}

$hayError = isset($_GET['error']);
$titulo_pagina = $id > 0 ? 'Editar película' : 'Agregar película';
include 'plantilla/cabecera.php';
?>
  <section class="titulo-seccion">
    <div>
      <h1><?= $id > 0 ? 'Editar película' : 'Agregar película a mano' ?></h1>
      <p class="subtitulo">
        <?php if ($id > 0): ?>
          Corrige los datos guardados o cambia su estado y tus notas
        <?php else: ?>
          Completa al menos el título; el resto es opcional
        <?php endif; ?>
      </p>
    </div>
    <a class="boton boton-secundario" href="index.php">← Volver al buscador</a>
  </section>

  <?php if ($hayError): ?>
    <div class="aviso aviso-error">El campo «Título» es obligatorio.</div>
  <?php endif; ?>

  <form class="formulario" method="post" action="acciones.php">
    <input type="hidden" name="accion" value="guardar">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="fuente" value="<?= e($pelicula['fuente']) ?>">

    <div class="campo">
      <label for="titulo">Título *</label>
      <input id="titulo" name="titulo" type="text" maxlength="200" required
             placeholder="Ej. Doom" value="<?= e($pelicula['titulo']) ?>">
    </div>

    <div class="campo">
      <label for="anio">Año</label>
      <input id="anio" name="anio" type="text" maxlength="10"
             placeholder="Ej. 2005" value="<?= e($pelicula['anio']) ?>">
    </div>

    <div class="campo">
      <label for="genero">Género o descripción corta</label>
      <input id="genero" name="genero" type="text" maxlength="80"
             placeholder="Ej. Acción" value="<?= e($pelicula['genero']) ?>">
    </div>

    <div class="campo">
      <label for="estado">Estado</label>
      <select id="estado" name="estado">
        <?php foreach (['pendiente', 'vista', 'favorita'] as $opcion): ?>
          <option value="<?= $opcion ?>" <?= $pelicula['estado'] === $opcion ? 'selected' : '' ?>>
            <?= ucfirst($opcion) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="campo">
      <label for="poster">URL del póster</label>
      <input id="poster" name="poster" type="url" maxlength="500"
             placeholder="https://…" value="<?= e($pelicula['poster']) ?>">
    </div>

    <div class="campo">
      <label for="trailer">ID del tráiler de YouTube</label>
      <input id="trailer" name="trailer" type="text" maxlength="30"
             placeholder="Ej. AreOachjFf0" value="<?= e($pelicula['trailer']) ?>">
    </div>

    <div class="campo campo-ancho">
      <label for="enlace">Enlace de la ficha oficial</label>
      <input id="enlace" name="enlace" type="url" maxlength="500"
             placeholder="https://…" value="<?= e($pelicula['enlace']) ?>">
    </div>

    <div class="campo campo-ancho">
      <label for="sinopsis">Sinopsis</label>
      <textarea id="sinopsis" name="sinopsis" rows="4" maxlength="1000"
                placeholder="Breve descripción de la película"><?= e($pelicula['sinopsis']) ?></textarea>
    </div>

    <div class="campo campo-ancho">
      <label for="notas">Tus notas</label>
      <textarea id="notas" name="notas" rows="3" maxlength="600"
                placeholder="Ej. Recomendada por Ana, verla el fin de semana"><?= e($pelicula['notas']) ?></textarea>
    </div>

    <div class="acciones-formulario campo-ancho">
      <button class="boton boton-primario" type="submit">Guardar película</button>
      <a class="boton boton-secundario" href="index.php">Cancelar</a>
    </div>
  </form>
<?php include 'plantilla/pie.php';
