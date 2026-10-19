<?php
/**
 * Claves de los servicios externos.
 *
 * El buscador funciona SIN ninguna clave usando Wikipedia y Wikidata.
 * Si quieres carteles oficiales (pósteres) y tráilers de TMDB, crea el
 * archivo config/api.local.php —está excluido del repositorio— con una
 * clave gratuita de <https://www.themoviedb.org/settings/api>:
 *
 *     <?php
 *     define('TMDB_API_KEY', 'tu_clave_aqui');
 *
 * Sin clave, el sistema usa automáticamente el modo Wikipedia.
 */
if (file_exists(__DIR__ . '/api.local.php')) {
    require __DIR__ . '/api.local.php';
}

defined('TMDB_API_KEY') || define('TMDB_API_KEY', '');
