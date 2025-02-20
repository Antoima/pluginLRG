$(document).ready(function () {
  const accessToken = localStorage.getItem("access_token");
  let destinationAccessToken = localStorage.getItem("destination_access_token");

  // Escuchar mensajes desde auth-destination.php
  window.addEventListener("message", (event) => {
    if (event.data.action === "destinationAuthenticated") {
      const email = event.data.email;
      $("#destinationEmail").addClass("authenticated").val(email);
      $("#authDestinationBtn").prop("disabled", true).text("Autenticado");
      localStorage.setItem("destination_access_token", event.data.accessToken);

      // Mostrar notificación con SweetAlert2
      Swal.fire({
        icon: "success",
        title: "Autenticación exitosa",
        text: `Cuenta ${email} autenticada correctamente.`,
        timer: 3000,
        showConfirmButton: false,
      });
    }
  });

  // Obtener el correo del usuario autenticado
  function getUserInfo(accessToken) {
    return $.ajax({
      url: "https://www.googleapis.com/oauth2/v1/userinfo?alt=json",
      type: "GET",
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    });
  }

  // Verificar y renovar el token si es necesario
  async function getValidAccessToken() {
    let accessToken = localStorage.getItem("access_token");
    const refreshToken = localStorage.getItem("refresh_token");

    if (!accessToken) {
      throw new Error("No hay token de acceso disponible.");
    }

    // Verificar si el token es válido
    try {
      await getUserInfo(accessToken);
      return accessToken;
    } catch (error) {
      // Si el token ha expirado, renovarlo
      if (refreshToken) {
        const response = await fetch("https://oauth2.googleapis.com/token", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({
            client_id: GOOGLE_CLIENT_ID, // <-- Usar variable global
            client_secret: GOOGLE_CLIENT_SECRET, // <-- Usar variable global
            refresh_token: refreshToken,
            grant_type: "refresh_token",
          }),
        });

        const data = await response.json();
        accessToken = data.access_token;
        localStorage.setItem("access_token", accessToken);
        return accessToken;
      } else {
        throw new Error(
          "El token ha expirado y no hay refresh_token disponible."
        );
      }
    }
  }

  // Autocompletar correo de origen
  getValidAccessToken()
    .then((accessToken) => {
      return getUserInfo(accessToken);
    })
    .then((response) => {
      $("#sourceEmail").val(response.email);
    })
    .catch((error) => {
      Swal.fire(
        "Error",
        "No se pudo obtener la información del usuario.",
        "error"
      );
      console.error("Error:", error);
    });

  // Autenticar cuenta de destino
  $("#authDestinationBtn").click(() => {
    const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?
        client_id=${GOOGLE_CLIENT_ID}&
        redirect_uri=${encodeURIComponent(
          "https://pl.luisguevara.net/auth-destination.php"
        )}&
        response_type=token&
        scope=https://www.googleapis.com/auth/gmail.send&
        state=destination&
        prompt=select_account`.replace(/\s+/g, "");

    window.open(authUrl, "authPopup", "width=600,height=600");
  });

  // Verificar si ya hay token de destino
  if (destinationAccessToken) {
    $("#destinationEmail")
      .addClass("authenticated")
      .val("Cuenta autenticada ✔️");
    $("#authDestinationBtn").prop("disabled", true);
  }

  // Enviar formulario
  $("#backupMigrationForm").on("submit", function (e) {
    e.preventDefault();
    const destinationEmail = $("#destinationEmail").val();
    const destinationAccessToken = localStorage.getItem(
      "destination_access_token"
    );

    if (destinationEmail && !destinationAccessToken) {
      Swal.fire("Error", "Primero autentica la cuenta de destino.", "error");
      return;
    }

    $(".progress").show();
    const progressBar = $(".progress-bar");

    // Función para sondear el progreso
    const checkProgress = setInterval(() => {
      $.get("check_progress.php", (progress) => {
        progressBar.css("width", progress + "%").text(progress + "%");
      });
    }, 1000);

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
        clearInterval(checkProgress);
        progressBar.css("width", "100%").text("100%");
        const result = JSON.parse(response);
        Swal.fire(
          result.status === "success" ? "Éxito" : "Error",
          result.message,
          result.status
        );
        if (result.status === "success") $("#downloadBackup").show();
      },
      error: () => {
        clearInterval(checkProgress);
        Swal.fire("Error", "Error de conexión con el servidor.", "error");
      },
    });
  });
  window.addEventListener("message", (event) => {
    if (event.data.action === "authError") {
      Swal.fire("Error de autenticación", event.data.error, "error");
    }
  });
});
