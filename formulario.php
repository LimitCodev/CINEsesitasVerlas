<?php
require_once 'config/base.php';
$conexion = instalar_base();

$id = (int) ($_GET['id'] ?? 0);
$funcion = [
    'titulo' => '',
    'genero' => '',
    'sala' => 'Sala 1',
    'horario' => '',
    'precio' => '',
    'poster' => '',
    'sinopsis' => '',
];

if ($id > 0) {
    $sentencia = $conexion->prepare('SELECT * FROM funciones WHERE id = ?');
    $sentencia->bind_param('i', $id);
    $sentencia->execute();
    $funcion = $sentencia->get_result()->fetch_assoc();
    if (!$funcion) {
        redirigir('index.php?ok=error');
    }
}

$hayError = isset($_GET['error']);
$titulo_pagina = $id > 0 ? 'Editar función' : 'Nueva función';
$valorHorario = $funcion['horario'] !== ''
    ? date('Y-m-d\TH:i', strtotime($funcion['horario']))
    : '';
include 'plantilla/cabecera.php';
?>
  <section class="titulo-seccion">
    <div>
      <h1><?= $id > 0 ? 'Editar función' : 'Registrar nueva función' ?></h1>
      <p class="subtitulo">Completa los datos de la película y su horario de exhibición</p>
    </div>
    <a class="boton boton-secundario" href="index.php">← Volver a la cartelera</a>
  </section>

  <!-- Caso aplicativo de Ajax: importar datos de la película desde TVMaze
       sin recargar la página -->
  <section class="importador">
    <h2>Importar datos desde TVMaze</h2>
    <p class="subtitulo">Busca la película y toca «Usar datos» para completar el formulario automáticamente.</p>
    <div class="importador-busqueda">
      <input id="entrada-tvmaze" type="search" maxlength="80" placeholder="Ej. Duna, Oppenheimer, Coraline…">
      <button class="boton boton-primario" id="btn-buscar-tvmaze" type="button">Buscar en TVMaze</button>
    </div>
    <div id="resultados-tvmaze" class="importador-resultados" aria-live="polite"></div>
  </section>

  <?php if ($hayError): ?>
    <div class="aviso aviso-error">Faltan datos obligatorios: película, género, sala y horario.</div>
  <?php endif; ?>

  <form class="formulario" method="post" action="acciones.php">
    <input type="hidden" name="accion" value="guardar">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="campo">
      <label for="titulo">Película *</label>
      <input id="titulo" name="titulo" type="text" maxlength="150" required
             placeholder="Ej. Duna: Parte Dos" value="<?= e($funcion['titulo']) ?>">
    </div>

    <div class="campo">
      <label for="genero">Género *</label>
      <input id="genero" name="genero" type="text" maxlength="60" required list="lista-generos"
             placeholder="Ej. Acción" value="<?= e($funcion['genero']) ?>">
      <datalist id="lista-generos">
        <option value="Acción"></option>
        <option value="Animación"></option>
        <option value="Ciencia ficción"></option>
        <option value="Drama"></option>
        <option value="Musical"></option>
        <option value="Suspense"></option>
        <option value="Terror"></option>
      </datalist>
    </div>

    <div class="campo">
      <label for="sala">Sala *</label>
      <select id="sala" name="sala" required>
        <?php for ($n = 1; $n <= 6; $n++): ?>
          <?php $sala = 'Sala ' . $n; ?>
          <option value="<?= $sala ?>" <?= $funcion['sala'] === $sala ? 'selected' : '' ?>><?= $sala ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <div class="campo">
      <label for="horario">Fecha y hora de la función *</label>
      <input id="horario" name="horario" type="datetime-local" required value="<?= e($valorHorario) ?>">
    </div>

    <div class="campo">
      <label for="precio">Precio (S/) *</label>
      <input id="precio" name="precio" type="number" min="0" step="0.50" required
             placeholder="Ej. 20.00" value="<?= e($funcion['precio']) ?>">
    </div>

    <div class="campo">
      <label for="poster">URL del póster</label>
      <input id="poster" name="poster" type="url" maxlength="500"
             placeholder="https://…" value="<?= e($funcion['poster']) ?>">
    </div>

    <div class="campo campo-ancho">
      <label for="sinopsis">Sinopsis</label>
      <textarea id="sinopsis" name="sinopsis" rows="4" maxlength="1000"
                placeholder="Breve descripción de la película"><?= e($funcion['sinopsis']) ?></textarea>
    </div>

    <div class="acciones-formulario campo-ancho">
      <button class="boton boton-primario" type="submit">Guardar función</button>
      <a class="boton boton-secundario" href="index.php">Cancelar</a>
    </div>
  </form>
<?php include 'plantilla/pie.php';
