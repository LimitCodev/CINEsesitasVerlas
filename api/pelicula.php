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

/* Si hay clave de TMDB se usa ese proveedor; si no, Wikipedia + Wikidata. */
if (TMDB_API_KEY !== '') {
    $peliculas = buscar_en_tmdb($buscar);
    if ($peliculas !== null) {
        responder_json(['ok' => true, 'modo' => 'tmdb', 'peliculas' => $peliculas]);
    }
}

$peliculas = buscar_en_wikipedia($buscar);

/* null = la API externa no respondió (límite de consultas o caída).
   No lo presentamos como «sin resultados»: avisamos al usuario. */
if ($peliculas === null) {
    responder_json([
        'ok' => false,
        'mensaje' => 'Wikipedia no responde ahora (límite de consultas); intenta en unos segundos.',
    ]);
}

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

function buscar_en_wikipedia($texto)
{
    /* 1) Lista de artículos que coinciden con la búsqueda. Pedimos 16 porque
          los artículos de películas a veces quedan rankeados más abajo (el de
          «Doom» está en el puesto 10). */
    $busqueda = consultar_api(
        'https://es.wikipedia.org/w/api.php?action=query&format=json&utf8=1'
        . '&list=search&srsearch=' . urlencode($texto) . '&srlimit=16'
    );
    /* Distinguimos «la API no respondió» (null) de «no hubo coincidencias» ([]). */
    if (!$busqueda) {
        return null;
    }
    if (empty($busqueda['query']['search'])) {
        return [];
    }

    /* Guardamos el orden de relevancia del search para no perderlo después. */
    $ordenBusqueda = [];
    $titulos = [];
    foreach ($busqueda['query']['search'] as $indice => $item) {
        $titulos[] = $item['title'];
        $ordenBusqueda[$item['title']] = $indice;
    }

    /* 2) Resumen, descripción, miniatura y QID de Wikidata de cada artículo. */
    $codificados = implode('|', array_map('rawurlencode', $titulos));
    $fichas = consultar_api(
        'https://es.wikipedia.org/w/api.php?action=query&format=json&utf8=1&redirects=1'
        . '&prop=pageprops|extracts|description|pageimages'
        . '&ppprop=wikibase_item&exintro=1&explaintext=1&exlimit=max'
        . '&piprop=thumbnail&pithumbsize=400&titles=' . $codificados
    );
    if (!$fichas) {
        return null;
    }
    if (empty($fichas['query']['pages'])) {
        return [];
    }

    /* Mapa título del search → título final (por redirecciones/normalización)
          para poder recuperar el índice de relevancia de cada página. */
    $tituloOriginal = [];
    foreach (['normalized', 'redirects'] as $tipo) {
        foreach (($fichas['query'][$tipo] ?? []) as $cambio) {
            $tituloOriginal[$cambio['to']] = $cambio['from'];
        }
    }

    $fichasPorQid = [];
    $qids = [];
    foreach ($fichas['query']['pages'] as $pagina) {
        if (isset($pagina['missing'])) {
            continue;
        }
        $titulo = $pagina['title'] ?? '';
        $sinopsis = trim($pagina['extract'] ?? '');
        if ($titulo === '' || $sinopsis === '') {
            continue;
        }
        /* Índice de relevancia en el search (los desconocidos van al final). */
        $original = $tituloOriginal[$titulo] ?? $titulo;
        $orden = $ordenBusqueda[$original] ?? ($ordenBusqueda[$titulo] ?? 999);
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

    /* 3) Tráileres: propiedad P1651 de Wikidata (preferimos el calificado
          como «tráiler», P3831 = Q622550, y si no hay usamos el primero). */
    $trailers = trailer_de_wikidata(array_keys($qids));

    /* 4) Armo la lista: primero lo que parece película, después el resto.
          Cada grupo conserva el orden de relevancia del search (hallazgo 3).
          El /u es imprescindible: sin él [íi] casa bytes y «película» nunca
          coincide en UTF-8 (hallazgo 1). */
    $peliculas = [];
    $otros = [];
    foreach ($fichasPorQid as $clave => $ficha) {
        $coincideCine = preg_match(
            '/pel[íi]cula|film|largometraje|cine/iu',
            $ficha['descripcion'] . ' ' . mb_substr($ficha['sinopsis'], 0, 160)
        );
        $item = [
            'fuente' => 'wikipedia',
            'id' => $ficha['qid'] !== '' ? $ficha['qid'] : $ficha['titulo'],
            'titulo' => $ficha['titulo'],
            'anio' => primer_anio($ficha['descripcion'] . ' ' . mb_substr($ficha['sinopsis'], 0, 200)),
            'genero' => $ficha['descripcion'],
            'sinopsis' => mb_substr($ficha['sinopsis'], 0, 420),
            'poster' => $ficha['poster'],
            'trailer' => $trailers[$clave] ?? ($ficha['qid'] !== '' ? ($trailers[$ficha['qid']] ?? '') : ''),
            'enlace' => 'https://es.wikipedia.org/wiki/' . rawurlencode(str_replace(' ', '_', $ficha['titulo'])),
            'orden' => $ficha['orden'],
        ];
        if ($coincideCine) {
            $peliculas[] = $item;
        } else {
            $otros[] = $item;
        }
    }

    /* Ordena cada grupo por la posición original en el search. */
    $porRelevancia = function ($a, $b) {
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

/** Devuelve [qid => id_de_video_de_youtube] para los QIDs dados. */
function trailer_de_wikidata($qids)
{
    if (empty($qids)) {
        return [];
    }
    $lote = implode('|', array_slice($qids, 0, 20));
    $datos = consultar_api(
        'https://www.wikidata.org/w/api.php?action=wbgetentities&format=json'
        . '&ids=' . $lote . '&props=claims'
    );
    if (!$datos || empty($datos['entities'])) {
        return [];
    }

    $trailers = [];
    foreach ($datos['entities'] as $qid => $entidad) {
        $reclamos = $entidad['claims']['P1651'] ?? [];
        $preferido = '';
        $cualquiera = '';
        foreach ($reclamos as $reclamo) {
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
        $trailer = $preferido !== '' ? $preferido : $cualquiera;
        if ($trailer !== '') {
            $trailers[$qid] = $trailer;
        }
    }
    return $trailers;
}

/** Extrae el primer año (19xx/20xx) de un texto, o cadena vacía. */
function primer_anio($texto)
{
    if (preg_match('/\b((?:19|20)\d{2})\b/', $texto, $coincidencia)) {
        return $coincidencia[1];
    }
    return '';
}
