<?php
/**
 * Utilidades generales y conexión a la base de datos.
 * Este archivo se incluye en todas las páginas del sistema.
 */
require_once __DIR__ . '/db.php';

/* ================= Utilidades ================= */

/** Escapa un texto antes de imprimirlo dentro del HTML. */
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Redirige a otra página y detiene la ejecución. */
function redirigir($ruta)
{
    header('Location: ' . $ruta);
    exit;
}

/* ================= Conexión ================= */

/** Abre la conexión con MySQL (sin seleccionar base de datos todavía). */
function conectar()
{
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, '', (int) DB_PORT);
    if ($conexion->connect_errno) {
        die(
            'No se pudo conectar con MySQL. Asegúrate de que Apache y MySQL estén iniciados en el panel de XAMPP.'
            . '<br>Detalle: ' . $conexion->connect_error
        );
    }
    $conexion->set_charset('utf8mb4');
    return $conexion;
}

/* ================= Instalación automática ================= */

/**
 * Crea la base de datos y la tabla si no existen.
 * Si la tabla queda vacía, inserta los datos de ejemplo.
 * Devuelve la conexión ya apuntando a la base del proyecto.
 */
function instalar_base()
{
    $conexion = conectar();
    $conexion->query(
        'CREATE DATABASE IF NOT EXISTS `' . DB_NOMBRE . '` '
        . 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
    );
    $conexion->select_db(DB_NOMBRE);

    $conexion->query(
        'CREATE TABLE IF NOT EXISTS funciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(150) NOT NULL,
            genero VARCHAR(60) NOT NULL,
            sala VARCHAR(30) NOT NULL,
            horario DATETIME NOT NULL,
            precio DECIMAL(6,2) NOT NULL DEFAULT 0,
            poster VARCHAR(500) DEFAULT NULL,
            sinopsis TEXT,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $consulta = $conexion->query('SELECT COUNT(*) AS total FROM funciones');
    $fila = $consulta->fetch_assoc();
    if ((int) $fila['total'] === 0) {
        sembrar_funciones($conexion);
    }

    return $conexion;
}

/** Inserta las funciones de ejemplo la primera vez que se usa el sistema. */
function sembrar_funciones($conexion)
{
    $ejemplos = [
        ['Duna: Parte Dos', 'Ciencia ficción', 'Sala 1', '2026-10-19 19:30', 22.00, '', 'Paul Atreides se une a los Fremen para vengar la muerte de su familia y frenar una guerra santa.'],
        ['Oppenheimer', 'Drama', 'Sala 2', '2026-10-19 21:00', 20.00, '', 'El físico J. Robert Oppenheimer dirige el proyecto Manhattan y enfrenta las consecuencias de la bomba atómica.'],
        ['Intensa Mente 2', 'Animación', 'Sala 3', '2026-10-20 16:00', 18.00, '', 'Las emociones de Riley enfrentan nuevos sentimientos cuando ella inicia la secundaria.'],
        ['Godzilla y Kong: El Nuevo Imperio', 'Acción', 'Sala 4', '2026-10-20 18:30', 22.00, '', 'Godzilla y Kong chocan contra una amenaza oculta que desafía su existencia.'],
        ['Deadpool y Wolverine', 'Acción', 'Sala 1', '2026-10-20 20:45', 24.00, '', 'Wade Wilson regresa y se alía con Wolverine en una misión que cambiará su historia.'],
        ['El Conjuro: La Maldición', 'Terror', 'Sala 5', '2026-10-21 22:00', 20.00, '', 'Los Warren investigan un caso relacionado con un sacerdote y un demonio antiguo.'],
        ['Coraline', 'Terror', 'Sala 6', '2026-10-22 21:30', 16.00, '', 'Una niña descubre una puerta secreta que la lleva a un mundo parecido pero peligroso.'],
        ['La Sustancia', 'Terror', 'Sala 5', '2026-10-23 23:00', 18.00, '', 'Una actriz descubre un producto que le permite volver a ser joven… por un precio.'],
        ['Kung Fu Panda 4', 'Animación', 'Sala 3', '2026-10-24 15:30', 16.00, '', 'Po debe encontrar al próximo Guerrero Dragón mientras enfrenta a una hechicera.'],
        ['Wicked', 'Musical', 'Sala 2', '2026-10-25 17:00', 24.00, '', 'Dos brujas jóvenes forjan una amistad en la tierra de Oz antes de tomar caminos opuestos.'],
        ['Anora', 'Drama', 'Sala 4', '2026-10-26 20:00', 20.00, '', 'Una joven de Brooklyn contrae un matrimonio impulsivo con el hijo de un oligarca ruso.'],
        ['Trap', 'Suspense', 'Sala 6', '2026-10-27 21:00', 18.00, '', 'Un padre descubre que el concierto al que asiste con su hija es una trampa para captar a un asesino.'],
        ['Mufasa: El Rey León', 'Animación', 'Sala 3', '2026-11-01 14:00', 20.00, '', 'La historia de cómo Mufasa llegó a ser rey y conoció a su hermano Scar.'],
        ['Misión: Imposible – The Final Reckoning', 'Acción', 'Sala 1', '2026-11-02 19:00', 26.00, '', 'Ethan Hunt y su equipo persiguen un peligroso arma que amenaza al mundo.'],
        ['Cómo entrenar a tu dragón', 'Animación', 'Sala 2', '2026-11-03 16:30', 18.00, '', 'En la isla de Berk, un joven vikingo hace amistad con un temido dragón.'],
        ['Gladiador II', 'Drama', 'Sala 4', '2026-11-04 20:30', 22.00, '', 'Lucio entra al Coliseo para vengar a su familia y desafiar al emperador de Roma.'],
    ];

    $sentencia = $conexion->prepare(
        'INSERT INTO funciones (titulo, genero, sala, horario, precio, poster, sinopsis)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($ejemplos as $f) {
        $sentencia->bind_param('ssssdss', $f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6]);
        $sentencia->execute();
    }
    $sentencia->close();
}
