$(document).ready(function () {
  const puertosPorHost = {
    "smtp.gmail.com": ["587 (TLS)", "465 (SSL)"],
    "smtp.office365.com": ["587 (TLS)"],
    "smtp.mail.yahoo.com": ["587 (TLS)", "465 (SSL)"],
    "mail.smtp2go.com": ["2525 (TLS)", "587 (TLS)", "465 (SSL)"],
  };

  // Actualizar los puertos disponibles según el host seleccionado
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

  // Enviar formulario SMTP
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

  // Autenticación con Google OAuth 2.0
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
      "https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.modify https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/userinfo.email openid";
    const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${googleClientId}&redirect_uri=${redirectUri}&response_type=token&scope=${scope}`;

    window.location.href = authUrl;
  });

  // Manejar la redirección de OAuth
  function handleOAuthRedirect() {
    const hash = window.location.hash;
    if (hash) {
      const params = new URLSearchParams(hash.substring(1));
      const accessToken = params.get("access_token");
      const error = params.get("error");

      if (error) {
        Swal.fire({
          title: "Error de autenticación",
          text: "No se pudo autenticar con Google. Por favor, inténtalo de nuevo.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
        return;
      }

      if (accessToken) {
        localStorage.setItem("access_token", accessToken);
        // Obtener información del usuario de Google
        $.ajax({
          url: "https://www.googleapis.com/oauth2/v1/userinfo?alt=json",
          type: "GET",
          headers: {
            Authorization: `Bearer ${accessToken}`,
          },
          success: function (response) {
            console.log("Información del usuario:", response);

            Swal.fire({
              title: "Conexión exitosa",
              html: `
                <p><strong>Foto de perfil:</strong> <img src="${
                  response.picture
                }" alt="Foto de perfil" style="border-radius: 50%; width: 50px; height: 50px;"></p>
                <p><strong>Correo electrónico:</strong> ${response.email}</p>
                <p><strong>Correo verificado:</strong> ${
                  response.verified_email ? "Sí" : "No"
                }</p>
              `,
              icon: "success",
              confirmButtonText: "Continuar",
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = "sendEmail.php";
              }
            });
          },
          error: function (xhr, status, error) {
            console.error(
              "Error al obtener la información del usuario:",
              error
            );
            Swal.fire({
              title: "Error",
              text: "Hubo un problema al obtener la información del usuario.",
              icon: "error",
              confirmButtonText: "Aceptar",
            }).then(() => {
              window.location.href = "sendEmail.php";
            });
          },
        });
      }
    }
  }

  // Renovar el token de acceso si ha expirado
  async function refreshAccessToken(refreshToken) {
    const response = await fetch("https://oauth2.googleapis.com/token", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        client_id: googleClientId,
        client_secret: "TU_CLIENT_SECRET", // Reemplaza con tu client_secret
        refresh_token: refreshToken,
        grant_type: "refresh_token",
      }),
    });

    const data = await response.json();
    return data.access_token;
  }

  // Obtener un token de acceso válido
  async function getValidAccessToken() {
    let accessToken = localStorage.getItem("access_token");
    const refreshToken = localStorage.getItem("refresh_token");

    if (!accessToken) {
      throw new Error("No hay token de acceso disponible.");
    }

    // Verificar si el token ha expirado (puedes usar una librería como jwt-decode para esto)
    const tokenExpired = true; // Cambia esto por una verificación real
    if (tokenExpired) {
      accessToken = await refreshAccessToken(refreshToken);
      localStorage.setItem("access_token", accessToken);
    }

    return accessToken;
  }

  // Limpiar localStorage al cerrar sesión
  function clearLocalStorage() {
    localStorage.removeItem("access_token");
    localStorage.removeItem("refresh_token");
  }

  // Ejemplo de uso al cerrar sesión
  $("#logoutButton").click(function () {
    clearLocalStorage();
    window.location.href = "logout.php";
  });

  // Manejar la redirección de OAuth al cargar la página
  handleOAuthRedirect();
});
