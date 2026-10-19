<?php
/**
 * Procesa las acciones del CRUD de la cartelera:
 * guardar (crear o editar), eliminar y guardar por Ajax
 * desde los resultados del buscador.
 */
require_once 'config/base.php';
$conexion = instalar_base();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}

$estadosPermitidos = ['pendiente', 'vista', 'favorita'];

/** Valida y arma los datos recibidos (formulario o Ajax). */
function datos_de_pelicula($estadosPermitidos)
{
    $titulo = trim($_POST['titulo'] ?? '');
    $anio = trim($_POST['anio'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $sinopsis = trim($_POST['sinopsis'] ?? '');
    $poster = trim($_POST['poster'] ?? '');
    $trailer = trim($_POST['trailer'] ?? '');
    $enlace = trim($_POST['enlace'] ?? '');
    $fuente = trim($_POST['fuente'] ?? 'wikipedia');
    $estado = $_POST['estado'] ?? 'pendiente';
    $notas = trim($_POST['notas'] ?? '');

    if (!in_array($fuente, ['wikipedia', 'tmdb', 'manual'], true)) {
        $fuente = 'manual';
    }
    if (!in_array($estado, $estadosPermitidos, true)) {
        $estado = 'pendiente';
    }
    /* Solo se aceptan IDs de YouTube o referencias tmdb:NNN. */
    if (!preg_match('/^[A-Za-z0-9_-]{5,20}$/', $trailer) && !preg_match('/^tmdb:\d+$/', $trailer)) {
        $trailer = '';
    }

    return [
        'titulo' => $titulo,
        'anio' => $anio,
        'genero' => $genero,
        'sinopsis' => $sinopsis,
        'poster' => $poster,
        'trailer' => $trailer,
        'enlace' => $enlace,
        'fuente' => $fuente,
        'estado' => $estado,
        'notas' => $notas,
        'valido' => $titulo !== '',
    ];
}

$accion = $_POST['accion'] ?? '';

/* ---------- Guardar por Ajax (desde los resultados del buscador) ---------- */
if ($accion === 'guardar_ajax') {
    $datos = datos_de_pelicula($estadosPermitidos);
    if (!$datos['valido']) {
        responder_json(['ok' => false, 'mensaje' => 'Falta el título de la película.']);
    }

    /* Evita duplicados: mismo título y mismo enlace. */
    $sentencia = $conexion->prepare('SELECT id FROM peliculas WHERE titulo = ? AND enlace = ?');
    $sentencia->bind_param('ss', $datos['titulo'], $datos['enlace']);
    $sentencia->execute();
    if ($sentencia->get_result()->num_rows > 0) {
        responder_json(['ok' => false, 'mensaje' => 'Ya estaba en tu cartelera.']);
    }
    $sentencia->close();

    $sentencia = $conexion->prepare(
        'INSERT INTO peliculas (titulo, anio, genero, sinopsis, poster, trailer, enlace, fuente, estado, notas)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $sentencia->bind_param(
        'ssssssssss',
        $datos['titulo'],
        $datos['anio'],
        $datos['genero'],
        $datos['sinopsis'],
        $datos['poster'],
        $datos['trailer'],
        $datos['enlace'],
        $datos['fuente'],
        $datos['estado'],
        $datos['notas']
    );
    $sentencia->execute();
    responder_json(['ok' => true, 'id' => $conexion->insert_id, 'mensaje' => '✓ Guardada en tu cartelera']);
}

/* ---------- Guardar (crear o editar desde el formulario) ---------- */
if ($accion === 'guardar') {
    $datos = datos_de_pelicula($estadosPermitidos);
    $id = (int) ($_POST['id'] ?? 0);

    if (!$datos['valido']) {
        redirigir('formulario.php?error=1' . ($id > 0 ? '&id=' . $id : ''));
    }

    if ($id > 0) {
        $sentencia = $conexion->prepare(
            'UPDATE peliculas SET titulo = ?, anio = ?, genero = ?, sinopsis = ?, poster = ?,
             trailer = ?, enlace = ?, fuente = ?, estado = ?, notas = ? WHERE id = ?'
        );
        $sentencia->bind_param(
            'ssssssssssi',
            $datos['titulo'],
            $datos['anio'],
            $datos['genero'],
            $datos['sinopsis'],
            $datos['poster'],
            $datos['trailer'],
            $datos['enlace'],
            $datos['fuente'],
            $datos['estado'],
            $datos['notas'],
            $id
        );
    } else {
        $sentencia = $conexion->prepare(
            'INSERT INTO peliculas (titulo, anio, genero, sinopsis, poster, trailer, enlace, fuente, estado, notas)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $sentencia->bind_param(
            'ssssssssss',
            $datos['titulo'],
            $datos['anio'],
            $datos['genero'],
            $datos['sinopsis'],
            $datos['poster'],
            $datos['trailer'],
            $datos['enlace'],
            $datos['fuente'],
            $datos['estado'],
            $datos['notas']
        );
    }

    $sentencia->execute();
    redirigir('index.php?ok=' . ($id > 0 ? 'actualizado' : 'guardado'));
}

/* ---------- Eliminar ---------- */
if ($accion === 'eliminar') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $sentencia = $conexion->prepare('DELETE FROM peliculas WHERE id = ?');
        $sentencia->bind_param('i', $id);
        $sentencia->execute();
    }
    redirigir('index.php?ok=eliminado');
}

redirigir('index.php');
