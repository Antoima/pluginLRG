$(document).ready(function () {
  const accessToken = localStorage.getItem("access_token");
  let destinationAccessToken = localStorage.getItem("destination_access_token");

  // Función para obtener la información del usuario autenticado
  function getUserInfo(accessToken) {
    return $.ajax({
      url: "https://www.googleapis.com/oauth2/v1/userinfo?alt=json",
      type: "GET",
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    });
  }

  // Función para verificar y renovar el token de acceso
  async function getValidAccessToken() {
    let accessToken = localStorage.getItem("access_token");
    const refreshToken = localStorage.getItem("refresh_token"); // ✅ Definir refreshToken

    if (!accessToken) {
      throw new Error("No hay token de acceso disponible.");
    }

    try {
      const userInfo = await fetch(
        "https://www.googleapis.com/oauth2/v1/userinfo?alt=json",
        {
          headers: {
            Authorization: `Bearer ${accessToken}`,
          },
        }
      );

      if (!userInfo.ok) {
        throw new Error(`Error ${userInfo.status}: ${userInfo.statusText}`);
      }

      return accessToken;
    } catch (error) {
      console.log("Error al validar token:", error);

      // Si el token ha expirado, renovarlo
      if (refreshToken) {
        const response = await fetch("https://oauth2.googleapis.com/token", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({
            client_id: GOOGLE_CLIENT_ID,
            client_secret: GOOGLE_CLIENT_SECRET,
            refresh_token: refreshToken,
            grant_type: "refresh_token",
          }),
        });

        const data = await response.json();
        if (data.access_token) {
          accessToken = data.access_token;
          localStorage.setItem("access_token", accessToken); // Guarda el nuevo access_token
          return accessToken;
        } else {
          throw new Error("No se pudo renovar el token de acceso.");
        }
      } else {
        throw new Error(
          "El token ha expirado y no hay refresh_token disponible."
        );
      }
    }
  }

  // Función para mostrar el progreso de la migración
  function showProgress() {
    $(".progress").show();
    const progressBar = $(".progress-bar");

    const checkProgress = setInterval(() => {
      $.get("check_progress.php", (progress) => {
        progress = parseInt(progress); // Asegúrate de que sea un número
        progressBar.css("width", progress + "%").text(progress + "%");

        if (progress >= 100) {
          clearInterval(checkProgress);
        }
      });
    }, 1000); // Verifica cada 1 segundo
  }

  // Función para manejar el envío del formulario de migración
  function handleMigrationSubmit(event) {
    event.preventDefault();

    const destinationEmail = $("#destinationEmail").val();
    const destinationAccessToken = localStorage.getItem(
      "destination_access_token"
    );

    if (destinationEmail && !destinationAccessToken) {
      Swal.fire("Error", "Primero autentica la cuenta de destino.", "error");
      return;
    }

    showProgress();

    // Enviar el token de acceso en los datos del formulario
    const formData = {
      sourceEmail: $("#sourceEmail").val(),
      destinationEmail: destinationEmail,
      accessToken: localStorage.getItem("access_token"), // Token de la cuenta de origen
      destinationAccessToken: destinationAccessToken, // Token de la cuenta de destino
    };

    $.ajax({
      url: "process_backup_migration.php",
      type: "POST",
      data: formData,
      success: (response) => {
        const progressBar = $(".progress-bar");
        clearInterval(progressBar);
        progressBar.css("width", "100%").text("100%");
        Swal.fire(
          response.status === "success" ? "Éxito" : "Error",
          response.message,
          response.status
        );
        if (response.status === "success") $("#downloadBackup").show();
      },
      error: () => {
        clearInterval(checkProgress);
        Swal.fire("Error", "Error de conexión con el servidor.", "error");
      },
    });
  }

  // Función para autenticar la cuenta de destino
  function authenticateDestination() {
    const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?
        client_id=${GOOGLE_CLIENT_ID}&
        redirect_uri=${encodeURIComponent(
          "https://pl.luisguevara.net/auth-destination.php"
        )}&
        response_type=token&
        scope=email%20openid%20https://www.googleapis.com/auth/gmail.readonly%20https://www.googleapis.com/auth/gmail.modify%20https://www.googleapis.com/auth/gmail.send&
        state=destination&
        prompt=select_account`.replace(/\s+/g, "");

    window.open(authUrl, "authPopup", "width=600,height=600");
  }

  // Función para manejar el mensaje de autenticación
  function handleAuthenticationMessage(event) {
    if (event.data.action === "destinationAuthenticated") {
      const email2 = event.data.email; // Obtener el correo electrónico
      console.log("Correo autenticado:", email2);
      $("#destinationEmail").addClass("authenticated").val(email2); // Mostrar el correo electrónico
      $("#authDestinationBtn").prop("disabled", true).text("Autenticado");
      localStorage.setItem("destination_access_token", event.data.accessToken);

      // Mostrar notificación con SweetAlert2
      Swal.fire({
        icon: "success",
        title: "Autenticación exitosa",
        text: `Cuenta ${email2} autenticada correctamente.`,
        timer: 3000,
        showConfirmButton: false,
      });
    }

    if (event.data.action === "authError") {
      Swal.fire("Error de autenticación", event.data.error, "error");
    }
  }

  // Obtener el correo de origen con el token
  getValidAccessToken()
    .then((accessToken) => {
      console.log("Token renovado:", accessToken); // ✅ Depuración
      return getUserInfo(accessToken);
    })
    .then((response) => {
      console.log("Respuesta de Google:", response); // ✅ Depuración
      $("#sourceEmail").val(response.email);
    })
    .catch((error) => {
      console.error("Error crítico:", error); // ✅ Depuración
      Swal.fire(
        "Error",
        "No se pudo obtener la información del usuario.",
        "error"
      );
    });

  // Enviar formulario de migración
  $("#backupMigrationForm").on("submit", handleMigrationSubmit);

  // Escuchar mensajes desde auth-destination.php
  window.addEventListener("message", handleAuthenticationMessage);

  // Autenticar cuenta de destino
  $("#authDestinationBtn").click(authenticateDestination);

  // Función para verificar el token de acceso en el servidor
  function verifyAccessToken(accessToken) {
    $.ajax({
      url: "verify_token.php", // Ruta a tu archivo PHP
      type: "POST",
      data: {
        accessToken: accessToken, // Enviar el token de acceso al servidor
      },
      success: function (response) {
        console.log(response); // Mostrar la respuesta del servidor
        // Aquí puedes manejar la respuesta del servidor y mostrar un mensaje al usuario
        if (response.includes("Token válido")) {
          Swal.fire("Éxito", "El token es válido.", "success");
        } else {
          Swal.fire("Error", response, "error");
        }
      },
      error: function (xhr, status, error) {
        Swal.fire(
          "Error",
          "Hubo un problema con la conexión al servidor.",
          "error"
        );
      },
    });
  }

  // Llamar a la función para verificar el token (puedes hacerlo cuando sea necesario)
  if (accessToken) {
    verifyAccessToken(accessToken);
  }
});
