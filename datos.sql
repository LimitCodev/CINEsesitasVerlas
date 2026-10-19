-- =========================================================
--  CINEsesitasVerlas - Base de datos
--  Importar desde phpMyAdmin (opcional: el sistema también
--  crea la base y la tabla automáticamente).
--
--  La cartelera inicia vacía a propósito: las películas se
--  agregan desde el buscador con datos reales (Wikipedia,
--  Wikidata o TMDB). No se incluyen datos de ejemplo.
-- =========================================================

CREATE DATABASE IF NOT EXISTS cinesitas
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE cinesitas;

CREATE TABLE IF NOT EXISTS peliculas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(200) NOT NULL,
  anio VARCHAR(10) NOT NULL DEFAULT '',
  genero VARCHAR(80) NOT NULL DEFAULT '',
  sinopsis TEXT,
  poster VARCHAR(500) NOT NULL DEFAULT '',
  trailer VARCHAR(30) NOT NULL DEFAULT '',
  enlace VARCHAR(500) NOT NULL DEFAULT '',
  fuente VARCHAR(20) NOT NULL DEFAULT 'wikipedia',
  estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
  notas VARCHAR(600) NOT NULL DEFAULT '',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Caché de las consultas a Wikipedia/Wikidata/TMDB.
-- Wikipedia solo admite ~10 peticiones por ventana y luego bloquea 45 s,
-- así que una búsqueda repetida se responde desde aquí sin volver a
-- consultar la API. Se llena solo, con datos reales; nunca con ejemplos.
CREATE TABLE IF NOT EXISTS cache_externa (
  clave VARCHAR(190) NOT NULL,
  datos MEDIUMTEXT NOT NULL,
  expira DATETIME NOT NULL,
  PRIMARY KEY (clave),
  KEY idx_cache_expira (expira)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
