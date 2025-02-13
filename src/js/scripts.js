$(document).ready(function () {
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
  actualizarPuertos();

  // Deshabilitar los campos de entrada "Usuario" y "Contraseña"
  $("#usuario").prop("disabled", true);
  $("#contraseña").prop("disabled", true);

  $("#smtpForm").on("submit", function (event) {
    event.preventDefault();

    // Validar datos del formulario
    const host = $("#host").val();
    const puerto = $("#puerto").val();
    if (!host || !puerto) {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Por favor, completa todos los campos.",
      });
      return;
    }

    $("#loading").fadeIn();
    $.ajax({
      url: "https://pl.luisguevara.net/includes/HP.php",
      type: "POST",
      data: $(this).serialize(),
      success: function (response) {
        $("#loading").fadeOut();
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
            text:
              result.message ||
              "Hubo un problema al intentar conectar con el servidor SMTP. Por favor, revisa los datos e inténtalo de nuevo.",
          });
        }
      },
      error: function (xhr, status, error) {
        $("#loading").fadeOut();
        console.error("Error en la conexión SMTP:", xhr.responseText);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Hubo un problema al intentar conectar con el servidor SMTP. Por favor, revisa los datos e inténtalo de nuevo.",
        });
      },
    });
  });

  $("#checkGoogleConnection").click(function () {
    $("#googleButtonText").addClass("d-none");
    $("#googleButtonSpinner").removeClass("d-none");

    Swal.fire({
      title: "Comprobando conexión...",
      text: "Por favor, espera mientras comprobamos la conexión con Google.",
      icon: "info",
      showConfirmButton: false,
      allowOutsideClick: false,
    });

    const redirectUri = "https://pl.luisguevara.net/";
    const scope =
      "https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/userinfo.email";
    const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${googleClientId}&redirect_uri=${redirectUri}&response_type=token&scope=${scope}`;

    window.location.href = authUrl;
  });

  function handleOAuthRedirect() {
    const hash = window.location.hash;
    if (hash) {
      const params = new URLSearchParams(hash.substring(1));
      const accessToken = params.get("access_token");
      if (accessToken) {
        // Almacenar el token en localStorage
        localStorage.setItem("access_token", accessToken);

        // Redirigir sin el token en la URL
        window.location.href = "sendEmail.php";
      }
    }
  }

  handleOAuthRedirect();
});
