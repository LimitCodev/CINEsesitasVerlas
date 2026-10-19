<?php $titulo_pagina = $titulo_pagina ?? 'Cartelera'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo_pagina) ?> · CINEsesitasVerlas</title>
  <link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
  <header class="encabezado">
    <div class="contenedor encabezado-interno">
      <a class="marca" href="index.php">
        <span class="marca-nombre">CINE<span class="marca-separado">sesitas</span>Verlas</span>
        <span class="marca-lema">Sistema de funciones</span>
      </a>
      <nav class="menu">
        <a class="menu-enlace" href="index.php">Cartelera</a>
        <a class="menu-enlace" href="formulario.php">Registrar función</a>
      </nav>
    </div>
  </header>
  <main class="contenedor">
