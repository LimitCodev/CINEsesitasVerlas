<?php
/**
 * Procesa las acciones del CRUD: guardar (crear o editar) y eliminar.
 */
require_once 'config/base.php';
$conexion = instalar_base();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}

$accion = $_POST['accion'] ?? '';

/* ---------- Guardar: crear o editar ---------- */
if ($accion === 'guardar') {
    $id = (int) ($_POST['id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $sala = trim($_POST['sala'] ?? '');
    $horario = str_replace('T', ' ', trim($_POST['horario'] ?? ''));
    $precio = (float) ($_POST['precio'] ?? 0);
    $poster = trim($_POST['poster'] ?? '');
    $sinopsis = trim($_POST['sinopsis'] ?? '');

    if ($titulo === '' || $genero === '' || $sala === '' || $horario === '') {
        redirigir('formulario.php?error=1' . ($id > 0 ? '&id=' . $id : ''));
    }

    if ($id > 0) {
        $sentencia = $conexion->prepare(
            'UPDATE funciones SET titulo = ?, genero = ?, sala = ?, horario = ?, precio = ?, poster = ?, sinopsis = ? WHERE id = ?'
        );
        $sentencia->bind_param('ssssdssi', $titulo, $genero, $sala, $horario, $precio, $poster, $sinopsis, $id);
    } else {
        $sentencia = $conexion->prepare(
            'INSERT INTO funciones (titulo, genero, sala, horario, precio, poster, sinopsis) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $sentencia->bind_param('ssssdss', $titulo, $genero, $sala, $horario, $precio, $poster, $sinopsis);
    }

    $sentencia->execute();
    redirigir('index.php?ok=' . ($id > 0 ? 'actualizado' : 'guardado'));
}

/* ---------- Eliminar ---------- */
if ($accion === 'eliminar') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $sentencia = $conexion->prepare('DELETE FROM funciones WHERE id = ?');
        $sentencia->bind_param('i', $id);
        $sentencia->execute();
    }
    redirigir('index.php?ok=eliminado');
}

redirigir('index.php');
