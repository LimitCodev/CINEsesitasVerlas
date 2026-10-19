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

/**
 * Consulta una URL externa con cURL y devuelve el JSON decodificado (o null).
 *
 * Wikipedia y Wikidata responden 429 (límite de consultas) con una ventana de
 * ~45 s. Si el servidor lo indica en Retry-After no reintentamos (no serviría);
 * si la espera es corta o falla un 5xx, reintentamos una vez. El resto de fallos
 * devuelve null sin esperas, para no tardar en la búsqueda.
 */
function consultar_api($url)
{
    for ($intento = 0; $intento < 2; $intento++) {
        if ($intento > 0) {
            usleep(1200000); /* 1,2 s de tregua antes de reintentar */
        }

        $esperaRetry = 0;
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
            /* Guardamos Retry-After para avisar al usuario con un tiempo real. */
            CURLOPT_HEADERFUNCTION => function ($manejador, $cabecera) use (&$esperaRetry) {
                $partes = explode(':', $cabecera, 2);
                if (count($partes) === 2 && strtolower(trim($partes[0])) === 'retry-after') {
                    $esperaRetry = max(0, (int) trim($partes[1]));
                }
                return strlen($cabecera);
            },
        ]);
        $respuesta = curl_exec($curl);
        $codigo = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($respuesta === false || $codigo === 0 || $codigo >= 400) {
            if ($codigo === 429 || $codigo >= 500) {
                if ($codigo === 429) {
                    /* Publicamos la espera que pide el servidor (si la hay). */
                    $GLOBALS['api_espera'] = $esperaRetry;
                    if ($esperaRetry > 3) {
                        return null; /* Reintentar a los 1,2 s daría 429 otra vez */
                    }
                }
                continue;
            }
            return null;
        }

        $datos = json_decode($respuesta, true);
        if (is_array($datos)) {
            return $datos;
        }
        /* Cuerpo que no es JSON (páginas de error): no insiste. */
        return null;
    }

    return null;
}

/** Segundos que el último 429 pidió esperar (0 si no hubo límite). */
function espera_recomendada()
{
    return (int) ($GLOBALS['api_espera'] ?? 0);
}

/* ================= Caché de consultas externas ================= */

/**
 * Wikipedia permite ~10 peticiones por ventana y luego bloquea 45 s. Guardamos
 * en MySQL tanto los resultados de cada búsqueda como los datos de cada entidad
 * de Wikidata: una consulta repetida no vuelve a gastar una llamada externa.
 *
 * Devuelve el array guardado o null (no había, caducó, o no hay conexión).
 */
function leer_cache($conexion, $clave)
{
    if (!($conexion instanceof mysqli)) {
        return null;
    }
    $sentencia = $conexion->prepare('SELECT datos, expira FROM cache_externa WHERE clave = ?');
    if (!$sentencia) {
        return null;
    }
    $sentencia->bind_param('s', $clave);
    if (!$sentencia->execute()) {
        $sentencia->close();
        return null;
    }
    $fila = $sentencia->get_result()->fetch_assoc();
    $sentencia->close();

    if (!$fila) {
        return null;
    }
    if (strtotime($fila['expira']) < time()) {
        $borrado = $conexion->prepare('DELETE FROM cache_externa WHERE clave = ?');
        if ($borrado) {
            $borrado->bind_param('s', $clave);
            $borrado->execute();
            $borrado->close();
        }
        return null;
    }
    $datos = json_decode($fila['datos'], true);
    return is_array($datos) ? $datos : null;
}

/** Guarda (o actualiza) una entrada de la caché con la vida indicada. */
function guardar_cache($conexion, $clave, $datos, $segundos)
{
    if (!($conexion instanceof mysqli) || !is_array($datos)) {
        return;
    }
    $expira = date('Y-m-d H:i:s', time() + $segundos);
    $contenido = json_encode($datos, JSON_UNESCAPED_UNICODE);
    if ($contenido === false) {
        return;
    }
    $sentencia = $conexion->prepare(
        'INSERT INTO cache_externa (clave, datos, expira) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE datos = VALUES(datos), expira = VALUES(expira)'
    );
    if (!$sentencia) {
        return;
    }
    $sentencia->bind_param('sss', $clave, $contenido, $expira);
    $sentencia->execute();
    $sentencia->close();
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

    /* Caché de las consultas a APIs externas (ver leer_cache/guardar_cache). */
    $conexion->query(
        'CREATE TABLE IF NOT EXISTS cache_externa (
            clave VARCHAR(190) NOT NULL,
            datos MEDIUMTEXT NOT NULL,
            expira DATETIME NOT NULL,
            PRIMARY KEY (clave),
            KEY idx_cache_expira (expira)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    return $conexion;
}
