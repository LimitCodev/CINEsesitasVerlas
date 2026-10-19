/* =========================================================
   CINEsesitasVerlas - JavaScript
   Caso aplicativo de Ajax:
   1) Búsqueda de películas contra api/pelicula.php
      (Wikipedia/Wikidata sin clave, o TMDB si hay clave).
   2) Reproducción del tráiler oficial en un modal con
      iframe de YouTube (nocookie).
   3) Guardado de la película en la cartelera por Ajax.
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
  var formBuscar = document.getElementById('form-buscar');
  var entrada = document.getElementById('entrada-buscar');
  var caja = document.getElementById('caja-resultados');
  var cuenta = document.getElementById('cuenta-resultados');

  var modal = document.getElementById('modal-trailer');
  var iframe = document.getElementById('iframe-trailer');
  var tituloModal = document.getElementById('modal-trailer-titulo');
  var btnCerrar = document.getElementById('btn-cerrar-trailer');

  if (formBuscar) {
    formBuscar.addEventListener('submit', function (evento) {
      evento.preventDefault();
      buscarPeliculas();
    });
  }

  if (btnCerrar) {
    btnCerrar.addEventListener('click', cerrarTrailer);
  }
  if (modal) {
    modal.addEventListener('click', function (evento) {
      if (evento.target === modal) {
        cerrarTrailer();
      }
    });
  }
  document.addEventListener('keydown', function (evento) {
    if (evento.key === 'Escape' && modal && !modal.hidden) {
      cerrarTrailer();
    }
  });

  /* ============ Caso de Ajax 1: búsqueda de películas ============ */
  function buscarPeliculas() {
    var texto = entrada.value.trim();
    caja.innerHTML = '';

    if (texto.length < 2) {
      caja.innerHTML = '<p class="vacio">Escribe al menos 2 letras para buscar.</p>';
      if (cuenta) { cuenta.textContent = ''; }
      return;
    }

    caja.innerHTML = '<p class="vacio">Buscando «' + escapar(texto) + '»…</p>';
    if (cuenta) { cuenta.textContent = 'buscando…'; }

    fetch('api/pelicula.php?buscar=' + encodeURIComponent(texto))
      .then(function (respuesta) { return respuesta.json(); })
      .then(function (datos) {
        if (!datos.ok) {
          caja.innerHTML = '<p class="vacio">' + escapar(datos.mensaje || 'Error en la búsqueda.') + '</p>';
          if (cuenta) { cuenta.textContent = ''; }
          return;
        }
        pintarResultados(datos);
      })
      .catch(function () {
        caja.innerHTML = '<p class="vacio">No se pudo consultar el servicio. Revisa tu conexión a internet.</p>';
        if (cuenta) { cuenta.textContent = ''; }
      });
  }

  function pintarResultados(datos) {
    caja.innerHTML = '';

    if (!datos.peliculas.length) {
      caja.innerHTML = '<p class="vacio">Sin coincidencias. Prueba con otro título.</p>';
      if (cuenta) { cuenta.textContent = ''; }
      return;
    }

    if (cuenta) {
      cuenta.textContent = datos.peliculas.length + ' resultado(s) · ' +
        (datos.modo === 'tmdb' ? 'TMDB' : 'Wikipedia');
    }

    datos.peliculas.forEach(function (peli) {
      var tarjeta = document.createElement('article');
      tarjeta.className = 'tarjeta';

      /* Póster */
      var zonaPoster = document.createElement('div');
      zonaPoster.className = 'tarjeta-poster';
      if (peli.poster) {
        var img = document.createElement('img');
        img.src = peli.poster;
        img.alt = 'Póster de ' + peli.titulo;
        img.loading = 'lazy';
        zonaPoster.appendChild(img);
      } else {
        var inicial = document.createElement('span');
        inicial.className = 'poster-inicial';
        inicial.textContent = (peli.titulo || '?').charAt(0).toUpperCase();
        zonaPoster.appendChild(inicial);
      }
      var insignia = document.createElement('span');
      insignia.className = 'tarjeta-genero';
      insignia.textContent = peli.genero ? peli.genero.slice(0, 24) : (peli.anio || 'nuevo');
      zonaPoster.appendChild(insignia);
      tarjeta.appendChild(zonaPoster);

      /* Cuerpo */
      var cuerpo = document.createElement('div');
      cuerpo.className = 'tarjeta-cuerpo';

      var titulo = document.createElement('h2');
      titulo.textContent = peli.titulo;
      cuerpo.appendChild(titulo);

      var datos_lista = document.createElement('ul');
      datos_lista.className = 'tarjeta-datos';
      datos_lista.appendChild(fila('Año', peli.anio || '—'));
      datos_lista.appendChild(fila('Fuente', datos.modo === 'tmdb' ? 'TMDB' : 'Wikipedia'));
      cuerpo.appendChild(datos_lista);

      if (peli.sinopsis) {
        var sinopsis = document.createElement('p');
        sinopsis.className = 'tarjeta-sinopsis';
        sinopsis.textContent = peli.sinopsis;
        cuerpo.appendChild(sinopsis);
      }

      /* Acciones */
      var acciones = document.createElement('div');
      acciones.className = 'tarjeta-acciones';

      if (peli.trailer) {
        var btnTrailer = document.createElement('button');
        btnTrailer.type = 'button';
        btnTrailer.className = 'boton boton-chico boton-primario';
        btnTrailer.textContent = '▶ Ver tráiler';
        btnTrailer.addEventListener('click', function () {
          abrirTrailer(peli.trailer, peli.titulo);
        });
        acciones.appendChild(btnTrailer);
      }

      var btnGuardar = document.createElement('button');
      btnGuardar.type = 'button';
      btnGuardar.className = 'boton boton-chico';
      btnGuardar.textContent = 'Guardar en mi cartelera';
      btnGuardar.addEventListener('click', function () {
        guardarPelicula(peli, btnGuardar);
      });
      acciones.appendChild(btnGuardar);

      if (peli.enlace) {
        var enlace = document.createElement('a');
        enlace.className = 'boton boton-chico boton-secundario';
        enlace.href = peli.enlace;
        enlace.target = '_blank';
        enlace.rel = 'noopener';
        enlace.textContent = 'Ficha ↗';
        acciones.appendChild(enlace);
      }

      cuerpo.appendChild(acciones);
      tarjeta.appendChild(cuerpo);
      caja.appendChild(tarjeta);
    });
  }

  function fila(etiqueta, valor) {
    var li = document.createElement('li');
    var span = document.createElement('span');
    span.textContent = etiqueta;
    li.appendChild(span);
    li.appendChild(document.createTextNode(' ' + valor));
    return li;
  }

  /* ============ Caso de Ajax 2: tráiler en el modal ============ */
  function abrirTrailer(referencia, titulo) {
    if (!modal) { return; }

    /* Las referencias tmdb:NNN se resuelven contra api/pelicula.php */
    if (/^tmdb:\d+$/.test(referencia)) {
      tituloModal.textContent = 'Cargando tráiler…';
      modal.hidden = false;
      iframe.src = '';

      fetch('api/pelicula.php?trailer=' + encodeURIComponent(referencia))
        .then(function (respuesta) { return respuesta.json(); })
        .then(function (datos) {
          if (datos.ok && datos.trailer) {
            reproducir(datos.trailer, titulo);
          } else {
            tituloModal.textContent = 'Tráiler no disponible';
          }
        })
        .catch(function () {
          tituloModal.textContent = 'No se pudo cargar el tráiler';
        });
      return;
    }

    reproducir(referencia, titulo);
  }

  function reproducir(idYoutube, titulo) {
    tituloModal.textContent = 'Tráiler · ' + (titulo || '');
    iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(idYoutube) + '?autoplay=1';
    modal.hidden = false;
  }

  function cerrarTrailer() {
    if (!modal) { return; }
    modal.hidden = true;
    iframe.src = ''; /* detiene la reproducción */
  }

  /* ============ Caso de Ajax 3: guardar en la cartelera ============ */
  function guardarPelicula(peli, boton) {
    boton.disabled = true;
    boton.textContent = 'Guardando…';

    var cuerpo = new URLSearchParams();
    cuerpo.append('accion', 'guardar_ajax');
    cuerpo.append('titulo', peli.titulo || '');
    cuerpo.append('anio', peli.anio || '');
    cuerpo.append('genero', (peli.genero || '').slice(0, 80));
    cuerpo.append('sinopsis', peli.sinopsis || '');
    cuerpo.append('poster', peli.poster || '');
    cuerpo.append('trailer', /^tmdb:\d+$/.test(peli.trailer) ? '' : (peli.trailer || ''));
    cuerpo.append('enlace', peli.enlace || '');
    cuerpo.append('fuente', peli.fuente || 'wikipedia');

    fetch('acciones.php', { method: 'POST', body: cuerpo })
      .then(function (respuesta) { return respuesta.json(); })
      .then(function (resultado) {
        boton.textContent = resultado.ok ? resultado.mensaje : resultado.mensaje;
        if (resultado.ok) {
          boton.classList.add('boton-exito');
        } else {
          boton.disabled = false;
        }
      })
      .catch(function () {
        boton.disabled = false;
        boton.textContent = 'Error al guardar, intenta de nuevo';
      });
  }

  /* Escapado mínimo para los textos que se insertan con innerHTML. */
  function escapar(texto) {
    var div = document.createElement('div');
    div.textContent = texto || '';
    return div.innerHTML;
  }
});
