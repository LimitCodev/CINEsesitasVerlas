<?php $titulo_pagina = $titulo_pagina ?? 'Buscador'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo_pagina) ?> · CINEsesitasVerlas</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
  <header class="encabezado">
    <div class="contenedor encabezado-interno">
      <a class="marca" href="index.php">
        <img class="marca-logo" src="assets/img/logo.svg" alt="" width="44" height="44">
        <span>
          <span class="marca-nombre">CINE<span class="marca-separado">sesitas</span>Verlas</span>
          <span class="marca-lema">Buscador y cartelera personal</span>
        </span>
      </a>
      <nav class="menu">
        <a class="menu-enlace" href="index.php">Buscador</a>
        <a class="menu-enlace" href="index.php#cartelera">Mi cartelera</a>
        <a class="boton boton-primario boton-chico" href="formulario.php">+ Película</a>
      </nav>
    </div>
  </header>
  <main class="contenedor">
