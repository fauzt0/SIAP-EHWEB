(function() {
  // Guardamos la referencia original de fetch
  const originalFetch = window.fetch;

  // Redefinimos la función fetch
  window.fetch = async function(resource, init) {
      init = init || {};
      init.headers = init.headers || {};

      // Añadir la cabecera CSRF si el método es POST
      if ((init.method || 'GET').toUpperCase() === 'POST' ) {
          const csrfField = document.querySelector('#csrf-field');
          if (csrfField) {
              init.headers[csrfField.getAttribute('name')] = csrfField.getAttribute('content');
          }
      }

      // Realizamos la solicitud
      const response = await originalFetch(resource, init);

      // Actualizamos el token CSRF tras la solicitud
      if ((init.method || 'GET').toUpperCase() === 'POST') {
          const csrfField = document.querySelector('#csrf-field');
          const csrfToken = response.headers.get(csrfField.getAttribute('name'));
          if (csrfField && csrfToken) {
              csrfField.setAttribute('content', csrfToken);
          }
      }

      return response;
  };
})();


$(document).on("ajaxSend", function (event, jqXHR, ajaxOptions) {
  console.log("ajaxSend", ajaxOptions.type);
  ///obtenemos el token CSRF de la cabecera por su id 
  const csrfMetaTag = document.querySelector('#csrf-field');
  // $("meta[name='csrf-token']");
  const csrfToken = csrfMetaTag.attr("content");
  const csrfTokenName = csrfMetaTag.attr("name");

  if (ajaxOptions.type?.toLowerCase() === "post" || ajaxOptions.type?.toLowerCase() === "get") {
    jqXHR.setRequestHeader(csrfTokenName, csrfToken);
  }
});

$(document).on("ajaxComplete", function (event, jqXHR, ajaxOptions) {
  const csrfMetaTag = ocument.querySelector('#csrf-field');
  const csrfTokenName = csrfMetaTag.attr("name");
  const newCsrfToken = jqXHR.getResponseHeader(csrfTokenName);

  if (newCsrfToken) {
    csrfMetaTag.attr("content", newCsrfToken);
  }
});



