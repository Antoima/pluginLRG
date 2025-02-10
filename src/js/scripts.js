//scripts.js
$(document).ready(function () {
  // Mostrar el spinner de carga cuando la página se está cargando
  $(window).on("load", function () {
    $("#loading").fadeOut();
  });

  const puertosPorHost = {
    "smtp.gmail.com": ["587 (TLS)", "465 (SSL)"],
    "smtp.office365.com": ["587 (TLS)"],
    "smtp.mail.yahoo.com": ["587 (TLS)", "465 (SSL)"],
    "mail.smtp2go.com": ["2525 (TLS)", "587 (TLS)", "465 (SSL)"],
  };

  function actualizarPuertos() {
    const hostSeleccionado = $("#host").val();
    const puertos = puertosPorHost[hostSeleccionado] || [];
    const puertoSelect = $("#puerto");
    puertoSelect.empty();
    puertos.forEach((puerto) => {
      puertoSelect.append(new Option(puerto, puerto.split(" ")[0]));
    });
  }

  $("#host").on("change", actualizarPuertos);
  actualizarPuertos(); // Llamar al cargar la página para establecer los puertos iniciales

  $("#smtpForm").on("submit", function (event) {
    event.preventDefault();
    $("#loading").fadeIn(); // Mostrar el spinner de carga durante la prueba de conexión SMTP
    $.ajax({
      url: "https://pl.luisguevara.net/includes/HP.php",
      type: "POST",
      data: $(this).serialize(),
      success: function (response) {
        $("#loading").fadeOut(); // Ocultar el spinner de carga cuando la prueba haya terminado
        const result = JSON.parse(response);
        if (result.status === "success") {
          console.log("Conexión SMTP exitosa:", result.message);
          Swal.fire({
            icon: "success",
            title: "Éxito",
            text: result.message,
          });
        } else {
          console.error("Error en la conexión SMTP:", result.message);
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Hubo un problema al intentar conectar con el servidor SMTP. Por favor, revisa los datos e inténtalo de nuevo. come azucar",
          });
        }
      },
      error: function (xhr, status, error) {
        $("#loading").fadeOut(); // Ocultar el spinner de carga cuando la prueba haya terminado
        console.error("Error en la conexión SMTP:", xhr.responseText);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Hubo un problema al intentar conectar con el servidor SMTP. Por favor, revisa los datos e inténtalo de nuevo. mmgv XD",
        });
      },
    });
  });
});
