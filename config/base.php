<?php
/**
 * Utilidades generales y conexión a la base de datos.
 * Este archivo se incluye en todas las páginas del sistema.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api.php';

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

/** Responde a una petición Ajax con JSON y termina. */
function responder_json($datos)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Consulta una URL externa con cURL y devuelve el JSON decodificado (o null). */
function consultar_api($url)
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: CINEsesitasVerlas/2.0 (aplicacion academica)',
        ],
    ]);
    $respuesta = curl_exec($curl);
    curl_close($curl);

    if ($respuesta === false) {
        return null;
    }
    $datos = json_decode($respuesta, true);
    return is_array($datos) ? $datos : null;
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
 * La cartelera inicia VACÍA: las películas se agregan desde el buscador
 * con datos reales (Wikipedia/Wikidata o TMDB), nunca con datos de ejemplo.
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

    /* Limpia la tabla de la versión anterior (solo contenía datos ficticios). */
    $conexion->query('DROP TABLE IF EXISTS funciones');

    $conexion->query(
        'CREATE TABLE IF NOT EXISTS peliculas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(200) NOT NULL,
            anio VARCHAR(10) NOT NULL DEFAULT "",
            genero VARCHAR(80) NOT NULL DEFAULT "",
            sinopsis TEXT,
            poster VARCHAR(500) NOT NULL DEFAULT "",
            trailer VARCHAR(30) NOT NULL DEFAULT "",
            enlace VARCHAR(500) NOT NULL DEFAULT "",
            fuente VARCHAR(20) NOT NULL DEFAULT "wikipedia",
            estado VARCHAR(20) NOT NULL DEFAULT "pendiente",
            notas VARCHAR(600) NOT NULL DEFAULT "",
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    return $conexion;
}
