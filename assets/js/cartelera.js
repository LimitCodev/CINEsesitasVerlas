/* =========================================================
   CINEsesitasVerlas - JavaScript
   Caso aplicativo de Ajax: consulta a la API de TVMaze para
   importar los datos de una película al formulario de registro
   sin recargar la página.
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
  var boton = document.getElementById('btn-buscar-tvmaze');
  var entrada = document.getElementById('entrada-tvmaze');
  var caja = document.getElementById('resultados-tvmaze');

  if (!boton || !entrada || !caja) {
    return;
  }

  boton.addEventListener('click', buscarPeliculas);
  entrada.addEventListener('keydown', function (evento) {
    if (evento.key === 'Enter') {
      evento.preventDefault();
      buscarPeliculas();
    }
  });

  /* ---------- Petición Ajax a TVMaze ---------- */
  function buscarPeliculas() {
    var texto = entrada.value.trim();
    caja.innerHTML = '';

    if (texto.length < 2) {
      caja.innerHTML = '<p class="mensaje-api mensaje-aviso">Escribe al menos 2 letras para buscar.</p>';
      return;
    }

    caja.innerHTML = '<p class="mensaje-api">Buscando en TVMaze…</p>';

    fetch('https://api.tvmaze.com/search/shows?q=' + encodeURIComponent(texto))
      .then(function (respuesta) {
        if (!respuesta.ok) {
          throw new Error('HTTP ' + respuesta.status);
        }
        return respuesta.json();
      })
      .then(function (datos) {
        pintarResultados(datos);
      })
      .catch(function (error) {
        caja.innerHTML =
          '<p class="mensaje-api mensaje-error">No se pudo consultar TVMaze. ' +
          'Revisa tu conexión a internet. (' + error.message + ')</p>';
      });
  }

  /* ---------- Pintar las tarjetas de resultados ---------- */
  function pintarResultados(datos) {
    caja.innerHTML = '';

    if (!datos.length) {
      caja.innerHTML = '<p class="mensaje-api mensaje-aviso">Sin coincidencias en TVMaze.</p>';
      return;
    }

    var lista = document.createElement('div');
    lista.className = 'lista-resultados';

    datos.slice(0, 6).forEach(function (item) {
      var pelicula = item.show;
      var fila = document.createElement('div');
      fila.className = 'resultado-fila';

      var info = document.createElement('div');
      info.className = 'resultado-info';

      var nombre = document.createElement('strong');
      nombre.textContent = pelicula.name;

      var detalle = document.createElement('span');
      var anio = pelicula.premiered ? pelicula.premiered.slice(0, 4) : 'sin fecha';
      var generos = pelicula.genres && pelicula.genres.length
        ? pelicula.genres.join(', ')
        : 'sin género';
      detalle.textContent = anio + ' · ' + generos;

      info.appendChild(nombre);
      info.appendChild(detalle);

      var botonUsar = document.createElement('button');
      botonUsar.type = 'button';
      botonUsar.className = 'boton boton-chico boton-primario';
      botonUsar.textContent = 'Usar datos';
      botonUsar.addEventListener('click', function () {
        copiarAlFormulario(pelicula);
      });

      fila.appendChild(info);
      fila.appendChild(botonUsar);
      lista.appendChild(fila);
    });

    caja.appendChild(lista);
  }

  /* ---------- Copiar los datos al formulario ---------- */
  function copiarAlFormulario(pelicula) {
    completarCampo('titulo', pelicula.name);

    if (pelicula.genres && pelicula.genres.length) {
      completarCampo('genero', pelicula.genres[0]);
    }
    if (pelicula.image) {
      var poster = pelicula.image.original || pelicula.image.medium;
      completarCampo('poster', poster);
    }
    if (pelicula.summary) {
      var cajaTexto = document.createElement('div');
      cajaTexto.innerHTML = pelicula.summary; // solo para quitar las etiquetas HTML
      completarCampo('sinopsis', cajaTexto.textContent.trim());
    }

    caja.innerHTML =
      '<p class="mensaje-api mensaje-exito">Datos copiados al formulario. ' +
      'Completa sala, horario y precio, y guarda la función.</p>';
  }

  function completarCampo(id, valor) {
    var campo = document.getElementById(id);
    if (campo && valor) {
      campo.value = valor;
    }
  }
});
