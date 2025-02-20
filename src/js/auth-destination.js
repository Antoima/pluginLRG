$(document).ready(function () {
  // Escuchar mensajes desde auth-destination.php
  window.addEventListener("message", (event) => {
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
  });

  // Autenticar cuenta de destino
  $("#authDestinationBtn").click(() => {
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
  });
});
