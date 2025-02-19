$(document).ready(function () {
  // Obtener el token de acceso de la URL
  const params = new URLSearchParams(window.location.search);
  const accessToken = params.get("access_token");

  // Manejar el envío del formulario
  $("#sendEmailForm").on("submit", function (event) {
    event.preventDefault();

    // Obtener el token de reCAPTCHA v3
    grecaptcha.ready(function () {
      grecaptcha
        .execute(recaptchaSiteKey, {
          // Usar la variable JS
          action: "submit",
        })
        .then(function (token) {
          const to = $("#to").val();
          const subject = $("#subject").val();
          const body = $("#body").val();

          // Verificar reCAPTCHA en el servidor
          $.post(
            "verifyRecaptcha.php",
            { recaptchaResponse: token },
            function (data) {
              const response = JSON.parse(data);
              if (response.success) {
                sendEmail(accessToken, to, subject, body);
              } else {
                $("#recaptchaError")
                  .text("La verificación de reCAPTCHA falló.")
                  .show();
                console.error("Error de reCAPTCHA:", response.message);
              }
            }
          );
        });
    });
  });

  // Función para enviar un correo electrónico
  function sendEmail(accessToken, to, subject, body) {
    const email = [
      `To: ${to}`,
      'Content-Type: text/plain; charset="UTF-8"',
      "MIME-Version: 1.0",
      `Subject: ${subject}`,
      "",
      body,
    ].join("\n");

    const base64EncodedEmail = btoa(unescape(encodeURIComponent(email)))
      .replace(/\+/g, "-")
      .replace(/\//g, "_")
      .replace(/=+$/, "");

    $.ajax({
      url: "https://www.googleapis.com/gmail/v1/users/me/messages/send",
      type: "POST",
      contentType: "application/json",
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
      data: JSON.stringify({
        raw: base64EncodedEmail,
      }),
      success: function (response) {
        Swal.fire({
          title: "Correo enviado",
          text: "El correo electrónico se ha enviado correctamente.",
          icon: "success",
          confirmButtonText: "Aceptar",
        });
        $("#sendEmailForm")[0].reset();
        $("#contentSection").hide();
        $("#cameraSection").show();
      },
      error: function (xhr, status, error) {
        console.error("Error al enviar el correo:", xhr.responseText);
        Swal.fire({
          title: "Error",
          text: "Hubo un problema al enviar el correo electrónico.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
      },
    });
  }
});
