<?php
/**
 * Endpoint de Ajax: búsqueda de películas con datos REALES.
 *
 * Proveedor conmutable:
 *   - TMDB (si hay una clave gratuita en config/api.local.php): búsqueda,
 *     pósteres oficiales y tráilers de YouTube.
 *   - Wikipedia + Wikidata (sin ninguna clave): búsqueda, resumen e ID del
 *     tráiler (propiedad P1651 de Wikidata) para reproducirlo con YouTube.
 *
 * Uso: api/pelicula.php?buscar=doom
 *       api/pelicula.php?trailer=tmdb:615457
 */
require_once __DIR__ . '/../config/base.php';

/* Deja creada la base (y la tabla de caché) y devuelve la conexión. */
$conexion = instalar_base();

$buscar = trim($_GET['buscar'] ?? '');
$trailer = trim($_GET['trailer'] ?? '');

/* ---------- Pedido puntual de un tráiler de TMDB (al pulsar «Ver tráiler») ---------- */
if ($trailer !== '') {
    if (TMDB_API_KEY === '') {
        responder_json(['ok' => false, 'mensaje' => 'Este modo no tiene tráilers disponibles.']);
    }
    if (!preg_match('/^tmdb:(\d+)$/', $trailer, $coincidencia)) {
        responder_json(['ok' => false, 'mensaje' => 'Referencia de tráiler inválida.']);
    }
    $idTmdb = $coincidencia[1];
    foreach (['es', 'en'] as $idioma) {
        $datos = consultar_api(
            'https://api.themoviedb.org/3/movie/' . $idTmdb . '/videos'
            . '?api_key=' . urlencode(TMDB_API_KEY) . '&language=' . $idioma
        );
        if (!$datos || empty($datos['results'])) {
            continue;
        }
        $elegido = null;
        foreach ($datos['results'] as $video) {
            if (($video['site'] ?? '') !== 'YouTube' || empty($video['key'])) {
                continue;
            }
            $esTrailer = ($video['type'] ?? '') === 'Trailer';
            $esOficial = !empty($video['official']);
            if ($esTrailer && $esOficial) {
                $elegido = $video;
                break;
            }
            if ($esTrailer && !$elegido) {
                $elegido = $video;
            }
            if (!$elegido) {
                $elegido = $video;
            }
        }
        if ($elegido) {
            responder_json(['ok' => true, 'trailer' => $elegido['key']]);
        }
    }
    responder_json(['ok' => false, 'mensaje' => 'TMDB no tiene tráiler para esa película.']);
}

/* ---------- Búsqueda ---------- */
if ($buscar === '' || mb_strlen($buscar) > 80) {
    responder_json(['ok' => false, 'mensaje' => 'Escribe un título para buscar (máx. 80 caracteres).']);
}

/* Clave de caché: lo que se busca + el proveedor activo (vida de 6 horas).
   Una repetición responde al instante y sin gastar una sola llamada externa. */
$proveedor = TMDB_API_KEY !== '' ? 'tmdb' : 'wikipedia';
$claveBusqueda = 'consulta:' . $proveedor . ':' . mb_strtolower(preg_replace('/\s+/u', ' ', $buscar));

$enCache = leer_cache($conexion, $claveBusqueda);
if ($enCache !== null) {
    responder_json(['ok' => true, 'modo' => $proveedor, 'cache' => true, 'peliculas' => $enCache]);
}

/* Si hay clave de TMDB se usa ese proveedor; si no, Wikipedia + Wikidata. */
if (TMDB_API_KEY !== '') {
    $peliculas = buscar_en_tmdb($buscar);
    if ($peliculas !== null) {
        guardar_cache($conexion, $claveBusqueda, $peliculas, 21600);
        responder_json(['ok' => true, 'modo' => 'tmdb', 'peliculas' => $peliculas]);
    }
}

$peliculas = buscar_en_wikipedia($buscar, $conexion);

/* null = la API externa no respondió (límite de consultas o caída).
   No lo presentamos como «sin resultados»: avisamos al usuario. */
if ($peliculas === null) {
    $espera = espera_recomendada();
    responder_json([
        'ok' => false,
        'mensaje' => $espera > 0
            ? 'Wikipedia limita las consultas: espera ' . $espera . ' s y vuelve a intentarlo.'
            : 'Wikipedia no responde ahora (límite de consultas); intenta en unos segundos.',
    ]);
}

guardar_cache($conexion, $claveBusqueda, $peliculas, 21600);
responder_json(['ok' => true, 'modo' => 'wikipedia', 'peliculas' => $peliculas]);

/* ================= Proveedor TMDB (requiere clave gratuita) ================= */

function buscar_en_tmdb($texto)
{
    $url = 'https://api.themoviedb.org/3/search/movie?api_key=' . urlencode(TMDB_API_KEY)
        . '&language=es&query=' . urlencode($texto) . '&page=1&include_adult=false';
    $datos = consultar_api($url);
    if (!$datos || !isset($datos['results'])) {
        return null;
    }

    $peliculas = [];
    foreach (array_slice($datos['results'], 0, 12) as $item) {
        $id = (int) ($item['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $peliculas[] = [
            'fuente' => 'tmdb',
            'id' => (string) $id,
            'titulo' => $item['title'] ?? '',
            'anio' => substr($item['release_date'] ?? '', 0, 4),
            'genero' => '',
            'sinopsis' => mb_substr($item['overview'] ?? '', 0, 420),
            'poster' => !empty($item['poster_path'])
                ? 'https://image.tmdb.org/t/p/w342' . $item['poster_path']
                : '',
            'trailer' => '',
            'enlace' => 'https://www.themoviedb.org/movie/' . $id,
        ];
    }
    return $peliculas;
}

/* ================= Proveedor Wikipedia + Wikidata (sin clave) ================= */

function buscar_en_wikipedia($texto, $conexion = null)
{
    /* 1) Una sola petición trae todo: el generator devuelve los artículos
          ordenados por relevancia (campo 'index') junto con su extracto,
          descripción, miniatura y QID. Así cada búsqueda hace 2 peticiones en
          vez de 3 y Wikipedia nos límite menos (evita el 429). Pedimos 16
          porque los artículos de películas a veces rankean más abajo (el de
          «Doom» está en el puesto 10). */
    $datos = consultar_api(
        'https://es.wikipedia.org/w/api.php?action=query&format=json&utf8=1&redirects=1'
        . '&generator=search&gsrsearch=' . urlencode($texto) . '&gsrlimit=16&gsrnamespace=0'
        . '&prop=pageprops|extracts|description|pageimages'
        . '&ppprop=wikibase_item&exintro=1&explaintext=1&exlimit=max'
        . '&piprop=thumbnail&pithumbsize=400'
    );
    /* Distinguimos «la API no respondió» (null) de «no hubo coincidencias» ([]). */
    if (!$datos) {
        return null;
    }
    if (empty($datos['query']['pages'])) {
        return [];
    }

    $fichasPorQid = [];
    $qids = [];
    foreach ($datos['query']['pages'] as $pagina) {
        if (isset($pagina['missing'])) {
            continue;
        }
        $titulo = $pagina['title'] ?? '';
        $sinopsis = trim($pagina['extract'] ?? '');
        if ($titulo === '' || $sinopsis === '') {
            continue;
        }
        /* 'index' es la posición en el ranking de búsqueda (relevancia). */
        $orden = isset($pagina['index']) ? (int) $pagina['index'] : 999;
        $qid = $pagina['pageprops']['wikibase_item'] ?? '';
        if ($qid !== '') {
            $qids[$qid] = true;
        }
        $fichasPorQid[$qid !== '' ? $qid : $titulo] = [
            'qid' => $qid,
            'titulo' => $titulo,
            'sinopsis' => $sinopsis,
            'descripcion' => trim($pagina['description'] ?? ''),
            'poster' => $pagina['thumbnail']['source'] ?? '',
            'orden' => $orden,
        ];
    }

    /* 3) Tráileres e imagen: una sola petición a Wikidata (P1651 y P18),
          con caché por entidad: lo ya consultado no se repite. */
    $datosWikidata = datos_de_wikidata(array_keys($qids), $conexion);

    /* 4) Armo la lista: primero lo que parece película, después el resto.
          Cada grupo conserva el orden de relevancia del search (hallazgo 3).
          El /u es imprescindible: sin él [íi] casa bytes y «película» nunca
          coincide en UTF-8 (hallazgo 1). */
    $peliculas = [];
    $otros = [];
    foreach ($fichasPorQid as $ficha) {
        $coincideCine = preg_match(
            '/pel[íi]cula|film|largometraje|cine/iu',
            $ficha['descripcion'] . ' ' . mb_substr($ficha['sinopsis'], 0, 160)
        );
        /* Póster: primero la imagen P18 de Wikidata; si no, la de Wikipedia. */
        $poster = $ficha['qid'] !== '' ? ($datosWikidata[$ficha['qid']]['poster'] ?? '') : '';
        if ($poster === '') {
            $poster = $ficha['poster'];
        }
        $trailer = $ficha['qid'] !== '' ? ($datosWikidata[$ficha['qid']]['trailer'] ?? '') : '';

        $item = [
            'fuente' => 'wikipedia',
            'id' => $ficha['qid'] !== '' ? $ficha['qid'] : $ficha['titulo'],
            'titulo' => $ficha['titulo'],
            'anio' => primer_anio($ficha['descripcion'] . ' ' . mb_substr($ficha['sinopsis'], 0, 200)),
            'genero' => $ficha['descripcion'],
            'sinopsis' => mb_substr($ficha['sinopsis'], 0, 420),
            'poster' => $poster,
            'trailer' => $trailer,
            'enlace' => 'https://es.wikipedia.org/wiki/' . rawurlencode(str_replace(' ', '_', $ficha['titulo'])),
            'orden' => $ficha['orden'],
        ];
        if ($coincideCine) {
            $peliculas[] = $item;
        } else {
            $otros[] = $item;
        }
    }

    /* Ordena cada grupo: primero los artículos del año que se buscó y luego
          por la posición en el ranking de Wikipedia. Sirve para que
          «nosferatu 2024» no devuelva la versión de 1979 (Wikipedia la rankea
          arriba), sin romper búsquedas sin año, que no cambian de orden. */
    $anosBuscados = [];
    if (preg_match_all('/\b((?:19|20)\d{2})\b/', $texto, $encontrados)) {
        $anosBuscados = $encontrados[1];
    }
    $porRelevancia = function ($a, $b) use ($anosBuscados) {
        $aCoincide = in_array($a['anio'], $anosBuscados, true) ? 1 : 0;
        $bCoincide = in_array($b['anio'], $anosBuscados, true) ? 1 : 0;
        if ($aCoincide !== $bCoincide) {
            return $bCoincide - $aCoincide;
        }
        return $a['orden'] <=> $b['orden'];
    };
    usort($peliculas, $porRelevancia);
    usort($otros, $porRelevancia);

    $resultado = array_merge($peliculas, $otros);
    /* Quita el campo interno antes de devolver. */
    foreach ($resultado as &$item) {
        unset($item['orden']);
    }
    unset($item);

    return array_slice($resultado, 0, 8);
}

/**
 * Devuelve, para los QIDs dados, [qid => ['trailer' => id_youtube, 'poster' => url]].
 *
 * Es UNA sola petición: P1651 (tráiler) y P18 (imagen del filme) vienen juntas
 * en 'claims'. El póster P18 apunta a Wikimedia Commons, que sí tiene imágenes
 * libres; cuando está vacío (copyright) usamos la miniatura de Wikipedia.
 */
function datos_de_wikidata($qids, $conexion = null)
{
    if (empty($qids)) {
        return [];
    }

    /* 1) Lo que ya se pidió esta semana está en caché: no se repite. */
    $fichas = [];
    $faltan = [];
    foreach ($qids as $qid) {
        $guardado = leer_cache($conexion, 'entidad:' . $qid);
        if ($guardado !== null) {
            $fichas[$qid] = $guardado;
        } else {
            $faltan[] = $qid;
        }
    }
    if (empty($faltan)) {
        return $fichas;
    }

    /* 2) Una sola petición para las entidades que faltan. */
    $lote = implode('|', array_slice($faltan, 0, 20));
    $datos = consultar_api(
        'https://www.wikidata.org/w/api.php?action=wbgetentities&format=json'
        . '&ids=' . $lote . '&props=claims'
    );
    if (!$datos || empty($datos['entities'])) {
        return $fichas;
    }

    foreach ($datos['entities'] as $qid => $entidad) {
        $ficha = ['trailer' => '', 'poster' => ''];

        if (is_array($entidad) && !isset($entidad['missing'])) {
            /* --- Tráiler (P1651); preferimos el marcado como tráiler (P3831) --- */
            $preferido = '';
            $cualquiera = '';
            foreach (($entidad['claims']['P1651'] ?? []) as $reclamo) {
                $valor = $reclamo['mainsnak']['datavalue']['value'] ?? '';
                if (!is_string($valor) || $valor === '') {
                    continue;
                }
                if ($cualquiera === '') {
                    $cualquiera = $valor;
                }
                foreach (($reclamo['qualifiers']['P3831'] ?? []) as $cualificador) {
                    $tipo = $cualificador['mainsnak']['datavalue']['value']['id'] ?? '';
                    if ($tipo === 'Q622550') {
                        $preferido = $valor;
                    }
                }
            }
            $ficha['trailer'] = $preferido !== '' ? $preferido : $cualquiera;

            /* --- Imagen (P18): archivo alojado en Wikimedia Commons --- */
            $archivo = $entidad['claims']['P18'][0]['mainsnak']['datavalue']['value'] ?? '';
            if (is_string($archivo) && $archivo !== '') {
                $ficha['poster'] = 'https://commons.wikimedia.org/wiki/Special:FilePath/'
                    . rawurlencode(str_replace(' ', '_', $archivo)) . '?width=400';
            }
        }

        /* Se guarda también la entidad sin nada, para no volver a pedirla. */
        guardar_cache($conexion, 'entidad:' . $qid, $ficha, 604800);
        $fichas[$qid] = $ficha;
    }

    return $fichas;
}

/** Extrae el primer año (19xx/20xx) de un texto, o cadena vacía. */
function primer_anio($texto)
{
    if (preg_match('/\b((?:19|20)\d{2})\b/', $texto, $coincidencia)) {
        return $coincidencia[1];
    }
    return '';
}
